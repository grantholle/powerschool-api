<?php

namespace GrantHolle\PowerSchool;

use GrantHolle\PowerSchool\Exception;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;
use GrantHolle\PowerSchool\Exception\MissingClientCredentialsException;
use GrantHolle\PowerSchool\Exception\MissingServerAddressException;

class Request
{
    /* @var string */
    public const AUTH_TOKEN = 'authToken';

    /* @var string */
    public const CLIENT_ID = 'clientId';

    /* @var string */
    public const CLIENT_SECRET = 'clientSecret';

    /* @var GuzzleHttp\Client */
    private $client;

    /* @var Symfony\Component\Cache\Simple\FilesystemCache */
    private $cache;

    /* @var string */
    private $clientId;

    /* @var string */
    private $clientSecret;

    /* @var string */
    private $authToken;

    /**
     * Creates a new Request object to interact with PS's api
     *
     * @param string $serverAddress The url of the server
     * @param string $clientId Optional if already cached. The client id obtained from installing a plugin with oauth enabled
     * @param string $clientSecret Optional if already cached. The client secret obtained from installing a plugin with oauth enabled
     */
    public function __construct(string $serverAddress = null, string $clientId = null, string $clientSecret = null)
    {
        $this->cache = new FilesystemCache();

        if (is_null($serverAddress)) {
            throw new MissingServerAddressException('No PowerSchool server address has been configured');
        }

        $this->client = new Client(['base_uri' => $serverAddress]);

        // Cache the client id and secret in case they aren't included
        if ($clientId) {
            $this->setAndCacheClientId($clientId);
        } else {
            $this->clientId = $this->cache->get(self::CLIENT_ID, false);
        }

        if ($clientSecret) {
            $this->setAndCacheClientSecret($clientSecret);
        } else {
            $this->clientSecret = $this->cache->get(self::CLIENT_SECRET, false);
        }

        $this->authToken = $this->cache->get(self::AUTH_TOKEN, false);
        $this->authenticate();
    }

    /**
     * Makes an api call to PowerSchool
     *
     * @param string $method The HTTP method to use
     * @param string $endpoint The api endpoint to call
     * @param Array $options The HTTP options
     * @param bool $returnResponse Return a response or just decoded
     * @return Array
     */
    public function makeRequest(string $method, string $endpoint, Array $options, bool $returnResponse)
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        // Force json
        $options['headers']['Accept'] = 'application/json';
        $options['headers']['Content-Type'] = 'application/json';

        // Add the auth token for the header
        $options['headers']['Authorization'] = 'Bearer ' . $this->authToken;

        // Don't throw exceptions for 4xx and 5xx errors
        $options['http_errors'] = false;

        $response = $this->client->request($method, $endpoint, $options);

        // If the response is an expired token, reauthenticate and try again
        if ($response->getStatusCode() === 401) {
            $wwwHeader = $response->getHeader('WWW-Authenticate');

            if (strpos($wwwHeader, 'expired') !== false) {
                return $this->authenticate(true)->request($method, $endpoint, $options);
            }
        }

        $body = json_decode($response->getBody()->getContents());

        if ($returnResponse) {
            return response()->json($body, $response->getStatusCode());
        }

        return $body;
    }

    /**
     * Sets and caches client credentials
     *
     * @param string $clientId The client id obtained from installing a plugin with oauth enabled
     * @param string $clientSecret The client secret obtained from installing a plugin with oauth enabled
     * @return $this
     */
    public function setClientCredentials(string $clientId, string $clientSecret)
    {
        return $this->setAndCacheClientId($clientId)
            ->setAndCacheClientSecret($clientSecret);
    }

    /**
     * Sets and caches the client id
     *
     * @param string $clientId The client id obtained from installing a plugin with oauth enabled
     * @return $this
     */
    public function setAndCacheClientId(string $clientId)
    {
        $this->clientId = $clientId;
        $this->cache->set(self::CLIENT_ID, $clientId);

        return $this;
    }

    /**
     * Sets and caches the client secret
     *
     * @param string $clientSecret The client secret obtained from installing a plugin with oauth enabled
     * @return $this
     */
    public function setAndCacheClientSecret(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
        $this->cache->set(self::CLIENT_SECRET, $clientSecret);

        return $this;
    }

    /**
     * Authenticates against the api and retrieves an auth token
     *
     * @param boolean $force Force authentication even if there is an existing token
     * @return $this
     */
    public function authenticate(bool $force = false)
    {
        // Check if there is already a token and we're not doing a force-retrieval
        if (!$force && $this->authToken) {
            return $this;
        }

        // Double check that there are client credentials
        if (!$this->clientId || !$this->clientSecret) {
            throw new MissingClientCredentialsException('Missing either client ID or secret. Cannot authenticate with PowerSchool API.');
        }

        // Fetch and cache if there isn't
        $token = base64_encode($this->clientId . ':' . $this->clientSecret);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . $token,
        ];

        // Retrieve the access token
        $response = $this->client->post('/oauth/access_token', [
            'headers' => $headers,
            'body' => 'grant_type=client_credentials'
        ]);

        $json = json_decode($response->getBody()->getContents());

        // Set and cache the auth token
        $this->authToken = $json->access_token;
        $this->cache->set(self::AUTH_TOKEN, $this->authToken);

        return $this;
    }
}

<?php

namespace PowerSchool;

use PowerSchool\Exception;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\FilesystemCache;
use PowerSchool\Exception\MissingClientCredentialsException;

class Request
{
    private const AUTH_TOKEN = 'authToken';
    private const CLIENT_ID = 'clientId';
    private const CLIENT_SECRET = 'clientSecret';
    private $client;
    private $cache;
    private $clientId;
    private $clientSecret;
    private $authToken;

    /**
     * Creates a new Request object to interact with PS's api
     *
     * @param string $serverAddress The url of the server
     * @param string $clientId Optional if already cached. The client id obtained from installing a plugin with oauth enabled
     * @param string $clientSecret Optional if already cached. The client secret obtained from installing a plugin with oauth enabled
     */
    public function __construct(string $serverAddress, string $clientId = null, string $clientSecret = null)
    {
        $this->client = new Client(['base_uri' => $serverAddress]);
        $this->cache = new FilesystemCache();

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
    }

    /**
     * Makes an api call to PowerSchool
     *
     * @param string $endpoint The api endpoint to call
     * @param string $method The HTTP method to use
     * @param Array $options The HTTP options
     * @return void
     */
    public function makeRequest(string $endpoint)
    {
        // If the response is an expired token, reauthenticate
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
        // Check if there is already a token and we're not forcing retrieving one
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

        $json = json_decode((string)$response->getBody());

        // Set and cache the auth token
        $this->authToken = $json->access_token;
        $this->cache->set(self::AUTH_TOKEN, $this->authToken);

        return $this;
    }
}

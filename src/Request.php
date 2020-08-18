<?php

namespace GrantHolle\PowerSchool\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException;
use Illuminate\Support\Facades\Response;

class Request
{
    /* @var string */
    public const AUTH_TOKEN = 'authToken';

    /* @var string */
    public const CLIENT_ID = 'clientId';

    /* @var string */
    public const CLIENT_SECRET = 'clientSecret';

    /* @var Client */
    private $client;

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
     * @param string $clientId The client id obtained from installing a plugin with oauth enabled
     * @param string $clientSecret The client secret obtained from installing a plugin with oauth enabled
     */
    public function __construct(string $serverAddress, string $clientId, string $clientSecret)
    {
        $this->client = new Client(['base_uri' => $serverAddress]);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authToken = Cache::get(self::AUTH_TOKEN, false);
    }

    /**
     * Makes an api call to PowerSchool
     *
     * @param string $method The HTTP method to use
     * @param string $endpoint The api endpoint to call
     * @param array $options The HTTP options
     * @param bool $returnResponse Return a response or just decoded
     * @return mixed
     * @throws MissingClientCredentialsException
     */
    public function makeRequest(string $method, string $endpoint, array $options, bool $returnResponse = false)
    {
        $this->authenticate();

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

            if (strpos($wwwHeader[0], 'expired') !== false) {
                return $this->authenticate(true)
                    ->makeRequest($method, $endpoint, $options);
            }
        }

        $body = json_decode($response->getBody()->getContents());

        if ($returnResponse) {
            return Response::json($body, $response->getStatusCode());
        }

        return $body;
    }

    /**
     * Authenticates against the api and retrieves an auth token
     *
     * @param boolean $force Force authentication even if there is an existing token
     * @return $this
     * @throws MissingClientCredentialsException
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
        Cache::put(self::AUTH_TOKEN, $this->authToken, now()->addSeconds($json->expires_in));

        return $this;
    }
}

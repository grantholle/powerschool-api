<?php

namespace GrantHolle\PowerSchool\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use GrantHolle\PowerSchool\Api\Exception\MissingClientCredentialsException;
use Illuminate\Support\Facades\Response as LaravelResponse;

class Request
{
    /* @var string */
    public const AUTH_TOKEN = 'powerschool_token';

    public bool $cacheToken;
    protected Client $client;
    protected string $clientId;
    protected string $clientSecret;
    protected string $authToken;
    protected int $attempts = 0;

    /**
     * Creates a new Request object to interact with PS's api
     *
     * @param string $serverAddress The url of the server
     * @param string $clientId The client id obtained from installing a plugin with oauth enabled
     * @param string $clientSecret The client secret obtained from installing a plugin with oauth enabled
     * @param bool $cacheToken
     */
    public function __construct(string $serverAddress, string $clientId, string $clientSecret, bool $cacheToken = true)
    {
        $this->client = new Client(['base_uri' => $serverAddress]);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cacheToken = $cacheToken;

        if ($this->cacheToken) {
            $this->authToken = Cache::get(self::AUTH_TOKEN, false);
        }
    }

    /**
     * Makes an api call to PowerSchool
     */
    public function makeRequest(string $method, string $endpoint, array $options, bool $returnResponse = false): JsonResponse|array
    {
        $this->authenticate();
        $this->attempts++;

        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        // Force json
        $options['headers']['Accept'] = 'application/json';
        $options['headers']['Content-Type'] = 'application/json';

        // Add the auth token for the header
        $options['headers']['Authorization'] = 'Bearer ' . $this->authToken;

        // Throw exceptions for 4xx and 5xx errors
        $options['http_errors'] = true;

        try {
            $response = $this->getClient()
                ->request($method, $endpoint, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();

            // If the response is an expired token, reauthenticate and try again
            if ($response->getStatusCode() === 401 && $this->attempts < 3) {
                return $this->authenticate(true)
                    ->makeRequest($method, $endpoint, $options);
            }

            ray($response->getStatusCode());
            ray()->json($response->getBody()->getContents());

            throw $exception;
        }

        $this->attempts = 0;
        $body = json_decode($response->getBody()->getContents(), true);

        if ($returnResponse) {
            return LaravelResponse::json($body, $response->getStatusCode());
        }

        return $body;
    }

    /**
     * Authenticates against the api and retrieves an auth token
     *
     * @param boolean $force Force authentication even if there is an existing token
     * @return $this
     * @throws MissingClientCredentialsException|\GuzzleHttp\Exception\GuzzleException
     */
    public function authenticate(bool $force = false): static
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
        $response = $this->getClient()
            ->post('/oauth/access_token', [
                'headers' => $headers,
                'body' => 'grant_type=client_credentials'
            ]);

        $json = json_decode($response->getBody()->getContents());

        // Set and cache the auth token
        $this->authToken = $json->access_token;

        if ($this->cacheToken) {
            Cache::put(self::AUTH_TOKEN, $this->authToken, now()->addSeconds($json->expires_in));
        }

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}

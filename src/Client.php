<?php

namespace L1Ntu\Frigate;

use L1Ntu\Frigate\Exceptions\FrigateAuthException;
use L1Ntu\Frigate\Exceptions\FrigateConnectException;
use L1Ntu\Frigate\Exceptions\FrigateException;
use L1Ntu\Frigate\Sections\App;
use L1Ntu\Frigate\Sections\Auth;
use L1Ntu\Frigate\Sections\Cameras;
use L1Ntu\Frigate\Sections\Events;
use L1Ntu\Frigate\Sections\Exports;
use L1Ntu\Frigate\Sections\Media;
use L1Ntu\Frigate\Sections\Recordings;
use L1Ntu\Frigate\Sections\Reviews;
use L1Ntu\Frigate\Sections\Users;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private string $baseUrl;
    private ?string $token = null;

    public function __construct(
        string $baseUrl,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private ?string $username = null,
        private ?string $password = null,
    ) {
        // Frigate serves its API under /api; endpoint paths are relative to it.
        $this->baseUrl = rtrim($baseUrl, '/') . '/api/';
    }

    public function app(): App
    {
        return new App($this);
    }

    public function auth(): Auth
    {
        return new Auth($this);
    }

    public function cameras(): Cameras
    {
        return new Cameras($this);
    }

    public function events(): Events
    {
        return new Events($this);
    }

    public function exports(): Exports
    {
        return new Exports($this);
    }

    public function media(): Media
    {
        return new Media($this);
    }

    public function recordings(): Recordings
    {
        return new Recordings($this);
    }

    public function reviews(): Reviews
    {
        return new Reviews($this);
    }

    public function users(): Users
    {
        return new Users($this);
    }

    public function isAuthEnabled(): bool
    {
        return $this->username !== null || $this->password !== null;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * Encodes a value for use as a URL path segment, e.g. an event ID or camera name.
     */
    public static function segment(string|int|float $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * Sends an API request, logging in first when credentials are configured.
     * If the session has expired (HTTP 401), it logs in again and retries once.
     *
     * Supported options:
     *  - 'query'   => array<string, mixed>  appended to the URL; null values are skipped
     *  - 'json'    => mixed                 encoded as the JSON request body
     *  - 'headers' => array<string, string>
     *
     * @throws FrigateException on connection failure, login failure or HTTP 4xx/5xx
     */
    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        if ($this->isAuthEnabled() && $this->token === null) {
            $this->login();
        }

        $response = $this->dispatch($method, $path, $options);

        if ($response->getStatusCode() === 401 && $this->isAuthEnabled()) {
            $this->token = null;
            $this->login();
            $response = $this->dispatch($method, $path, $options);
        }

        if ($response->getStatusCode() >= 400) {
            throw $this->httpError($response, $method, $path);
        }

        return $response;
    }

    /**
     * Like request(), but decodes a JSON object or array response.
     * An empty body returns an empty array.
     *
     * @return array<mixed>
     * @throws FrigateException
     */
    public function requestJson(string $method, string $path, array $options = []): array
    {
        $body = (string) $this->request($method, $path, $options)->getBody();
        if (trim($body) === '') {
            return [];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new FrigateException(sprintf('Expected a JSON response from %s %s', $method, $path));
        }

        return $data;
    }

    /**
     * Sends a request without login or status checks. Used by the auth section.
     *
     * @throws FrigateConnectException when the server cannot be reached
     */
    public function send(string $method, string $path, array $options = []): ResponseInterface
    {
        $uri = $this->baseUrl . ltrim($path, '/');
        $query = http_build_query($options['query'] ?? []);
        if ($query !== '') {
            $uri .= '?' . $query;
        }

        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($options['headers'] ?? [] as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (array_key_exists('json', $options)) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode($options['json'], JSON_THROW_ON_ERROR)));
        }

        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new FrigateConnectException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function login(): void
    {
        $this->auth()->login((string) $this->username, (string) $this->password);
    }

    private function dispatch(string $method, string $path, array $options): ResponseInterface
    {
        if ($this->token !== null) {
            $options['headers']['Cookie'] = 'frigate_token=' . $this->token;
        }

        return $this->send($method, $path, $options);
    }

    private function httpError(ResponseInterface $response, string $method, string $path): FrigateException
    {
        $status = $response->getStatusCode();
        $message = sprintf('Frigate responded with HTTP %d to %s %s', $status, $method, $path);

        $detail = trim(substr((string) $response->getBody(), 0, 300));
        if ($detail !== '') {
            $message .= ': ' . $detail;
        }

        return $status === 401
            ? new FrigateAuthException($message, $status)
            : new FrigateException($message, $status);
    }
}

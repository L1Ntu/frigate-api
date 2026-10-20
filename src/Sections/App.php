<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class App
{
    public function __construct(private Client $client)
    {
    }

    /**
     * True when the server answers its root endpoint with HTTP 200.
     */
    public function isHealthy(): bool
    {
        try {
            return $this->client->request('GET', '/')->getStatusCode() === 200;
        } catch (FrigateException) {
            return false;
        }
    }

    /**
     * @throws FrigateException
     */
    public function version(): string
    {
        return trim((string) $this->client->request('GET', 'version')->getBody());
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function stats(): array
    {
        return $this->client->requestJson('GET', 'stats');
    }

    /**
     * Full Frigate configuration as JSON.
     *
     * @return array<mixed>
     * @throws FrigateException
     */
    public function config(): array
    {
        return $this->client->requestJson('GET', 'config');
    }

    /**
     * Prometheus-format metrics.
     *
     * @throws FrigateException
     */
    public function metrics(): string
    {
        return (string) $this->client->request('GET', 'metrics')->getBody();
    }
}

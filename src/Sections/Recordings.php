<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Recordings
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Hourly recording summary for one camera, or for all cameras when $camera is null.
     *
     * @return array<mixed>
     * @throws FrigateException
     */
    public function summary(?string $camera = null, string $timezone = 'utc'): array
    {
        $path = $camera === null
            ? 'recordings/summary'
            : 'recordings/' . Client::segment($camera) . '/summary';

        return $this->client->requestJson('GET', $path, ['query' => ['timezone' => $timezone]]);
    }

    /**
     * Disk usage of recordings and clips.
     *
     * @return array<mixed>
     * @throws FrigateException
     */
    public function storage(): array
    {
        return $this->client->requestJson('GET', 'recordings/storage');
    }

    /**
     * Time ranges with no recordings.
     *
     * @param array<string, mixed> $filters cameras, before, after, scale
     * @return array<mixed>
     * @throws FrigateException
     */
    public function unavailable(array $filters = []): array
    {
        return $this->client->requestJson('GET', 'recordings/unavailable', ['query' => $filters]);
    }
}

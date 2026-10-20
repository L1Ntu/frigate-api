<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;
use Psr\Http\Message\ResponseInterface;

/**
 * Binary media endpoints. These return the raw PSR-7 response, so use getBody() or getHeaderLine('Content-Type').
 */
class Media
{
    public function __construct(private Client $client)
    {
    }

    /**
     * JPEG snapshot of an event. Options: download, timestamp, bbox, crop, height, quality.
     *
     * @param array<string, mixed> $options
     * @throws FrigateException
     */
    public function eventSnapshot(string $eventId, array $options = []): ResponseInterface
    {
        return $this->client->request(
            'GET',
            'events/' . Client::segment($eventId) . '/snapshot.jpg',
            ['query' => $options],
        );
    }

    /**
     * MP4 clip of an event. $padding adds seconds before and after the event.
     *
     * @throws FrigateException
     */
    public function eventClip(string $eventId, int $padding = 0): ResponseInterface
    {
        return $this->client->request(
            'GET',
            'events/' . Client::segment($eventId) . '/clip.mp4',
            ['query' => ['padding' => $padding]],
        );
    }

    /**
     * Latest decoded frame from a camera as JPEG.
     *
     * @param array<string, mixed> $options Query options such as height, quality, bbox, timestamp.
     * @throws FrigateException
     */
    public function latestFrame(string $camera, array $options = []): ResponseInterface
    {
        return $this->client->request(
            'GET',
            Client::segment($camera) . '/latest.jpg',
            ['query' => $options],
        );
    }

    /**
     * MP4 clip of a camera's recordings between two Unix timestamps.
     *
     * @throws FrigateException
     */
    public function recordingClip(string $camera, float $startTs, float $endTs): ResponseInterface
    {
        return $this->client->request(
            'GET',
            sprintf('%s/start/%s/end/%s/clip.mp4', Client::segment($camera), $startTs, $endTs),
        );
    }
}

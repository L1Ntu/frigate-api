<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Reviews
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Lists review items. Filters: cameras, labels, zones, reviewed, severity, before, after, limit.
     * Multi-value filters are comma-separated strings.
     *
     * @param array<string, mixed> $filters
     * @return array<mixed>
     * @throws FrigateException
     */
    public function list(array $filters = []): array
    {
        return $this->client->requestJson('GET', 'review', ['query' => $filters]);
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function get(string $reviewId): array
    {
        return $this->client->requestJson('GET', 'review/' . Client::segment($reviewId));
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function byEvent(string $eventId): array
    {
        return $this->client->requestJson('GET', 'review/event/' . Client::segment($eventId));
    }

    /**
     * Review counts for the given filters, grouped by camera.
     *
     * @param array<string, mixed> $filters cameras, labels, zones, timezone
     * @return array<mixed>
     * @throws FrigateException
     */
    public function summary(array $filters = []): array
    {
        return $this->client->requestJson('GET', 'review/summary', ['query' => $filters]);
    }

    /**
     * Marks review items as reviewed, or as not reviewed when $reviewed is false.
     *
     * @param string[] $reviewIds
     * @throws FrigateException
     */
    public function markViewed(array $reviewIds, bool $reviewed = true): void
    {
        $this->client->request('POST', 'reviews/viewed', [
            'json' => ['ids' => array_values($reviewIds), 'reviewed' => $reviewed],
        ]);
    }

    /**
     * @param string[] $reviewIds
     * @throws FrigateException
     */
    public function delete(array $reviewIds): void
    {
        $this->client->request('POST', 'reviews/delete', [
            'json' => ['ids' => array_values($reviewIds)],
        ]);
    }
}

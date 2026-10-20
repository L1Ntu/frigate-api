<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Events
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Lists events. Filters use Frigate's query names, e.g. camera, label, after, before, limit, offset,
     * has_clip, has_snapshot, time_range, timezone. Multi-value filters are comma-separated strings.
     *
     * @param array<string, mixed> $filters
     * @return array<mixed>
     * @throws FrigateException
     */
    public function list(array $filters = []): array
    {
        return $this->client->requestJson('GET', 'events', ['query' => $filters]);
    }

    /**
     * Semantic / text search over events. Takes the same filters as list(), plus 'query' and 'search_type'.
     *
     * @param array<string, mixed> $filters
     * @return array<mixed>
     * @throws FrigateException
     */
    public function search(array $filters = []): array
    {
        return $this->client->requestJson('GET', 'events/search', ['query' => $filters]);
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function get(string $eventId): array
    {
        return $this->client->requestJson('GET', 'events/' . Client::segment($eventId));
    }

    /**
     * Counts of events grouped by camera and label.
     *
     * @param array<string, mixed> $query
     * @return array<mixed>
     * @throws FrigateException
     */
    public function summary(array $query = []): array
    {
        return $this->client->requestJson('GET', 'events/summary', ['query' => $query]);
    }

    /**
     * @throws FrigateException
     */
    public function delete(string $eventId): void
    {
        $this->client->request('DELETE', 'events/' . Client::segment($eventId));
    }

    /**
     * Deletes several events at once.
     *
     * @param string[] $eventIds
     * @throws FrigateException
     */
    public function deleteMany(array $eventIds): void
    {
        $this->client->request('DELETE', 'events/', ['json' => ['event_ids' => array_values($eventIds)]]);
    }

    /**
     * @throws FrigateException
     */
    public function retain(string $eventId): void
    {
        $this->client->request('POST', 'events/' . Client::segment($eventId) . '/retain');
    }

    /**
     * @throws FrigateException
     */
    public function unretain(string $eventId): void
    {
        $this->client->request('DELETE', 'events/' . Client::segment($eventId) . '/retain');
    }

    /**
     * @throws FrigateException
     */
    public function setSubLabel(string $eventId, string $subLabel, ?float $score = null, ?string $camera = null): void
    {
        $this->client->request('POST', 'events/' . Client::segment($eventId) . '/sub_label', [
            'json' => array_filter([
                'subLabel' => $subLabel,
                'subLabelScore' => $score,
                'camera' => $camera,
            ], static fn($value) => $value !== null),
        ]);
    }

    /**
     * @throws FrigateException
     */
    public function setDescription(string $eventId, ?string $description): void
    {
        $this->client->request('POST', 'events/' . Client::segment($eventId) . '/description', [
            'json' => ['description' => $description],
        ]);
    }

    /**
     * @throws FrigateException
     */
    public function setLicensePlate(string $eventId, string $plate, ?float $score = null): void
    {
        $this->client->request('POST', 'events/' . Client::segment($eventId) . '/recognized_license_plate', [
            'json' => array_filter([
                'recognizedLicensePlate' => $plate,
                'recognizedLicensePlateScore' => $score,
            ], static fn($value) => $value !== null),
        ]);
    }
}

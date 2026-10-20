<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Exports
{
    public function __construct(private Client $client)
    {
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function list(): array
    {
        return $this->client->requestJson('GET', 'exports');
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function get(string $exportId): array
    {
        return $this->client->requestJson('GET', 'exports/' . Client::segment($exportId));
    }

    /**
     * Starts an export of a camera's recordings between two Unix timestamps.
     *
     * Options: name, source ('recordings' or 'timeline'), image_path, export_case_id, chapters.
     *
     * @param array<string, mixed> $options
     * @return array<mixed>
     * @throws FrigateException
     */
    public function start(string $camera, float $startTs, float $endTs, array $options = []): array
    {
        return $this->client->requestJson(
            'POST',
            sprintf('export/%s/start/%s/end/%s', Client::segment($camera), $startTs, $endTs),
            ['json' => (object) $options],
        );
    }

    /**
     * Like start(), with ffmpeg tuning. Options: name, source, image_path, export_case_id,
     * ffmpeg_input_args, ffmpeg_output_args, cpu_fallback.
     *
     * @param array<string, mixed> $options
     * @return array<mixed>
     * @throws FrigateException
     */
    public function startCustom(string $camera, float $startTs, float $endTs, array $options = []): array
    {
        return $this->client->requestJson(
            'POST',
            sprintf('export/custom/%s/start/%s/end/%s', Client::segment($camera), $startTs, $endTs),
            ['json' => (object) $options],
        );
    }

    /**
     * @throws FrigateException
     */
    public function rename(string $exportId, string $name): void
    {
        $this->client->request('PATCH', 'export/' . Client::segment($exportId) . '/rename', [
            'json' => ['name' => $name],
        ]);
    }

    /**
     * @param string[] $exportIds
     * @throws FrigateException
     */
    public function bulkDelete(array $exportIds): void
    {
        $this->client->request('POST', 'exports/delete', ['json' => ['ids' => array_values($exportIds)]]);
    }

    /**
     * Moves exports to another export case, or removes them from their case when $caseId is null.
     *
     * @param string[] $exportIds
     * @throws FrigateException
     */
    public function bulkReassign(array $exportIds, ?string $caseId): void
    {
        $this->client->request('POST', 'exports/reassign', [
            'json' => ['ids' => array_values($exportIds), 'export_case_id' => $caseId],
        ]);
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function activeJobs(): array
    {
        return $this->client->requestJson('GET', 'jobs/export');
    }

    /**
     * @return array<mixed>
     * @throws FrigateException
     */
    public function jobStatus(string $exportId): array
    {
        return $this->client->requestJson('GET', 'jobs/export/' . Client::segment($exportId));
    }
}

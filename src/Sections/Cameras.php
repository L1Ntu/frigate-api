<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Cameras
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Changes a camera setting at runtime, e.g. set('front', 'detect', 'ON') or
     * set('front', 'motion', 'OFF'). The feature names and values are Frigate's own.
     *
     * @throws FrigateException
     */
    public function set(string $camera, string $feature, string $value, ?string $subCommand = null): void
    {
        $path = sprintf('camera/%s/set/%s', Client::segment($camera), Client::segment($feature));
        if ($subCommand !== null) {
            $path .= '/' . Client::segment($subCommand);
        }

        $this->client->request('PUT', $path, ['json' => ['value' => $value]]);
    }

    /**
     * Removes a camera from the Frigate configuration.
     *
     * @throws FrigateException
     */
    public function delete(string $camera, bool $deleteExports = false): void
    {
        $this->client->request(
            'DELETE',
            'cameras/' . Client::segment($camera),
            ['query' => ['delete_exports' => $deleteExports ? 1 : 0]],
        );
    }
}

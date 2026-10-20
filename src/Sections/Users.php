<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateException;

/**
 * User management. Requires an admin session.
 */
class Users
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
        return $this->client->requestJson('GET', 'users');
    }

    /**
     * @throws FrigateException
     */
    public function create(string $username, string $password, ?string $role = null): void
    {
        $body = array_filter(
            ['username' => $username, 'password' => $password, 'role' => $role],
            static fn($value) => $value !== null,
        );

        $this->client->request('POST', 'users', ['json' => $body]);
    }

    /**
     * @throws FrigateException
     */
    public function delete(string $username): void
    {
        $this->client->request('DELETE', 'users/' . Client::segment($username));
    }

    /**
     * @throws FrigateException
     */
    public function setPassword(string $username, string $password, ?string $oldPassword = null): void
    {
        $body = array_filter(
            ['password' => $password, 'old_password' => $oldPassword],
            static fn($value) => $value !== null,
        );

        $this->client->request('PUT', 'users/' . Client::segment($username) . '/password', ['json' => $body]);
    }

    /**
     * @throws FrigateException
     */
    public function setRole(string $username, string $role): void
    {
        $this->client->request('PUT', 'users/' . Client::segment($username) . '/role', [
            'json' => ['role' => $role],
        ]);
    }
}

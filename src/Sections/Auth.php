<?php

namespace L1Ntu\Frigate\Sections;

use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateAuthException;
use L1Ntu\Frigate\Exceptions\FrigateException;

class Auth
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Logs in and stores the frigate_token session cookie on the client.
     *
     * @throws FrigateAuthException when the credentials are rejected or no token is issued
     */
    public function login(string $username, string $password): string
    {
        $response = $this->client->send('POST', 'login', [
            'json' => ['user' => $username, 'password' => $password],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new FrigateAuthException(
                sprintf('Frigate login failed with HTTP %d', $response->getStatusCode()),
                $response->getStatusCode(),
            );
        }

        $token = $this->extractToken($response->getHeader('Set-Cookie'));
        if ($token === null) {
            throw new FrigateAuthException('Frigate login succeeded but returned no frigate_token cookie');
        }

        $this->client->setToken($token);

        return $token;
    }

    /**
     * Ends the session and forgets the stored token.
     *
     * @throws FrigateException
     */
    public function logout(): void
    {
        $this->client->request('GET', 'logout');
        $this->client->setToken(null);
    }

    /**
     * The logged-in user's profile (username and role).
     *
     * @return array<mixed>
     * @throws FrigateException
     */
    public function profile(): array
    {
        return $this->client->requestJson('GET', 'profile');
    }

    /**
     * @param string[] $setCookieHeaders
     */
    private function extractToken(array $setCookieHeaders): ?string
    {
        foreach ($setCookieHeaders as $header) {
            if (preg_match('/frigate[_-]token=([^;]+)/i', $header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

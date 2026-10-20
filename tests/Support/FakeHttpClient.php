<?php

namespace L1Ntu\Frigate\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * PSR-18 client that replays queued responses and records every request it receives.
 */
class FakeHttpClient implements ClientInterface
{
    /** @var RequestInterface[] */
    public array $requests = [];

    /** @var array<ResponseInterface|ClientExceptionInterface> */
    private array $queue = [];

    public function queue(ResponseInterface|ClientExceptionInterface $result): void
    {
        $this->queue[] = $result;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new RuntimeException('No queued response for ' . $request->getMethod() . ' ' . $request->getUri());
        }
        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }
}

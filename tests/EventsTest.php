<?php

namespace L1Ntu\Frigate\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    private FakeHttpClient $http;
    private Client $client;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $factory = new HttpFactory();
        $this->client = new Client('http://frigate.local:5000', $this->http, $factory, $factory);
    }

    public function testListPassesFiltersAsQueryAndSkipsNulls(): void
    {
        $this->http->queue(new Response(200, [], '[]'));

        $this->client->events()->list(['camera' => 'front', 'limit' => 10, 'before' => null]);

        $this->assertSame(
            'http://frigate.local:5000/api/events?camera=front&limit=10',
            (string) $this->http->requests[0]->getUri(),
        );
    }

    public function testEventIdIsUrlEncoded(): void
    {
        $this->http->queue(new Response(200, [], '{}'));

        $this->client->events()->get('1700000000.1-ab/cd');

        $this->assertSame(
            'http://frigate.local:5000/api/events/1700000000.1-ab%2Fcd',
            (string) $this->http->requests[0]->getUri(),
        );
    }

    public function testSetSubLabelOmitsNullOptionalFields(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->client->events()->setSubLabel('evt-1', 'Ihor');

        $this->assertSame('{"subLabel":"Ihor"}', (string) $this->http->requests[0]->getBody());
    }

    public function testDeleteManySendsIdsInBody(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->client->events()->deleteMany(['a', 'b']);

        $request = $this->http->requests[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('{"event_ids":["a","b"]}', (string) $request->getBody());
    }

    public function testCameraSetBuildsFeaturePath(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->client->cameras()->set('front', 'detect', 'ON');

        $this->assertSame(
            'http://frigate.local:5000/api/camera/front/set/detect',
            (string) $this->http->requests[0]->getUri(),
        );
        $this->assertSame('{"value":"ON"}', (string) $this->http->requests[0]->getBody());
    }

    public function testCameraSetAppendsSubCommandToPath(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->client->cameras()->set('front', 'ptz', 'left', 'move');

        $this->assertSame(
            'http://frigate.local:5000/api/camera/front/set/ptz/move',
            (string) $this->http->requests[0]->getUri(),
        );
    }
}

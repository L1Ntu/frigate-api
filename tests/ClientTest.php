<?php

namespace L1Ntu\Frigate\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use L1Ntu\Frigate\Client;
use L1Ntu\Frigate\Exceptions\FrigateAuthException;
use L1Ntu\Frigate\Exceptions\FrigateConnectException;
use L1Ntu\Frigate\Exceptions\FrigateException;
use L1Ntu\Frigate\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

class ClientTest extends TestCase
{
    private FakeHttpClient $http;
    private HttpFactory $factory;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $this->factory = new HttpFactory();
    }

    private function client(?string $username = null, ?string $password = null): Client
    {
        return new Client(
            'http://frigate.local:5000/',
            $this->http,
            $this->factory,
            $this->factory,
            $username,
            $password,
        );
    }

    private function connectionError(): ClientExceptionInterface
    {
        return new class ('connection refused') extends RuntimeException implements ClientExceptionInterface {};
    }

    public function testBaseUrlGetsApiPrefix(): void
    {
        $this->http->queue(new Response(200, [], '{}'));

        $this->client()->app()->stats();

        $this->assertSame('http://frigate.local:5000/api/stats', (string) $this->http->requests[0]->getUri());
    }

    public function testAnonymousRequestHasNoCookie(): void
    {
        $this->http->queue(new Response(200, [], '{}'));

        $this->client()->app()->stats();

        $this->assertFalse($this->http->requests[0]->hasHeader('Cookie'));
    }

    public function testLoginStoresTokenAndSendsItOnLaterRequests(): void
    {
        $this->http->queue(new Response(200, ['Set-Cookie' => 'frigate_token=abc.def; Path=/; HttpOnly']));
        $this->http->queue(new Response(200, [], '{}'));

        $this->client('admin', 'secret')->app()->stats();

        $login = $this->http->requests[0];
        $this->assertSame('POST', $login->getMethod());
        $this->assertSame('http://frigate.local:5000/api/login', (string) $login->getUri());
        $this->assertSame('{"user":"admin","password":"secret"}', (string) $login->getBody());

        $this->assertSame('frigate_token=abc.def', $this->http->requests[1]->getHeaderLine('Cookie'));
    }

    public function testExpiredSessionIsRenewedOnce(): void
    {
        $this->http->queue(new Response(200, ['Set-Cookie' => 'frigate_token=old']));
        $this->http->queue(new Response(401, [], 'expired'));
        $this->http->queue(new Response(200, ['Set-Cookie' => 'frigate_token=new']));
        $this->http->queue(new Response(200, [], '{"ok":true}'));

        $data = $this->client('admin', 'secret')->app()->stats();

        $this->assertSame(['ok' => true], $data);
        $this->assertSame('frigate_token=new', $this->http->requests[3]->getHeaderLine('Cookie'));
    }

    public function testRejectedLoginThrowsAuthException(): void
    {
        $this->http->queue(new Response(401, [], 'Unauthorized'));

        $this->expectException(FrigateAuthException::class);

        $this->client('admin', 'wrong')->app()->stats();
    }

    public function testLoginWithoutTokenCookieThrowsAuthException(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->expectException(FrigateAuthException::class);

        $this->client('admin', 'secret')->app()->stats();
    }

    public function testHttpErrorStatusThrowsFrigateExceptionWithStatusCode(): void
    {
        $this->http->queue(new Response(500, [], 'boom'));

        try {
            $this->client()->app()->stats();
            $this->fail('Expected FrigateException');
        } catch (FrigateException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertStringContainsString('boom', $e->getMessage());
        }
    }

    public function testTransportFailureIsWrappedAsConnectException(): void
    {
        $this->http->queue($this->connectionError());

        $this->expectException(FrigateConnectException::class);

        $this->client()->app()->stats();
    }

    public function testIsHealthyReturnsFalseWhenServerIsDown(): void
    {
        $this->http->queue($this->connectionError());

        $this->assertFalse($this->client()->app()->isHealthy());
    }

    public function testIsHealthyReturnsTrueOnOk(): void
    {
        $this->http->queue(new Response(200, [], 'Frigate is running'));

        $this->assertTrue($this->client()->app()->isHealthy());
    }

    public function testEmptyJsonBodyDecodesToEmptyArray(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->assertSame([], $this->client()->exports()->list());
    }

    public function testRenameSendsJsonBody(): void
    {
        $this->http->queue(new Response(200, [], '{}'));

        $this->client()->exports()->rename('exp-1', 'Front door');

        $request = $this->http->requests[0];
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('http://frigate.local:5000/api/export/exp-1/rename', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('{"name":"Front door"}', (string) $request->getBody());
    }

    public function testExportStartWithoutOptionsSendsEmptyObject(): void
    {
        $this->http->queue(new Response(200, [], '{"success":true}'));

        $this->client()->exports()->start('front', 1700000000, 1700000600);

        $this->assertSame('{}', (string) $this->http->requests[0]->getBody());
    }
}

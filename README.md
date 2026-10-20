# frigate-api

PHP client for the [Frigate NVR](https://frigate.video) HTTP API. Covers the main API groups (app, auth, events, exports, recordings, reviews, media, cameras, users) with typed methods. Any PSR-18 HTTP client works.

API reference: https://docs.frigate.video/integrations/api/frigate-http-api/

## Requirements

- PHP 8.1+
- A PSR-18 HTTP client, e.g. `guzzlehttp/guzzle`
- A PSR-17 factory, e.g. `guzzlehttp/psr7`

## Installation

```bash
composer require l1ntu/frigate-api guzzlehttp/guzzle guzzlehttp/psr7
```

## Usage

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use L1Ntu\Frigate\Client;

$factory = new HttpFactory();

$frigate = new Client(
    baseUrl: 'http://frigate.local:5000',   // /api is appended automatically
    httpClient: new GuzzleClient(),
    requestFactory: $factory,
    streamFactory: $factory,
    username: 'admin',                      // omit both when Frigate auth is disabled
    password: 'secret',
);

$frigate->app()->version();
$frigate->app()->stats();

$events = $frigate->events()->list(['camera' => 'front', 'limit' => 20]);
$frigate->events()->setSubLabel($events[0]['id'], 'Ihor');

$frigate->exports()->start('front', $startTs, $endTs, ['name' => 'Evening']);

// Binary endpoints return a PSR-7 response
$jpeg = $frigate->media()->eventSnapshot($events[0]['id'])->getBody();
```

When credentials are set, the client logs in on the first request, keeps the
`frigate_token` cookie, and logs in again once if the session expires.

## Sections

| Method | Covers |
|---|---|
| `app()` | health, version, stats, config, metrics |
| `auth()` | login, logout, profile |
| `events()` | list, search, get, delete, retain, sub-labels, descriptions, license plates |
| `exports()` | list, start, rename, bulk delete/reassign, job status |
| `recordings()` | summary, storage, unavailable ranges |
| `reviews()` | list, get, summary, mark viewed, delete |
| `media()` | event snapshot/clip, latest frame, recording clip |
| `cameras()` | change a camera feature at runtime, delete a camera |
| `users()` | list, create, delete, change password or role (admin) |

Query filters and bodies are passed as arrays with Frigate's own parameter names, so new options work without a library update.

Multi-value filters such as `labels` or `zones` are comma-separated strings, as in the Frigate docs.

## Errors

All exceptions extend `L1Ntu\Frigate\Exceptions\FrigateException`:

- `FrigateConnectException`: the server could not be reached
- `FrigateAuthException`: login was rejected, returned no token, or the session is unauthorized
- `FrigateException`: any other HTTP 4xx/5xx; `getCode()` returns the status

## Development

```bash
composer install
composer test
```

## License

MIT

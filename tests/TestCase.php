<?php

namespace Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetHttpClient();

        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [],
            ], 200),
        ]);
    }

    protected function fakeTelegramResponse(mixed $response): void
    {
        $this->resetHttpClient();

        Http::fake([
            'https://api.telegram.org/*' => $response,
        ]);
    }

    private function resetHttpClient(): void
    {
        Http::swap(new Factory($this->app->make(Dispatcher::class)));
        Http::preventStrayRequests();
    }
}

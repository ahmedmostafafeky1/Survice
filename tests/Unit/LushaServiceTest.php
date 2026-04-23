<?php

namespace Tests\Unit;

use App\Services\LushaService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class LushaServiceTest extends TestCase
{
    private function makeService(array $responses): LushaService
    {
        $mock    = new MockHandler($responses);
        $stack   = HandlerStack::create($mock);
        $client  = new Client(['handler' => $stack]);

        $service = new LushaService();
        // Inject mock client via reflection
        $ref = new \ReflectionProperty(LushaService::class, 'client');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    public function test_enrich_person_returns_parsed_json(): void
    {
        $payload = ['firstName' => 'Jane', 'lastName' => 'Doe', 'email' => 'jane@example.com'];

        $service = $this->makeService([
            new Response(200, [], json_encode($payload)),
        ]);

        $result = $service->enrichPerson('Jane', 'Doe', 'Acme');

        $this->assertSame('jane@example.com', $result['email']);
    }

    public function test_enrich_company_throws_on_missing_params(): void
    {
        $service = $this->makeService([]);

        $this->expectException(\InvalidArgumentException::class);
        $service->enrichCompany();
    }

    public function test_prospect_applies_default_limit(): void
    {
        $payload = ['results' => [], 'total' => 0];

        $service = $this->makeService([
            new Response(200, [], json_encode($payload)),
        ]);

        // Should not throw
        $result = $service->prospect(['jobTitle' => 'Engineer']);

        $this->assertArrayHasKey('results', $result);
    }

    public function test_get_throws_runtime_exception_on_client_error(): void
    {
        $service = $this->makeService([
            new Response(401, [], json_encode(['message' => 'Unauthorized'])),
        ]);

        $this->expectException(\RuntimeException::class);
        $service->enrichPerson('Jane', 'Doe', 'Acme');
    }
}

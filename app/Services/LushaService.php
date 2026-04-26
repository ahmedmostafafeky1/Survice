<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class LushaService
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('lusha.api_key');

        $this->client = new Client([
            'base_uri' => rtrim(config('lusha.base_url'), '/') . '/',
            'timeout'  => config('lusha.timeout', 30),
            'headers'  => [
                'api_key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    /**
     * Enrich a person's contact details using their name and company.
     *
     * @param  string  $firstName
     * @param  string  $lastName
     * @param  string  $company    Company name or domain
     * @return array<string,mixed>
     *
     * @throws \RuntimeException
     */
    public function enrichPerson(string $firstName, string $lastName, string $company): array
    {
        $params = [
            'firstName'   => $firstName,
            'lastName'    => $lastName,
            'company'     => $company,
        ];

        return $this->get('v1/person', $params);
    }

    /**
     * Enrich company data by domain or company name.
     *
     * @param  string|null  $domain
     * @param  string|null  $companyName
     * @return array<string,mixed>
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function enrichCompany(?string $domain = null, ?string $companyName = null): array
    {
        if (empty($domain) && empty($companyName)) {
            throw new \InvalidArgumentException('Either domain or companyName must be provided.');
        }

        $params = array_filter([
            'domain'      => $domain,
            'companyName' => $companyName,
        ]);

        return $this->get('v1/company', $params);
    }

    /**
     * Run a prospecting query to discover leads matching the given criteria.
     *
     * Available filters (all optional):
     *  - jobTitle        (string)
     *  - companyName     (string)
     *  - country         (string, ISO 3166-1 alpha-2)
     *  - industry        (string)
     *  - companySize     (string, e.g. "11-50")
     *  - department      (string)
     *  - seniorityLevel  (string, e.g. "Manager")
     *  - page            (int, 1-based)
     *  - pageSize        (int, max 100)
     *
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     *
     * @throws \RuntimeException
     */
    public function prospect(array $filters = []): array
    {
        $params = array_merge(
            ['pageSize' => config('lusha.default_limit', 25), 'page' => 1],
            array_filter($filters, static fn ($v) => $v !== null && $v !== ''),
        );

        return $this->get('v1/prospecting', $params);
    }

    /**
     * Retrieve contact details (email + phone) for a Lusha contact ID.
     *
     * @param  string  $contactId
     * @return array<string,mixed>
     *
     * @throws \RuntimeException
     */
    public function getContactDetails(string $contactId): array
    {
        return $this->get("v1/person/{$contactId}/contact");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Perform a GET request and return the decoded JSON body.
     *
     * @param  string               $endpoint
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     *
     * @throws \RuntimeException
     */
    private function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, ['query' => $query]);

            return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $body   = (string) $e->getResponse()->getBody();

            Log::error('Lusha API client error', [
                'endpoint' => $endpoint,
                'status'   => $status,
                'body'     => $body,
            ]);

            throw new \RuntimeException("Lusha API error ({$status}): {$body}", $status, $e);
        } catch (GuzzleException $e) {
            Log::error('Lusha API request failed', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            throw new \RuntimeException('Lusha API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}

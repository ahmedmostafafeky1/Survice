<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrichCompanyRequest;
use App\Http\Requests\EnrichPersonRequest;
use App\Http\Requests\ProspectLeadsRequest;
use App\Models\Lead;
use App\Services\LushaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadGenerationController extends Controller
{
    public function __construct(private readonly LushaService $lusha) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Prospecting
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Search for leads on Lusha using the prospecting endpoint.
     *
     * GET /api/leads/prospect
     */
    public function prospect(ProspectLeadsRequest $request): JsonResponse
    {
        $filters = array_filter([
            'jobTitle'       => $request->job_title,
            'companyName'    => $request->company_name,
            'country'        => $request->country,
            'industry'       => $request->industry,
            'companySize'    => $request->company_size,
            'department'     => $request->department,
            'seniorityLevel' => $request->seniority_level,
            'page'           => $request->page ?? 1,
            'pageSize'       => $request->page_size ?? config('lusha.default_limit'),
        ]);

        $data = $this->lusha->prospect($filters);

        return response()->json($data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Person enrichment
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Enrich a single person and optionally persist as a Lead.
     *
     * POST /api/leads/enrich/person
     */
    public function enrichPerson(EnrichPersonRequest $request): JsonResponse
    {
        $data = $this->lusha->enrichPerson(
            $request->first_name,
            $request->last_name,
            $request->company,
        );

        $lead = $this->upsertLeadFromPersonData($data);

        return response()->json([
            'lusha_data' => $data,
            'lead'       => $lead,
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Company enrichment
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Enrich company data by domain or company name.
     *
     * POST /api/leads/enrich/company
     */
    public function enrichCompany(EnrichCompanyRequest $request): JsonResponse
    {
        $data = $this->lusha->enrichCompany(
            $request->domain,
            $request->company_name,
        );

        return response()->json($data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Import
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Import selected contacts from a Lusha prospecting result into the leads table.
     *
     * POST /api/leads/import
     * Body: { "contacts": [ { lusha contact objects … } ] }
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'contacts'              => ['required', 'array', 'min:1'],
            'contacts.*.id'        => ['nullable', 'string'],
            'contacts.*.firstName' => ['required', 'string'],
            'contacts.*.lastName'  => ['required', 'string'],
        ]);

        $imported = [];

        foreach ($request->contacts as $contact) {
            $imported[] = $this->upsertLeadFromPersonData($contact);
        }

        return response()->json(['imported' => $imported], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CRUD for persisted leads
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List all persisted leads.
     *
     * GET /api/leads
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate(25)
        );
    }

    /**
     * Show a single persisted lead.
     *
     * GET /api/leads/{id}
     */
    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead);
    }

    /**
     * Update the status or notes on a persisted lead.
     *
     * PATCH /api/leads/{id}
     */
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,qualified,disqualified,converted'],
        ]);

        $lead->update($validated);

        return response()->json($lead);
    }

    /**
     * Delete a persisted lead (soft delete).
     *
     * DELETE /api/leads/{id}
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(null, 204);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create or update a Lead from a Lusha person/contact data array.
     *
     * @param  array<string,mixed>  $data
     */
    private function upsertLeadFromPersonData(array $data): Lead
    {
        $contactId = $data['id'] ?? null;

        $attributes = array_filter([
            'first_name'     => $data['firstName'] ?? null,
            'last_name'      => $data['lastName'] ?? null,
            'email'          => $data['email'] ?? ($data['emails'][0]['email'] ?? null),
            'phone'          => $data['phone'] ?? ($data['phones'][0]['normalizedNumber'] ?? null),
            'job_title'      => $data['jobTitle'] ?? null,
            'company_name'   => $data['company'] ?? ($data['companyName'] ?? null),
            'company_domain' => $data['companyDomain'] ?? null,
            'industry'       => $data['industry'] ?? null,
            'country'        => $data['country'] ?? null,
            'linkedin_url'   => $data['linkedinUrl'] ?? null,
            'raw_data'       => $data,
        ]);

        if ($contactId) {
            return Lead::updateOrCreate(
                ['lusha_contact_id' => $contactId],
                $attributes,
            );
        }

        // No Lusha ID – match on email if available, otherwise always insert
        $email = $attributes['email'] ?? null;

        if ($email) {
            return Lead::updateOrCreate(
                ['email' => $email],
                $attributes,
            );
        }

        return Lead::create($attributes);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a lead imported or discovered through Lusha.
 *
 * @property int         $id
 * @property string|null $lusha_contact_id   Lusha's internal contact identifier
 * @property string      $first_name
 * @property string      $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property string|null $company_name
 * @property string|null $company_domain
 * @property string|null $industry
 * @property string|null $country
 * @property string|null $linkedin_url
 * @property string      $status             pending|qualified|disqualified|converted
 * @property array|null  $raw_data           Full Lusha API response
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Lead extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lusha_contact_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'company_name',
        'company_domain',
        'industry',
        'country',
        'linkedin_url',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

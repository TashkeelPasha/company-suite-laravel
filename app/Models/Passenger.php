<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Passenger extends Model
{
    /** M/F/C/I on ERP-001 / ERP-002: Male, Female, Child, Infant. */
    public const PAX_TYPES = ['M', 'F', 'C', 'I'];

    /**
     * Pre-incident data from the Excel manifest (booking / APIS).
     * API camelCase key => DB column.
     */
    public const MANIFEST_FIELDS = [
        'lastName'           => 'last_name',
        'firstName'          => 'first_name',
        'paxType'            => 'pax_type',
        'dateOfBirth'        => 'date_of_birth',
        'nationality'        => 'nationality',
        'pnr'                => 'pnr',
        'routeFrom'          => 'route_from',
        'routeTo'            => 'route_to',
        'routeVia'           => 'route_via',
        'contactNumbers'     => 'contact_numbers',
        'primaryAddress'     => 'primary_address',
        'passportNumber'     => 'passport_number',
        'passportIssueDate'  => 'passport_issue_date',
        'passportIssuePlace' => 'passport_issue_place',
        'birthPlace'         => 'birth_place',
        'awardPlusNumber'    => 'award_plus_number',
        'nokName'            => 'nok_name',
        'nokRelationship'    => 'nok_relationship',
        'nokPhone'           => 'nok_phone',
    ];

    protected $table = 'passengers';
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'incident_id', 'name', 'seat_number', 'cnic',
        'flight_number', 'from_location', 'to_location',
        'departure_time', 'arrival_time',
        'last_name', 'first_name', 'pax_type', 'date_of_birth', 'nationality', 'pnr',
        'route_from', 'route_to', 'route_via', 'contact_numbers', 'primary_address',
        'passport_number', 'passport_issue_date', 'passport_issue_place', 'birth_place',
        'award_plus_number', 'nok_name', 'nok_relationship', 'nok_phone',
    ];

    protected $hidden = ['passport_number'];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'created_at' => 'datetime',
        'date_of_birth' => 'date',
        'passport_issue_date' => 'date',
        'contact_numbers' => 'array',
        // PII — encrypted with APP_KEY. Rotating APP_KEY makes these unreadable.
        'passport_number' => 'encrypted',
    ];

    /** Validation rules for the manifest fields; $prefix e.g. "passengers.*." or "passenger.". */
    public static function manifestRules(string $prefix = ''): array
    {
        return [
            $prefix.'lastName'           => 'nullable|string|max:255',
            $prefix.'firstName'          => 'nullable|string|max:255',
            $prefix.'paxType'            => 'nullable|in:'.implode(',', self::PAX_TYPES),
            $prefix.'dateOfBirth'        => 'nullable|date_format:Y-m-d|before_or_equal:today',
            $prefix.'nationality'        => 'nullable|string|max:100',
            $prefix.'pnr'                => 'nullable|string|max:20',
            $prefix.'routeFrom'          => 'nullable|string|max:255',
            $prefix.'routeTo'            => 'nullable|string|max:255',
            $prefix.'routeVia'           => 'nullable|string|max:255',
            $prefix.'contactNumbers'     => 'nullable|array|max:3',
            $prefix.'contactNumbers.*'   => 'nullable|string|max:50',
            $prefix.'primaryAddress'     => 'nullable|string|max:2000',
            $prefix.'passportNumber'     => 'nullable|string|max:50',
            $prefix.'passportIssueDate'  => 'nullable|date_format:Y-m-d',
            $prefix.'passportIssuePlace' => 'nullable|string|max:255',
            $prefix.'birthPlace'         => 'nullable|string|max:255',
            $prefix.'awardPlusNumber'    => 'nullable|string|max:50',
            $prefix.'nokName'            => 'nullable|string|max:255',
            $prefix.'nokRelationship'    => 'nullable|string|max:100',
            $prefix.'nokPhone'           => 'nullable|string|max:100',
        ];
    }

    /** "First Last" from the split name; falls back to $fallback when both are empty. */
    public static function displayName(?string $firstName, ?string $lastName, ?string $fallback = null): ?string
    {
        $name = trim(($firstName ?? '').' '.($lastName ?? ''));
        return $name !== '' ? $name : $fallback;
    }

    /** Age in whole years on $on (incident date), or today. */
    public function ageAt(?DateTimeInterface $on = null): ?int
    {
        return $this->date_of_birth ? (int) $this->date_of_birth->diffInYears(Carbon::instance($on ?? now())) : null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(PassengerUpdate::class);
    }

    public function relativeInfoUpdates(): HasMany
    {
        return $this->hasMany(RelativeInfoUpdate::class);
    }

    public function recordCards(): HasMany
    {
        return $this->hasMany(PassengerRecordCard::class);
    }

    public function informationForms(): HasMany
    {
        return $this->hasMany(PassengerInformationForm::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP-002 Passenger Information Form = the passenger's ONE contact person.
 * ERC sets them on the first call; other callers are referred to that person.
 * Append-only: an edit is a new row (the newest is current). Replacing the
 * person with someone else needs `change_reason`.
 */
class PassengerInformationForm extends Model
{
    /** API camelCase key => DB column. */
    public const FIELDS = [
        'informantLastName'           => 'informant_last_name',
        'informantFirstName'          => 'informant_first_name',
        'informantContactNumbers'     => 'informant_contact_numbers',
        'informantAddress'            => 'informant_address',
        'informantEmail'              => 'informant_email',
        'informantRelationship'       => 'informant_relationship',
        'informantIsRelativeOrMeeter' => 'informant_is_relative_or_meeter',
        'paxFlightDetails'            => 'pax_flight_details',
        'informantLanguages'          => 'informant_languages',
        'informantContactedPax'       => 'informant_contacted_pax',
        'informantContactDetails'     => 'informant_contact_details',
        'travellingWith'              => 'travelling_with',
        'nokDetails'                  => 'nok_details',
        'hasMedicalProblem'           => 'has_medical_problem',
        'medicalDetails'              => 'medical_details',
        'otherInformation'            => 'other_information',
    ];

    protected $table = 'passenger_information_forms';
    public $timestamps = false;

    // Writes only ever come from FIELDS (via ApiController::columnsFrom) plus the ids below.
    protected $guarded = ['id', 'created_at'];

    protected $casts = [
        'informant_contact_numbers' => 'array',
        'informant_is_relative_or_meeter' => 'boolean',
        'informant_contacted_pax' => 'boolean',
        'has_medical_problem' => 'boolean',
        // PII — encrypted with APP_KEY. Rotating APP_KEY makes these unreadable.
        'medical_details' => 'encrypted',
        'created_at' => 'datetime',
    ];

    /** Fields marked * on ERP-002 (person-providing-information section) are required. */
    public static function rules(): array
    {
        $text = 'nullable|string|max:5000';
        return [
            'informantLastName'           => 'required|string|max:255',
            'informantFirstName'          => 'required|string|max:255',
            'informantContactNumbers'     => 'required|array|min:1|max:3',
            'informantContactNumbers.*'   => 'required|string|max:50',
            'informantAddress'            => $text,
            'informantEmail'              => 'nullable|email|max:255',
            'informantRelationship'       => 'required|string|max:100',
            'informantIsRelativeOrMeeter' => 'required|boolean',
            'paxFlightDetails'            => 'required|string|max:5000',
            'informantLanguages'          => 'nullable|string|max:255',
            'informantContactedPax'       => 'required|boolean',
            'informantContactDetails'     => $text,
            'travellingWith'              => $text,
            'nokDetails'                  => $text,
            'hasMedicalProblem'           => 'nullable|boolean',
            'medicalDetails'              => $text,
            'otherInformation'            => $text,
            'changeReason'                => 'nullable|string|max:1000',
        ];
    }

    /** Same person if the name matches, ignoring case and spacing. */
    public function isSamePerson(?string $firstName, ?string $lastName): bool
    {
        $n = fn ($s) => preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $s)));
        return $n($this->informant_first_name) === $n($firstName) && $n($this->informant_last_name) === $n($lastName);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }
}

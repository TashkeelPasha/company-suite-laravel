<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP-001 Passenger Record Card — the Station / SAT / GO part: what is learnt from
 * the passenger after the incident, answered inside their status update. The
 * Company's part (name, DOB, passport…) lives on `passengers`. Append-only;
 * `submitted_by` is denormalised on purpose — see DEVELOPER_GUIDE §4.
 */
class PassengerRecordCard extends Model
{
    /** API camelCase key => DB column. */
    public const FIELDS = [
        'travellingWith'                  => 'travelling_with',
        'nokDetails'                      => 'nok_details',
        'relativesMeeters'                => 'relatives_meeters',
        'contactMade'                     => 'contact_made',
        'contactDetails'                  => 'contact_details',
        'localAddress'                    => 'local_address',
        'otherContacts'                   => 'other_contacts',
        'lifeThreatening'                 => 'life_threatening',
        'injuryDetails'                   => 'injury_details',
        'intentionLocalAddress'           => 'intention_local_address',
        'intentionLocalAddressDetails'    => 'intention_local_address_details',
        'intentionContinueJourney'        => 'intention_continue_journey',
        'intentionContinueJourneyDetails' => 'intention_continue_journey_details',
        'intentionSrc'                    => 'intention_src',
        'intentionOther'                  => 'intention_other',
        'familyAssistOffered'             => 'family_assist_offered',
        'familyAssistAccepted'            => 'family_assist_accepted',
        'familyAssistTeamAdvised'         => 'family_assist_team_advised',
        'speaksEnglish'                   => 'speaks_english',
        'primaryLanguage'                 => 'primary_language',
        'secondaryLanguage'               => 'secondary_language',
        'occupation'                      => 'occupation',
        'otherInformation'                => 'other_information',
    ];

    protected $table = 'passenger_record_cards';
    public $timestamps = false;

    // Writes only ever come from FIELDS (via ApiController::columnsFrom) plus the ids below.
    protected $guarded = ['id', 'created_at'];

    protected $casts = [
        'contact_made' => 'boolean',
        'life_threatening' => 'boolean',
        'intention_local_address' => 'boolean',
        'intention_continue_journey' => 'boolean',
        'intention_src' => 'boolean',
        'family_assist_offered' => 'boolean',
        'family_assist_accepted' => 'boolean',
        'family_assist_team_advised' => 'boolean',
        'speaks_english' => 'boolean',
        'created_at' => 'datetime',
    ];

    public const SOURCE_FORM = 'form';

    /**
     * Fields marked * on ERP-001 are required, unless $partial — a status update may
     * carry only the answers known so far; the rest come with later updates.
     */
    public static function rules(string $prefix = '', bool $partial = false): array
    {
        $text = 'nullable|string|max:5000';
        $bool = 'nullable|boolean';
        $rules = [
            'travellingWith'                  => $text,
            'nokDetails'                      => 'required|string|max:5000',
            'relativesMeeters'                => $text,
            'contactMade'                     => 'required|boolean',
            'contactDetails'                  => $text,
            'localAddress'                    => $text,
            'otherContacts'                   => $text,
            'lifeThreatening'                 => $bool,
            'injuryDetails'                   => $text,
            'intentionLocalAddress'           => $bool,
            'intentionLocalAddressDetails'    => $text,
            'intentionContinueJourney'        => $bool,
            'intentionContinueJourneyDetails' => $text,
            'intentionSrc'                    => $bool,
            'intentionOther'                  => $text,
            'familyAssistOffered'             => 'required|boolean',
            'familyAssistAccepted'            => $bool,
            'familyAssistTeamAdvised'         => $bool,
            'speaksEnglish'                   => $bool,
            'primaryLanguage'                 => 'nullable|string|max:100',
            'secondaryLanguage'               => 'nullable|string|max:100',
            'occupation'                      => 'nullable|string|max:255',
            'otherInformation'                => $text,
        ];
        $out = [];
        foreach ($rules as $key => $rule) $out[$prefix.$key] = $partial ? str_replace('required|', 'nullable|', $rule) : $rule;
        return $out;
    }

    /**
     * Save a team's (possibly partial) answers as the passenger's next PRC version.
     * `$answers` is API camelCase; keys left out keep the latest card's value, an explicit
     * null clears it. Returns null when nothing would change (or there is nothing to save).
     */
    public static function recordAnswers(Passenger $p, array $answers, TeamMember $by): ?self
    {
        $latest = $p->recordCards()->latest('id')->first();
        $sent = [];
        foreach (self::FIELDS as $key => $column) if (array_key_exists($key, $answers)) $sent[$column] = $answers[$key];

        $changed = collect($sent)->contains(fn ($v, $column) => !$latest || $latest->{$column} != $v || ($latest->{$column} === null) !== ($v === null));
        if (!$changed) return null;

        $merged = $sent;
        if ($latest) foreach (self::FIELDS as $column) if (!array_key_exists($column, $sent)) $merged[$column] = $latest->getRawOriginal($column);
        if (!array_filter($merged, fn ($v) => $v !== null && $v !== '')) return null;

        return self::create($merged + [
            'passenger_id' => $p->id, 'team_member_id' => $by->id,
            'submitted_by' => $by->full_name, 'source' => self::SOURCE_FORM,
        ]);
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

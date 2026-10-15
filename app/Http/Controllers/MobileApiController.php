<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Passenger;
use App\Models\PassengerInformationForm;
use App\Models\PassengerRecordCard;
use App\Models\PassengerUpdate;
use App\Models\RelativeInfoUpdate;
use App\Models\TeamMember;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MobileApiController extends BaseController
{
    // ── AUTH ─────────────────────────────────────────

    public function stationLogin(Request $request): JsonResponse
    { return $this->teamLogin($request, 'station', [TeamMember::TYPE_STATION, TeamMember::TYPE_SAT]); }

    public function goLogin(Request $request): JsonResponse
    { return $this->teamLogin($request, 'go', [TeamMember::TYPE_GO]); }

    public function ercLogin(Request $request): JsonResponse
    { return $this->teamLogin($request, 'erc', [TeamMember::TYPE_ERC]); }

    public function me(): JsonResponse
    {
        $m = $this->member();
        return response()->json($this->memberJson($m, true));
    }

    public function logout(): JsonResponse
    {
        $this->member()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }

    // ── STATION ──────────────────────────────────────

    public function stationPassengers(): JsonResponse
    { return response()->json($this->teamPassengerList()); }

    public function stationUpdates(int $id): JsonResponse
    {
        $p = $this->teamPassenger($id);
        return $p ? response()->json($p->updates()->latest('id')->get()->map(fn ($u) => $this->updateJson($u)))
                  : $this->error('Passenger not found', 404);
    }

    public function stationPassengerErp(int $id): JsonResponse
    {
        $p = $this->teamPassenger($id);
        return $p ? response()->json($this->dossierJson($p, true, false))
                  : $this->error('Passenger not found', 404);
    }

    public function stationIncidents(): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)->withCount('passengers')->latest('id')->get();
        return response()->json($i->map(fn ($incident) => $this->incidentJson($incident, true)));
    }

    public function stationIncident(int $id): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)->with('passengers')->find($id);
        return $i ? response()->json($this->incidentWithPassengers($i)) : $this->error('Incident not found', 404);
    }

    public function createStationUpdate(Request $r, int $id): JsonResponse
    { return $this->createStatusUpdate($r, $id); }

    // ── GO ───────────────────────────────────────────

    public function goIncidents(): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)->withCount('passengers')->latest('id')->get();
        return response()->json($i->map(fn ($incident) => $this->incidentJson($incident, true)));
    }

    public function goIncident(int $id): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)->with('passengers')->find($id);
        return $i ? response()->json($this->incidentWithPassengers($i)) : $this->error('Incident not found', 404);
    }

    public function goUpdates(int $id): JsonResponse
    {
        $p = $this->teamPassenger($id);
        return $p ? response()->json($p->updates()->latest('id')->get()->map(fn ($u) => $this->updateJson($u)))
                  : $this->error('Passenger not found', 404);
    }

    public function goPassengerErp(int $id): JsonResponse
    {
        $p = $this->teamPassenger($id);
        return $p ? response()->json($this->dossierJson($p, true, true))
                  : $this->error('Passenger not found', 404);
    }

    public function createGoUpdate(Request $r, int $id): JsonResponse
    { return $this->createStatusUpdate($r, $id); }

    // ── ERC ──────────────────────────────────────────

    public function ercIncidents(): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)
            ->where('status', Incident::STATUS_ONGOING)->withCount('passengers')->latest('id')->get();
        return response()->json($i->map(fn ($incident) => $this->incidentJson($incident, true)));
    }

    public function ercIncident(int $id): JsonResponse
    {
        $i = Incident::where('company_id', $this->member()->company_id)->with('passengers')->find($id);
        if (!$i) return $this->error('Incident not found', 404);
        if ($i->status === Incident::STATUS_COMPLETED) return $this->error('Completed incidents are not available to ERC', 403);
        return response()->json($this->incidentWithPassengers($i));
    }

    public function relativeInfo(int $id): JsonResponse
    {
        $p = $this->ercPassenger($id);
        return $p ? response()->json($p->relativeInfoUpdates()->latest('id')->get()->map(fn ($u) => $this->relativeJson($u)))
                  : $this->error('Passenger not found', 404);
    }

    public function createRelativeInfo(Request $r, int $id): JsonResponse
    {
        $p = $this->ercPassenger($id);
        if (!$p) return $this->error('Passenger not found', 404);
        $d = $this->validateData($r, [
            'contactName' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'telephoneNumbers' => 'required|string',
            'address' => 'nullable|string',
        ]);
        $m = $this->member();
        $u = RelativeInfoUpdate::create([
            'passenger_id' => $p->id,
            'team_member_id' => $m->id,
            'updated_by_name' => $m->full_name,
            'contact_name' => $d['contactName'],
            'relationship' => $d['relationship'],
            'telephone_numbers' => $d['telephoneNumbers'],
            'address' => $d['address'] ?? null,
        ]);
        return response()->json($this->relativeJson($u), 201);
    }

    public function ercUpdates(int $id): JsonResponse
    {
        $p = $this->ercPassenger($id);
        return $p ? response()->json($p->updates()->with('teamMember')->latest('id')->get()->map(fn ($u) => $this->updateJson($u, true)))
                  : $this->error('Passenger not found', 404);
    }

    // ── PRIVATE HELPERS ──────────────────────────────

    private function teamLogin(Request $request, string $role, array $types): JsonResponse
{
    $data = $this->validateData($request, ['email' => 'required|email', 'password' => 'required|string']);

    // Pehle email se user dhoondo (type filter ke bagair) taake invalid_credentials vs role_mismatch differentiate ho sake
    $user = TeamMember::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password_hash)) {
        return response()->json([
            'message' => 'Invalid credentials',
            'error_code' => 'invalid_credentials',
        ], 401);
    }

    // Credentials sahi hain, ab check karo ke ye user is role ke allowed types mein aata hai ya nahi
    if (!in_array($user->team_type, $types, true)) {
        return response()->json([
            'message' => 'You are not authorized for this role',
            'error_code' => 'role_mismatch',
        ], 401);
    }

    $token = $user->createToken('mobile')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $this->memberJson($user, true) + ['role' => $role],
    ]);
}

    private function member(): TeamMember
    { return Auth::guard('sanctum')->user(); }

    private function teamPassenger(int $id): ?Passenger
    { $m = $this->member(); return Passenger::where('id', $id)->where('company_id', $m->company_id)->first(); }

    private function ercPassenger(int $id): ?Passenger
    {
        $p = $this->teamPassenger($id);
        return $p && (!$p->incident_id || $p->incident()->where('status', Incident::STATUS_ONGOING)->exists()) ? $p : null;
    }

    private function teamPassengerList(): array
    {
        $m = $this->member();
        return Passenger::where('company_id', $m->company_id)->with(['updates', 'incident'])
            ->latest('id')->get()->map(fn ($p) => $this->passengerJson($p, true))->all();
    }

    private function createStatusUpdate(Request $r, int $id): JsonResponse
    {
        $p = $this->teamPassenger($id);
        if (!$p) return $this->error('Passenger not found', 404);
        $d = $this->validateData($r, ['status' => 'required|in:Safe,Injured,Critical,Deceased,Unconfirmed', 'remarks' => 'nullable|string', 'prc' => 'nullable|array']
            + PassengerRecordCard::rules('prc.', true));
        $m = $this->member();
        $card = null;
        $u = DB::transaction(function () use ($p, $d, $m, &$card) {
            $update = PassengerUpdate::create([
                'passenger_id' => $p->id,
                'team_member_id' => $m->id,
                'submitted_by' => $m->full_name,
                'status' => $d['status'],
                'remarks' => $d['remarks'] ?? null,
            ]);

            // Partial answers are merged onto the latest card, so other teams' answers are kept.
            if (!empty($d['prc'])) $card = PassengerRecordCard::recordAnswers($p, $d['prc'], $m);

            return $update;
        });

        return response()->json($this->updateJson($u) + [
            'recordCard' => $card ? $this->recordCardJson($card) : null,
        ], 201);
    }

    private function validateData(Request $r, array $rules): array
    {
        $v = Validator::make($r->all(), $rules);
        if ($v->fails()) abort(response()->json(['message' => $v->errors()->first()], 422));
        return $v->validated();
    }

    private function error(string $message, int $status = 400): JsonResponse
    { return response()->json(['message' => $message], $status); }

    private function dateValue($value): ?string
    { return $value ? $value->toISOString() : null; }

    private function memberJson($m, bool $withCompany = false): array
    {
        $r = ['id' => $m->id, 'fullName' => $m->full_name, 'email' => $m->email, 'companyId' => $m->company_id, 'teamType' => $m->team_type, 'createdAt' => $this->dateValue($m->created_at)];
        if ($withCompany) { $r['name'] = $m->full_name; $r['companyName'] = $m->company?->name; }
        return $r;
    }

    private function incidentJson($i, bool $count = false): array
    {
        $r = ['id' => $i->id, 'companyId' => $i->company_id, 'flightNumber' => $i->flight_number, 'fromLocation' => $i->from_location, 'toLocation' => $i->to_location, 'via' => $i->via, 'otherDetails' => $i->other_details, 'incidentDate' => $i->incident_date?->format('Y-m-d'), 'incidentTime' => $i->incident_time, 'status' => $i->status, 'createdAt' => $this->dateValue($i->created_at)];
        if ($count) $r['passengerCount'] = $i->passengers_count;
        return $r;
    }

    private function passengerJson($p, bool $latest = false): array
    {
        $flightNumber = $p->flight_number ?? $p->incident?->flight_number;
        $r = ['id' => $p->id, 'companyId' => $p->company_id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic, 'flightNumber' => $flightNumber, 'fromLocation' => $p->from_location, 'toLocation' => $p->to_location, 'departureTime' => $this->dateValue($p->departure_time), 'arrivalTime' => $this->dateValue($p->arrival_time), 'createdAt' => $this->dateValue($p->created_at)];
        if ($latest) {
            $updates = $p->relationLoaded('updates') ? $p->updates : $p->updates()->with('teamMember')->get();
            $r['latestStatus'] = $updates->sortByDesc('id')->first()?->status;
            $r['latestStatusAt'] = $this->dateValue($updates->sortByDesc('id')->first()?->created_at);
        }
        return $r;
    }

    private function incidentWithPassengers($i): array
    {
        $i->passengers->loadCount('recordCards');
        $r = $this->incidentJson($i);
        $r['passengers'] = $i->passengers->map(fn ($p) => $this->incidentPassengerJson($p))->all();
        return $r;
    }

    private function incidentPassengerJson($p): array
    {
        $updates = $p->updates()->with('teamMember')->latest('id')->get();
        $latest = fn ($type = null) => $updates->first(fn ($u) => !$type || $u->teamMember?->team_type === $type)?->status;
        return [
            'id' => $p->id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic,
            'prcCount' => $p->record_cards_count,
            'latestStatus' => $latest(), 'latestGoStatus' => $latest(TeamMember::TYPE_GO),
            'latestStationStatus' => $latest(TeamMember::TYPE_STATION), 'latestSatStatus' => $latest(TeamMember::TYPE_SAT),
            'latestRelativeInfo' => $p->relativeInfoUpdates()->latest('id')->first() ? $this->relativeJson($p->relativeInfoUpdates()->latest('id')->first()) : null,
            'createdAt' => $this->dateValue($p->created_at),
        ];
    }

    private function updateJson($u, bool $withTeam = false): array
    {
        $r = ['id' => $u->id, 'passengerId' => $u->passenger_id, 'teamMemberId' => $u->team_member_id, 'submittedBy' => $u->submitted_by, 'status' => $u->status, 'remarks' => $u->remarks, 'createdAt' => $this->dateValue($u->created_at)];
        if ($withTeam) $r['teamType'] = $u->teamMember?->team_type;
        return $r;
    }

    private function relativeJson($u): array
    { return ['id' => $u->id, 'passengerId' => $u->passenger_id, 'teamMemberId' => $u->team_member_id, 'updatedByName' => $u->updated_by_name, 'contactName' => $u->contact_name, 'relationship' => $u->relationship, 'telephoneNumbers' => $u->telephone_numbers, 'address' => $u->address, 'createdAt' => $this->dateValue($u->created_at)]; }

    private function dossierJson(Passenger $p, bool $withPrc, bool $withPif): array
    {
        $incident = $p->incident;
        $party = $p->incident_id && $p->pnr
            ? Passenger::where('incident_id', $p->incident_id)->where('pnr', $p->pnr)->where('id', '!=', $p->id)->orderBy('id')->get()
            : collect();
        $forms = $p->informationForms()->latest('id')->get();
        $contact = $forms->first();

        return [
            'incident' => $incident ? $this->incidentJson($incident) : null,
            'passenger' => $this->passengerDetailJson($p, $incident, $party),
            'updates' => $p->updates()->with('teamMember')->latest('id')->get()->map(fn ($u) => $this->updateJson($u, true))->all(),
            'contactPerson' => $contact ? [
                'name' => trim($contact->informant_first_name.' '.$contact->informant_last_name),
                'relationship' => $contact->informant_relationship,
                'contactNumbers' => $contact->informant_contact_numbers ?? [],
                'email' => $contact->informant_email,
                'updatedBy' => $contact->submitted_by,
                'updatedAt' => $this->dateValue($contact->created_at),
            ] : null,
            'recordCards' => $withPrc ? $p->recordCards()->latest('id')->get()->map(fn ($card) => $this->recordCardJson($card))->all() : [],
            'informationForms' => $withPif ? $forms->map(fn ($form) => $this->informationFormJson($form))->all() : [],
        ];
    }

    private function passengerDetailJson(Passenger $p, ?Incident $incident, $travellingWith): array
    {
        $fields = [];
        foreach (Passenger::MANIFEST_FIELDS as $key => $column) {
            $value = $p->{$column};
            $fields[$key] = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        }

        $ownRoute = $p->route_from || $p->route_to;

        return ['id' => $p->id, 'incidentId' => $p->incident_id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic]
            + $fields
            + [
                'age' => $p->ageAt($incident?->incident_date),
                'effectiveRoute' => $ownRoute
                    ? ['from' => $p->route_from ?? $incident?->from_location, 'to' => $p->route_to ?? $incident?->to_location, 'via' => $p->route_via]
                    : ['from' => $incident?->from_location ?? $p->from_location, 'to' => $incident?->to_location ?? $p->to_location, 'via' => $p->route_via ?? $incident?->via],
                'travellingWith' => $travellingWith->map(fn ($other) => [
                    'id' => $other->id,
                    'name' => $other->name,
                    'seatNumber' => $other->seat_number,
                    'paxType' => $other->pax_type,
                    'dateOfBirth' => $other->date_of_birth?->format('Y-m-d'),
                    'nationality' => $other->nationality,
                ])->values()->all(),
            ];
    }

    private function recordCardJson(PassengerRecordCard $card): array
    {
        return [
            'id' => $card->id,
            'passengerId' => $card->passenger_id,
            'teamMemberId' => $card->team_member_id,
            'submittedBy' => $card->submitted_by,
            'source' => $card->source,
        ] + $this->fieldsJson($card, PassengerRecordCard::FIELDS) + [
            'createdAt' => $this->dateValue($card->created_at),
        ];
    }

    private function informationFormJson(PassengerInformationForm $form): array
    {
        return [
            'id' => $form->id,
            'passengerId' => $form->passenger_id,
            'teamMemberId' => $form->team_member_id,
            'submittedBy' => $form->submitted_by,
            'changeReason' => $form->change_reason,
        ] + $this->fieldsJson($form, PassengerInformationForm::FIELDS) + [
            'createdAt' => $this->dateValue($form->created_at),
        ];
    }

    private function fieldsJson($model, array $map): array
    {
        $fields = [];
        foreach ($map as $key => $column) {
            $value = $model->{$column};
            $fields[$key] = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        }
        return $fields;
    }
}
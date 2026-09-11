<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Passenger;
use App\Models\PassengerUpdate;
use App\Models\RelativeInfoUpdate;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
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
        $user = TeamMember::where('email', $data['email'])->whereIn('team_type', $types)->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
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
        $d = $this->validateData($r, ['status' => 'required|in:Safe,Injured,Critical,Deceased,Unconfirmed', 'remarks' => 'nullable|string']);
        $m = $this->member();
        $u = PassengerUpdate::create([
            'passenger_id' => $p->id,
            'team_member_id' => $m->id,
            'submitted_by' => $m->full_name,
            'status' => $d['status'],
            'remarks' => $d['remarks'] ?? null,
        ]);
        return response()->json($this->updateJson($u), 201);
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
        $r = ['id' => $i->id, 'companyId' => $i->company_id, 'flightNumber' => $i->flight_number, 'fromLocation' => $i->from_location, 'toLocation' => $i->to_location, 'incidentDate' => $i->incident_date?->format('Y-m-d'), 'incidentTime' => $i->incident_time, 'status' => $i->status, 'createdAt' => $this->dateValue($i->created_at)];
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
}
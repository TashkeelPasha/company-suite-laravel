<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Incident;
use App\Models\Passenger;
use App\Models\PassengerInformationForm;
use App\Models\PassengerRecordCard;
use App\Models\PassengerUpdate;
use App\Models\RelativeInfoUpdate;
use App\Models\SuperAdmin;
use App\Models\TeamMember;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    public function health(): JsonResponse { return response()->json(['status' => 'ok']); }

    public function superadminLogin(Request $request): JsonResponse
    {
        $data = $this->validateData($request, ['email' => 'required|email', 'password' => 'required|string']);
        $user = SuperAdmin::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password_hash)) return $this->error('Invalid credentials', 401);
        Auth::guard('superadmin')->login($user, true); $request->session()->regenerate();
        return response()->json($this->superadminJson($user));
    }

    public function superadminLogout(Request $request): JsonResponse
    { Auth::guard('superadmin')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return response()->json(null, 204); }

    public function superadminMe(): JsonResponse
    { return response()->json($this->superadminJson(Auth::guard('superadmin')->user())); }

    public function whoami(): JsonResponse
    {
        foreach ([['superadmin', 'superadmin'], ['company', 'company'], ['station', 'team_member'], ['go', 'team_member'], ['erc', 'team_member']] as [$guard, $role]) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                return response()->json($role === 'superadmin' ? $this->superadminJson($user) : ($role === 'company' ? $this->companyJson($user) : $this->memberJson($user, true)) + ['role' => $role]);
            }
        }
        return $this->error('Not authenticated', 401);
    }

    public function changeSuperadminPassword(Request $request): JsonResponse
    {
        $data = $this->validateData($request, ['currentPassword' => 'required|string', 'newPassword' => 'required|string|min:8']);
        $user = Auth::guard('superadmin')->user();
        if (!Hash::check($data['currentPassword'], $user->password_hash)) return $this->error('Current password is incorrect');
        $user->password_hash = Hash::make($data['newPassword']); $user->save();
        return response()->json($this->superadminJson($user));
    }

    public function companies(): JsonResponse { return response()->json(Company::latest('id')->get()->map(fn ($c) => $this->companyJson($c))); }
    public function companyStats(): JsonResponse { return response()->json(['totalCompanies' => Company::count()]); }
    public function company(int $id): JsonResponse { $c = Company::find($id); return $c ? response()->json($this->companyJson($c)) : $this->error('Company not found', 404); }

    public function createCompany(Request $request): JsonResponse
    {
        $data = $this->validateData($request, ['name' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'required|string|min:8', 'logoUrl' => 'nullable|string|max:255']);
        if (Company::where('email', $data['email'])->exists()) return $this->error('Email already exists', 409);
        $c = Company::create(['name' => $data['name'], 'email' => $data['email'], 'password_hash' => Hash::make($data['password']), 'logo_url' => $data['logoUrl'] ?? null]);
        return response()->json($this->companyJson($c), 201);
    }

    public function updateCompany(Request $request, int $id): JsonResponse
    {
        $c = Company::find($id); if (!$c) return $this->error('Company not found', 404);
        $data = $this->validateData($request, ['name' => 'sometimes|required|string|max:255', 'email' => 'sometimes|required|email|max:255', 'logoUrl' => 'nullable|string|max:255']);
        if (isset($data['email']) && Company::where('email', $data['email'])->where('id', '!=', $id)->exists()) return $this->error('Email already exists', 409);
        $c->fill(['name' => $data['name'] ?? $c->name, 'email' => $data['email'] ?? $c->email, 'logo_url' => array_key_exists('logoUrl', $data) ? $data['logoUrl'] : $c->logo_url]); $c->save();
        return response()->json($this->companyJson($c));
    }

    public function deleteCompany(int $id): JsonResponse
    { $c = Company::find($id); if (!$c) return $this->error('Company not found', 404); $c->delete(); return response()->json(null, 204); }
    public function resetCompanyPassword(Request $request, int $id): JsonResponse
    { $c = Company::find($id); if (!$c) return $this->error('Company not found', 404); $d = $this->validateData($request, ['newPassword' => 'required|string|min:8']); $c->password_hash = Hash::make($d['newPassword']); $c->save(); return response()->json($this->companyJson($c)); }

    public function companyLogin(Request $request): JsonResponse { return $this->memberLogin($request, 'company', fn ($u) => $this->companyJson($u)); }
    public function companyLogout(Request $request): JsonResponse { return $this->logoutGuard($request, 'company'); }
    public function companyMe(): JsonResponse { return response()->json($this->companyJson(Auth::guard('company')->user())); }

    public function teamMembers(): JsonResponse
    { return response()->json(TeamMember::where('company_id', $this->companyId())->latest('id')->get()->map(fn ($m) => $this->memberJson($m))); }

    public function createTeamMember(Request $request): JsonResponse
    {
        $d = $this->validateData($request, ['fullName' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'required|string|min:8', 'teamType' => 'required|string|in:'.implode(',', TeamMember::ALL_TYPES)]);
        if (TeamMember::where('email', $d['email'])->exists()) return $this->error('Email already exists', 409);
        $m = TeamMember::create(['company_id' => $this->companyId(), 'full_name' => $d['fullName'], 'email' => $d['email'], 'password_hash' => Hash::make($d['password']), 'team_type' => $d['teamType']]);
        return response()->json($this->memberJson($m), 201);
    }

    public function updateTeamMember(Request $request, int $id): JsonResponse
    {
        $m = TeamMember::where('company_id', $this->companyId())->find($id); if (!$m) return $this->error('Team member not found', 404);
        $d = $this->validateData($request, ['fullName' => 'sometimes|required|string|max:255', 'email' => 'sometimes|required|email|max:255', 'password' => 'nullable|string|min:8', 'teamType' => 'sometimes|required|string|in:'.implode(',', TeamMember::ALL_TYPES)]);
        if (isset($d['email']) && TeamMember::where('email', $d['email'])->where('id', '!=', $id)->exists()) return $this->error('Email already exists', 409);
        $m->fill(['full_name' => $d['fullName'] ?? $m->full_name, 'email' => $d['email'] ?? $m->email, 'team_type' => $d['teamType'] ?? $m->team_type]); if (!empty($d['password'])) $m->password_hash = Hash::make($d['password']); $m->save();
        return response()->json($this->memberJson($m));
    }

    public function deleteTeamMember(int $id): JsonResponse { $m = TeamMember::where('company_id', $this->companyId())->find($id); if (!$m) return $this->error('Team member not found', 404); $m->delete(); return response()->json(null, 204); }

    public function legacyPassengers(): JsonResponse { return response()->json(Passenger::where('company_id', $this->companyId())->whereNull('incident_id')->latest('id')->get()->map(fn ($p) => $this->passengerJson($p))); }
    public function createLegacyPassenger(Request $request): JsonResponse
    { $d = $this->validateData($request, ['name' => 'required|string|max:255', 'seatNumber' => 'required|string|max:50', 'cnic' => 'nullable|string|max:100', 'flightNumber' => 'nullable|string|max:100', 'fromLocation' => 'nullable|string|max:255', 'toLocation' => 'nullable|string|max:255', 'departureTime' => 'nullable|date', 'arrivalTime' => 'nullable|date']); $p = Passenger::create($this->passengerFields($d) + ['company_id' => $this->companyId()]); return response()->json($this->passengerJson($p), 201); }
    public function deletePassenger(int $id): JsonResponse { $p = Passenger::where('company_id', $this->companyId())->find($id); if (!$p) return $this->error('Passenger not found', 404); $p->delete(); return response()->json(null, 204); }

    public function companyIncidents(): JsonResponse { return response()->json(Incident::where('company_id', $this->companyId())->withCount('passengers')->latest('id')->get()->map(fn ($i) => $this->incidentJson($i, true))); }
    public function createIncident(Request $request): JsonResponse
    { $d = $this->validateData($request, ['flightNumber' => 'required|string|max:100', 'fromLocation' => 'required|string|max:255', 'toLocation' => 'required|string|max:255', 'via' => 'nullable|string|max:255', 'otherDetails' => 'nullable|string|max:5000', 'incidentDate' => 'required|date_format:Y-m-d', 'incidentTime' => 'required|date_format:H:i']); $i = Incident::create(['company_id' => $this->companyId(), 'flight_number' => $d['flightNumber'], 'from_location' => $d['fromLocation'], 'to_location' => $d['toLocation'], 'via' => $d['via'] ?? null, 'other_details' => $d['otherDetails'] ?? null, 'incident_date' => $d['incidentDate'], 'incident_time' => $d['incidentTime'], 'status' => Incident::STATUS_ONGOING]); return response()->json($this->incidentJson($i), 201); }
    public function companyIncident(int $id): JsonResponse { $i = Incident::where('company_id', $this->companyId())->with('passengers')->find($id); return $i ? response()->json($this->incidentWithPassengers($i)) : $this->error('Incident not found', 404); }
    public function updateIncidentStatus(Request $request, int $id): JsonResponse { $i = Incident::where('company_id', $this->companyId())->find($id); if (!$i) return $this->error('Incident not found', 404); $d = $this->validateData($request, ['status' => 'required|in:Ongoing,Operation Completed']); $i->status = $d['status']; $i->save(); return response()->json($this->incidentJson($i)); }
    public function bulkPassengers(Request $request, int $id): JsonResponse
    {
        $i = Incident::where('company_id', $this->companyId())->find($id); if (!$i) return $this->error('Incident not found', 404);
        // Either `name` or the split first/last name. Infants (paxType I) sit on a lap, so no seat.
        $d = $this->validateData($request, [
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'nullable|string|max:255|required_without_all:passengers.*.firstName,passengers.*.lastName',
            'passengers.*.seatNumber' => 'nullable|string|max:50|required_unless:passengers.*.paxType,I',
            'passengers.*.cnic' => 'nullable|string|max:100',
        ] + Passenger::manifestRules('passengers.*.'));
        // validated() builds the array rule-by-rule, so rows can come back out of order — restore manifest order.
        ksort($d['passengers']);
        DB::transaction(function () use ($d, $i) {
            foreach ($d['passengers'] as $row) {
                Passenger::create($this->manifestColumns($row) + [
                    'company_id' => $i->company_id, 'incident_id' => $i->id,
                    'name' => Passenger::displayName($row['firstName'] ?? null, $row['lastName'] ?? null, $row['name'] ?? null),
                    'seat_number' => $row['seatNumber'] ?? '', 'cnic' => $row['cnic'] ?? null,
                ]);
            }
        });
        return response()->json(['count' => count($d['passengers'])], 201);
    }
    public function companyManifest(int $id): JsonResponse { $i = Incident::where('company_id', $this->companyId())->find($id); return $i ? response()->json($this->manifestJson($i)) : $this->error('Incident not found', 404); }
    public function companyErp(int $id): JsonResponse { $p = Passenger::where('company_id', $this->companyId())->find($id); return $p ? response()->json($this->dossierJson($p, true, true)) : $this->error('Passenger not found', 404); }

    /** The Company's part of ERP-001: correct / complete the manifest data (same fields as the Excel). Only keys sent are changed. */
    public function updateCompanyPassenger(Request $r, int $id): JsonResponse
    {
        $p = Passenger::where('company_id', $this->companyId())->find($id); if (!$p) return $this->error('Passenger not found', 404);
        $d = $this->validateData($r, ['seatNumber' => 'nullable|string|max:50', 'cnic' => 'nullable|string|max:100'] + Passenger::manifestRules());
        $p->fill($this->manifestColumns($d));
        if (array_key_exists('seatNumber', $d)) $p->seat_number = $d['seatNumber'] ?? '';
        if (array_key_exists('cnic', $d)) $p->cnic = $d['cnic'];
        $p->name = Passenger::displayName($p->first_name, $p->last_name, $p->name);
        $p->save();
        return response()->json($this->passengerDetailJson($p, $p->incident, collect()));
    }

    public function stationLogin(Request $request): JsonResponse { return $this->teamLogin($request, 'station', [TeamMember::TYPE_STATION, TeamMember::TYPE_SAT]); }
    public function goLogin(Request $request): JsonResponse { return $this->teamLogin($request, 'go', [TeamMember::TYPE_GO]); }
    public function ercLogin(Request $request): JsonResponse { return $this->teamLogin($request, 'erc', [TeamMember::TYPE_ERC]); }
    public function stationLogout(Request $r): JsonResponse { return $this->logoutGuard($r, 'station'); }
    public function goLogout(Request $r): JsonResponse { return $this->logoutGuard($r, 'go'); }
    public function ercLogout(Request $r): JsonResponse { return $this->logoutGuard($r, 'erc'); }
    public function stationMe(): JsonResponse { return response()->json($this->memberJson(Auth::guard('station')->user(), true)); }
    public function goMe(): JsonResponse { return response()->json($this->memberJson(Auth::guard('go')->user(), true)); }
    public function ercMe(): JsonResponse { return response()->json($this->memberJson(Auth::guard('erc')->user(), true)); }

    public function stationPassengers(): JsonResponse { return response()->json($this->teamPassengerList(['Station Team', 'SAT - Volunteers'])); }
    public function stationUpdates(int $id): JsonResponse { $p = $this->teamPassenger($id, 'station'); return $p ? response()->json($p->updates()->latest('id')->get()->map(fn ($u) => $this->updateJson($u))) : $this->error('Passenger not found', 404); }
    public function createStationUpdate(Request $r, int $id): JsonResponse { return $this->createStatusUpdate($r, $id, 'station'); }
    public function stationErp(int $id): JsonResponse { $p = $this->teamPassenger($id, 'station'); return $p ? response()->json($this->dossierJson($p, true, false)) : $this->error('Passenger not found', 404); }

    public function goIncidents(): JsonResponse { $i = Incident::where('company_id', $this->teamMember()->company_id)->withCount('passengers')->latest('id')->get(); return response()->json($i->map(fn ($incident) => $this->incidentJson($incident, true))); }
    public function goIncident(int $id): JsonResponse { $i = Incident::where('company_id', $this->teamMember()->company_id)->with('passengers')->find($id); return $i ? response()->json($this->incidentWithPassengers($i)) : $this->error('Incident not found', 404); }
    public function goUpdates(int $id): JsonResponse { $p = $this->teamPassenger($id, 'go'); return $p ? response()->json($p->updates()->latest('id')->get()->map(fn ($u) => $this->updateJson($u))) : $this->error('Passenger not found', 404); }
    public function createGoUpdate(Request $r, int $id): JsonResponse { return $this->createStatusUpdate($r, $id, 'go'); }
    public function goManifest(int $id): JsonResponse { $i = Incident::where('company_id', $this->teamMember()->company_id)->find($id); return $i ? response()->json($this->manifestJson($i)) : $this->error('Incident not found', 404); }
    public function goErp(int $id): JsonResponse { $p = $this->teamPassenger($id, 'go'); return $p ? response()->json($this->dossierJson($p, true, true)) : $this->error('Passenger not found', 404); }
    public function ercIncidents(): JsonResponse { $i = Incident::where('company_id', $this->teamMember()->company_id)->where('status', Incident::STATUS_ONGOING)->withCount('passengers')->latest('id')->get(); return response()->json($i->map(fn ($incident) => $this->incidentJson($incident, true))); }
    public function ercIncident(int $id): JsonResponse { $i = Incident::where('company_id', $this->teamMember()->company_id)->with('passengers')->find($id); if (!$i) return $this->error('Incident not found', 404); if ($i->status === Incident::STATUS_COMPLETED) return $this->error('Completed incidents are not available to ERC', 403); return response()->json($this->incidentWithPassengers($i)); }
    public function relativeInfo(int $id): JsonResponse { $p = $this->ercPassenger($id); return $p ? response()->json($p->relativeInfoUpdates()->latest('id')->get()->map(fn ($u) => $this->relativeJson($u))) : $this->error('Passenger not found', 404); }
    public function createRelativeInfo(Request $r, int $id): JsonResponse { $p = $this->ercPassenger($id); if (!$p) return $this->error('Passenger not found', 404); $d = $this->validateData($r, ['contactName' => 'required|string|max:255', 'relationship' => 'required|string|max:255', 'telephoneNumbers' => 'required|string', 'address' => 'nullable|string']); $m = Auth::guard('erc')->user(); $u = RelativeInfoUpdate::create(['passenger_id' => $p->id, 'team_member_id' => $m->id, 'updated_by_name' => $m->full_name, 'contact_name' => $d['contactName'], 'relationship' => $d['relationship'], 'telephone_numbers' => $d['telephoneNumbers'], 'address' => $d['address'] ?? null]); return response()->json($this->relativeJson($u), 201); }
    public function ercErp(int $id): JsonResponse { $p = $this->ercPassenger($id); return $p ? response()->json($this->dossierJson($p, true, true)) : $this->error('Passenger not found', 404); }

    /**
     * ERP-002 — sets or edits the passenger's single contact person (each save is a new version).
     * Replacing the person needs `changeReason`. Same completed-incident gate as relative-info.
     */
    public function createErcPif(Request $r, int $id): JsonResponse
    {
        $p = $this->ercPassenger($id); if (!$p) return $this->error('Passenger not found', 404);
        $d = $this->validateData($r, PassengerInformationForm::rules());
        $current = $p->informationForms()->latest('id')->first();
        $reason = trim((string) ($d['changeReason'] ?? '')) ?: null;
        if ($current && !$current->isSamePerson($d['informantFirstName'], $d['informantLastName']) && !$reason) {
            return $this->error('This passenger already has a contact person. Give a reason to replace them.', 422);
        }
        $m = Auth::guard('erc')->user();
        $form = PassengerInformationForm::create($this->columnsFrom($d, PassengerInformationForm::FIELDS) + ['passenger_id' => $p->id, 'team_member_id' => $m->id, 'submitted_by' => $m->full_name, 'change_reason' => $current ? $reason : null]);
        return response()->json($this->informationFormJson($form), 201);
    }
    public function ercUpdates(int $id): JsonResponse { $p = $this->ercPassenger($id); return $p ? response()->json($p->updates()->with('teamMember')->latest('id')->get()->map(fn ($u) => $this->updateJson($u, true))) : $this->error('Passenger not found', 404); }

    public function upload(Request $request): JsonResponse
    { if (!$request->hasFile('file') || !$request->file('file')->isValid()) return $this->error('A valid image file is required'); $file = $request->file('file'); if (!str_starts_with((string) $file->getMimeType(), 'image/') || $file->getSize() > 2 * 1024 * 1024) return $this->error('Only images up to 2MB are allowed'); $name = $file->hashName(); $file->storeAs('', $name, 'public'); return response()->json(['url' => '/api/uploads/'.$name]); }
    public function serveUpload(string $filename) { $path = storage_path('app/public/'.$filename); if (!is_file($path)) abort(404); return response()->file($path); }

    private function memberLogin(Request $r, string $guard, callable $json): JsonResponse { $d = $this->validateData($r, ['email' => 'required|email', 'password' => 'required|string']); $u = Company::where('email', $d['email'])->first(); if (!$u || !Hash::check($d['password'], $u->password_hash)) return $this->error('Invalid credentials', 401); Auth::guard($guard)->login($u, true); $r->session()->regenerate(); return response()->json($json($u)); }
    private function teamLogin(Request $r, string $guard, array $types): JsonResponse { $d = $this->validateData($r, ['email' => 'required|email', 'password' => 'required|string']); $u = TeamMember::where('email', $d['email'])->whereIn('team_type', $types)->first(); if (!$u || !Hash::check($d['password'], $u->password_hash)) return $this->error('Invalid credentials', 401); Auth::guard($guard)->login($u, true); $r->session()->regenerate(); return response()->json($this->memberJson($u, true)); }
    private function logoutGuard(Request $r, string $guard): JsonResponse { Auth::guard($guard)->logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return response()->json(null, 204); }
    private function companyId(): int { return (int) Auth::guard('company')->id(); }
    private function teamMember(): TeamMember { foreach (['station', 'go', 'erc'] as $g) if (Auth::guard($g)->check()) return Auth::guard($g)->user(); abort(401); }
    private function teamPassenger(int $id, string $guard): ?Passenger { $m = Auth::guard($guard)->user(); return Passenger::where('id', $id)->where('company_id', $m->company_id)->first(); }
    private function ercPassenger(int $id): ?Passenger { $p = $this->teamPassenger($id, 'erc'); return $p && (!$p->incident_id || $p->incident()->where('status', Incident::STATUS_ONGOING)->exists()) ? $p : null; }
    private function teamPassengerList(array $types): array { $m = $this->teamMember(); return Passenger::where('company_id', $m->company_id)->with(['updates', 'incident'])->latest('id')->get()->map(fn ($p) => $this->passengerJson($p, true, $types))->all(); }
    /**
     * Station / SAT / GO status update. `prc` (optional) carries their ERP-001 answers asked in the same
     * window — saved as a new PRC version (partial answers allowed), unless nothing changed.
     * Manifest data (name, DOB, passport…) is the Company's and is not changed here.
     */
    private function createStatusUpdate(Request $r, int $id, string $guard): JsonResponse
    {
        $p = $this->teamPassenger($id, $guard); if (!$p) return $this->error('Passenger not found', 404);
        $d = $this->validateData($r, ['status' => 'required|in:Safe,Injured,Critical,Deceased,Unconfirmed', 'remarks' => 'nullable|string', 'prc' => 'nullable|array']
            + PassengerRecordCard::rules('prc.', true));
        $m = Auth::guard($guard)->user();
        $card = null;
        $u = DB::transaction(function () use ($p, $d, $m, &$card) {
            $u = PassengerUpdate::create(['passenger_id' => $p->id, 'team_member_id' => $m->id, 'submitted_by' => $m->full_name, 'status' => $d['status'], 'remarks' => $d['remarks'] ?? null]);
            // Partial answers are merged onto the latest card, so other teams' answers are kept.
            if (!empty($d['prc'])) $card = PassengerRecordCard::recordAnswers($p, $d['prc'], $m);
            return $u;
        });
        return response()->json($this->updateJson($u) + ['recordCard' => $card ? $this->recordCardJson($card) : null], 201);
    }

    private function validateData(Request $r, array $rules): array { $v = Validator::make($r->all(), $rules); if ($v->fails()) abort(response()->json(['error' => $v->errors()->first()], 400)); return $v->validated(); }
    private function error(string $message, int $status = 400): JsonResponse { return response()->json(['error' => $message], $status); }
    private function dateValue($value): ?string { return $value ? $value->toISOString() : null; }
    private function superadminJson($u): array { return ['id' => $u->id, 'email' => $u->email, 'role' => 'superadmin']; }
    private function companyJson($c): array { return ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'logoUrl' => $c->logo_url, 'createdAt' => $this->dateValue($c->created_at)]; }
    private function memberJson($m, bool $company = false): array { $r = ['id' => $m->id, 'fullName' => $m->full_name, 'email' => $m->email, 'companyId' => $m->company_id, 'teamType' => $m->team_type, 'createdAt' => $this->dateValue($m->created_at)]; if ($company) { $r['name'] = $m->full_name; $r['companyName'] = $m->company?->name; } return $r; }
    private function incidentJson($i, bool $count = false): array { $r = ['id' => $i->id, 'companyId' => $i->company_id, 'flightNumber' => $i->flight_number, 'fromLocation' => $i->from_location, 'toLocation' => $i->to_location, 'via' => $i->via, 'otherDetails' => $i->other_details, 'incidentDate' => $i->incident_date?->format('Y-m-d'), 'incidentTime' => $i->incident_time, 'status' => $i->status, 'createdAt' => $this->dateValue($i->created_at)]; if ($count) $r['passengerCount'] = $i->passengers_count; return $r; }
    private function passengerJson($p, bool $latest = false, array $types = []): array { $flightNumber = $p->flight_number ?? $p->incident?->flight_number; $r = ['id' => $p->id, 'companyId' => $p->company_id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic, 'flightNumber' => $flightNumber, 'fromLocation' => $p->from_location, 'toLocation' => $p->to_location, 'departureTime' => $this->dateValue($p->departure_time), 'arrivalTime' => $this->dateValue($p->arrival_time), 'createdAt' => $this->dateValue($p->created_at)]; if ($latest) { $updates = $p->relationLoaded('updates') ? $p->updates : $p->updates()->with('teamMember')->get(); $r['latestStatus'] = $updates->sortByDesc('id')->first()?->status; $r['latestStatusAt'] = $this->dateValue($updates->sortByDesc('id')->first()?->created_at); } return $r; }
    private function incidentWithPassengers($i): array { $i->passengers->loadCount(['recordCards', 'informationForms']); $r = $this->incidentJson($i); $r['passengers'] = $i->passengers->map(fn ($p) => $this->incidentPassengerJson($p))->all(); return $r; }
    private function incidentPassengerJson($p): array { $updates = $p->updates()->with('teamMember')->latest('id')->get(); $latest = fn ($type = null) => $updates->first(fn ($u) => !$type || $u->teamMember?->team_type === $type)?->status; $pif = $p->informationForms()->latest('id')->first(); return ['id' => $p->id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic, 'paxType' => $p->pax_type, 'prcCount' => $p->record_cards_count ?? $p->recordCards()->count(), 'pifCount' => $p->information_forms_count ?? $p->informationForms()->count(), 'latestPif' => $pif ? ['id' => $pif->id, 'informantName' => trim($pif->informant_first_name.' '.$pif->informant_last_name), 'informantRelationship' => $pif->informant_relationship, 'contactNumbers' => $pif->informant_contact_numbers ?? [], 'createdAt' => $this->dateValue($pif->created_at)] : null, 'latestStatus' => $latest(), 'latestGoStatus' => $latest(TeamMember::TYPE_GO), 'latestStationStatus' => $latest(TeamMember::TYPE_STATION), 'latestSatStatus' => $latest(TeamMember::TYPE_SAT), 'latestRelativeInfo' => $p->relativeInfoUpdates()->latest('id')->first() ? $this->relativeJson($p->relativeInfoUpdates()->latest('id')->first()) : null, 'createdAt' => $this->dateValue($p->created_at)]; }
    private function updateJson($u, bool $withTeam = false): array { $r = ['id' => $u->id, 'passengerId' => $u->passenger_id, 'teamMemberId' => $u->team_member_id, 'submittedBy' => $u->submitted_by, 'status' => $u->status, 'remarks' => $u->remarks, 'createdAt' => $this->dateValue($u->created_at)]; if ($withTeam) $r['teamType'] = $u->teamMember?->team_type; return $r; }
    private function relativeJson($u): array { return ['id' => $u->id, 'passengerId' => $u->passenger_id, 'teamMemberId' => $u->team_member_id, 'updatedByName' => $u->updated_by_name, 'contactName' => $u->contact_name, 'relationship' => $u->relationship, 'telephoneNumbers' => $u->telephone_numbers, 'address' => $u->address, 'createdAt' => $this->dateValue($u->created_at)]; }
    /* ERP-001 / ERP-002 ------------------------------------------------------ */

    /** API camelCase keys present in $data => DB columns, via a model's field map. */
    private function columnsFrom(array $data, array $map): array { $out = []; foreach ($map as $key => $column) if (array_key_exists($key, $data)) $out[$column] = $data[$key]; return $out; }
    /** DB columns => API camelCase; dates as "YYYY-MM-DD". */
    private function fieldsJson($model, array $map): array { $out = []; foreach ($map as $key => $column) { $v = $model->{$column}; $out[$key] = $v instanceof DateTimeInterface ? $v->format('Y-m-d') : $v; } return $out; }
    private function manifestColumns(array $data): array
    {
        $cols = $this->columnsFrom($data, Passenger::MANIFEST_FIELDS);
        if (array_key_exists('contact_numbers', $cols)) $cols['contact_numbers'] = array_values(array_filter((array) $cols['contact_numbers'], fn ($n) => $n !== null && $n !== '')) ?: null;
        return $cols;
    }

    /** Full passenger record for PRC/PIF. Own route only if the manifest gave one, else the incident's. */
    private function passengerDetailJson(Passenger $p, ?Incident $i, Collection $travellingWith): array
    {
        $ownRoute = $p->route_from || $p->route_to;
        return ['id' => $p->id, 'incidentId' => $p->incident_id, 'name' => $p->name, 'seatNumber' => $p->seat_number, 'cnic' => $p->cnic]
            + $this->fieldsJson($p, Passenger::MANIFEST_FIELDS)
            + [
                'age' => $p->ageAt($i?->incident_date),
                'effectiveRoute' => $ownRoute
                    ? ['from' => $p->route_from ?? $i?->from_location, 'to' => $p->route_to ?? $i?->to_location, 'via' => $p->route_via]
                    : ['from' => $i?->from_location ?? $p->from_location, 'to' => $i?->to_location ?? $p->to_location, 'via' => $p->route_via ?? $i?->via],
                'travellingWith' => $travellingWith->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'seatNumber' => $s->seat_number, 'paxType' => $s->pax_type, 'dateOfBirth' => $s->date_of_birth?->format('Y-m-d'), 'nationality' => $s->nationality])->values()->all(),
            ];
    }

    /**
     * Passenger File: passenger + incident + status updates + form history. Each role sees only the
     * forms it is allowed to; everyone sees who the contact person is (name, relationship, phones).
     */
    private function dossierJson(Passenger $p, bool $withPrc, bool $withPif): array
    {
        $i = $p->incident;
        $party = $p->incident_id && $p->pnr ? Passenger::where('incident_id', $p->incident_id)->where('pnr', $p->pnr)->where('id', '!=', $p->id)->orderBy('id')->get() : collect();
        $forms = $p->informationForms()->latest('id')->get();
        $contact = $forms->first();
        return [
            'incident' => $i ? $this->incidentJson($i) : null,
            'passenger' => $this->passengerDetailJson($p, $i, $party),
            'updates' => $p->updates()->with('teamMember')->latest('id')->get()->map(fn ($u) => $this->updateJson($u, true))->all(),
            'contactPerson' => $contact ? [
                'name' => trim($contact->informant_first_name.' '.$contact->informant_last_name),
                'relationship' => $contact->informant_relationship,
                'contactNumbers' => $contact->informant_contact_numbers ?? [],
                'email' => $contact->informant_email,
                'updatedBy' => $contact->submitted_by,
                'updatedAt' => $this->dateValue($contact->created_at),
            ] : null,
            'recordCards' => $withPrc ? $p->recordCards()->latest('id')->get()->map(fn ($c) => $this->recordCardJson($c))->all() : [],
            'informationForms' => $withPif ? $forms->map(fn ($f) => $this->informationFormJson($f))->all() : [],
        ];
    }

    /** Whole incident manifest + each passenger's latest PRC — used for "Print all PRC" and the XLSX export. */
    private function manifestJson(Incident $i): array
    {
        $passengers = $i->passengers()->get();
        $parties = $passengers->filter(fn ($p) => $p->pnr)->groupBy('pnr');
        $latestCards = PassengerRecordCard::whereIn('id', PassengerRecordCard::whereIn('passenger_id', $passengers->pluck('id'))->selectRaw('MAX(id)')->groupBy('passenger_id'))->get()->keyBy('passenger_id');
        return $this->incidentJson($i) + ['passengers' => $passengers->map(fn ($p) => $this->passengerDetailJson($p, $i, $p->pnr ? $parties[$p->pnr]->where('id', '!=', $p->id) : collect())
            + ['latestRecordCard' => isset($latestCards[$p->id]) ? $this->recordCardJson($latestCards[$p->id]) : null])->all()];
    }

    private function recordCardJson(PassengerRecordCard $c): array { return ['id' => $c->id, 'passengerId' => $c->passenger_id, 'teamMemberId' => $c->team_member_id, 'submittedBy' => $c->submitted_by, 'source' => $c->source] + $this->fieldsJson($c, PassengerRecordCard::FIELDS) + ['createdAt' => $this->dateValue($c->created_at)]; }
    private function informationFormJson(PassengerInformationForm $f): array { return ['id' => $f->id, 'passengerId' => $f->passenger_id, 'teamMemberId' => $f->team_member_id, 'submittedBy' => $f->submitted_by, 'changeReason' => $f->change_reason] + $this->fieldsJson($f, PassengerInformationForm::FIELDS) + ['createdAt' => $this->dateValue($f->created_at)]; }

    private function passengerFields(array $d): array { return ['name' => $d['name'], 'seat_number' => $d['seatNumber'], 'cnic' => $d['cnic'] ?? null, 'flight_number' => $d['flightNumber'] ?? null, 'from_location' => $d['fromLocation'] ?? null, 'to_location' => $d['toLocation'] ?? null, 'departure_time' => $d['departureTime'] ?? null, 'arrival_time' => $d['arrivalTime'] ?? null]; }
}
<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Incident;
use App\Models\Passenger;
use App\Models\PassengerRecordCard;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileTeamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_can_read_manifest_and_submit_partial_prc_with_status(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $member = $this->createMember($company, TeamMember::TYPE_STATION);
        $token = $member->createToken('test')->plainTextToken;

        $this->assertStationWorkflow($passenger, $member, $token);

        $this->assertDatabaseCount('passenger_updates', 1);
        $this->assertDatabaseCount('passenger_record_cards', 1);
    }

    public function test_sat_volunteer_can_login_and_use_station_passenger_apis(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $member = $this->createMember($company, TeamMember::TYPE_SAT);

        $login = $this->postJson('/api/mobile/auth/station/login', [
            'email' => $member->email,
            'password' => 'password',
        ]);
        $login->assertOk()->assertJsonPath('user.teamType', TeamMember::TYPE_SAT);

        $this->assertStationWorkflow($passenger, $member, $login->json('token'));

        $this->assertDatabaseCount('passenger_updates', 1);
        $this->assertDatabaseCount('passenger_record_cards', 1);
    }

    public function test_go_can_read_full_passenger_erp_and_submit_partial_prc(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $member = $this->createMember($company, TeamMember::TYPE_GO);
        $token = $member->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/mobile/go/passengers/{$passenger->id}/erp")
            ->assertOk()
            ->assertJsonPath('passenger.nationality', 'PK')
            ->assertJsonPath('recordCards', [])
            ->assertJsonPath('informationForms', []);

        $this->withToken($token)
            ->postJson("/api/mobile/go/passengers/{$passenger->id}/updates", [
                'status' => 'Injured',
                'prc' => [
                    'nokDetails' => 'Mother: +923001234567',
                    'familyAssistOffered' => true,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('recordCard.nokDetails', 'Mother: +923001234567')
            ->assertJsonPath('recordCard.familyAssistOffered', true);
    }

    public function test_partial_prc_answers_from_different_teams_are_merged_not_lost(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $station = $this->createMember($company, TeamMember::TYPE_STATION)->createToken('test')->plainTextToken;
        $go = $this->createMember($company, TeamMember::TYPE_GO)->createToken('test')->plainTextToken;

        $this->withToken($station)
            ->postJson("/api/mobile/station/passengers/{$passenger->id}/updates", [
                'status' => 'Injured',
                'prc' => ['contactMade' => true, 'nokDetails' => 'Mother: +923001234567'],
            ])->assertCreated();

        // GO sends only what it learnt — Station's answers must survive in the new version.
        // (forgetGuards: tests share one app instance, so drop the cached Station user before switching tokens.)
        $this->app['auth']->forgetGuards();
        $this->withToken($go)
            ->postJson("/api/mobile/go/passengers/{$passenger->id}/updates", [
                'status' => 'Critical',
                'prc' => ['lifeThreatening' => true],
            ])
            ->assertCreated()
            ->assertJsonPath('recordCard.lifeThreatening', true)
            ->assertJsonPath('recordCard.contactMade', true)
            ->assertJsonPath('recordCard.nokDetails', 'Mother: +923001234567');

        // Same answer again → no new version. Explicit null clears an answer.
        $this->withToken($go)
            ->postJson("/api/mobile/go/passengers/{$passenger->id}/updates", ['status' => 'Critical', 'prc' => ['lifeThreatening' => true]])
            ->assertCreated()->assertJsonPath('recordCard', null);
        $this->app['auth']->forgetGuards();
        $this->withToken($station)
            ->postJson("/api/mobile/station/passengers/{$passenger->id}/updates", ['status' => 'Critical', 'prc' => ['nokDetails' => null]])
            ->assertCreated()
            ->assertJsonPath('recordCard.nokDetails', null)
            ->assertJsonPath('recordCard.contactMade', true);

        $this->assertDatabaseCount('passenger_record_cards', 3);
    }

    public function test_team_member_cannot_read_or_update_another_company_passenger(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'email' => 'other@example.test',
            'password_hash' => Hash::make('password'),
        ]);
        $member = $this->createMember($otherCompany, TeamMember::TYPE_STATION);
        $token = $member->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/mobile/station/passengers/{$passenger->id}/erp")
            ->assertNotFound();

        $this->withToken($token)
            ->postJson("/api/mobile/station/passengers/{$passenger->id}/updates", [
                'status' => 'Safe',
                'prc' => ['contactMade' => true],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('passenger_updates', 0);
        $this->assertDatabaseCount('passenger_record_cards', 0);
    }

    public function test_prc_field_validation_returns_unprocessable_entity(): void
    {
        [$company, , $passenger] = $this->createPassengerRecords();
        $member = $this->createMember($company, TeamMember::TYPE_GO);
        $token = $member->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/mobile/go/passengers/{$passenger->id}/updates", [
                'status' => 'Safe',
                'prc' => ['contactMade' => 'not-a-boolean'],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('passenger_updates', 0);
        $this->assertDatabaseCount('passenger_record_cards', 0);
    }

    private function createPassengerRecords(): array
    {
        $company = Company::create([
            'name' => 'Example Airline',
            'email' => 'airline@example.test',
            'password_hash' => Hash::make('password'),
        ]);
        $incident = Incident::create([
            'company_id' => $company->id,
            'flight_number' => 'EX123',
            'from_location' => 'LHE',
            'to_location' => 'JED',
            'via' => 'DXB',
            'other_details' => 'Test incident',
            'incident_date' => '2026-10-14',
            'incident_time' => '10:30',
            'status' => Incident::STATUS_ONGOING,
        ]);
        $passenger = Passenger::create([
            'company_id' => $company->id,
            'incident_id' => $incident->id,
            'name' => 'Amina Khan',
            'seat_number' => '12A',
            'first_name' => 'Amina',
            'last_name' => 'Khan',
            'pax_type' => 'F',
            'date_of_birth' => '1990-01-02',
            'nationality' => 'PK',
            'passport_number' => 'P1234567',
            'route_from' => 'LHE',
            'route_to' => 'JED',
        ]);

        return [$company, $incident, $passenger];
    }

    private function createMember(Company $company, string $type): TeamMember
    {
        static $number = 0;
        $number++;

        return TeamMember::create([
            'company_id' => $company->id,
            'full_name' => "{$type} Member",
            'email' => "member{$number}@example.test",
            'password_hash' => Hash::make('password'),
            'team_type' => $type,
        ]);
    }

    private function assertStationWorkflow(Passenger $passenger, TeamMember $member, string $token): void
    {
        $this->withToken($token)
            ->getJson("/api/mobile/station/passengers/{$passenger->id}/erp")
            ->assertOk()
            ->assertJsonPath('passenger.firstName', 'Amina')
            ->assertJsonPath('passenger.passportNumber', 'P1234567')
            ->assertJsonPath('incident.via', 'DXB')
            ->assertJsonPath('recordCards', [])
            ->assertJsonPath('informationForms', []);

        $contactDetails = "Family notified by {$member->team_type}";
        $this->withToken($token)
            ->postJson("/api/mobile/station/passengers/{$passenger->id}/updates", [
                'status' => 'Safe',
                'remarks' => 'Passenger contacted',
                'prc' => [
                    'contactMade' => true,
                    'contactDetails' => $contactDetails,
                    'familyAssistOffered' => false,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'Safe')
            ->assertJsonPath('recordCard.contactMade', true)
            ->assertJsonPath('recordCard.contactDetails', $contactDetails)
            ->assertJsonPath('recordCard.familyAssistOffered', false)
            ->assertJsonPath('recordCard.submittedBy', $member->full_name);
    }
}

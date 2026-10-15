<?php

namespace Database\Seeders;

use App\Models\Passenger;
use App\Models\PassengerInformationForm;
use App\Models\PassengerRecordCard;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds test accounts across every role for QA.
 *
 * Idempotent — safe to re-run; uses updateOrInsert on unique email keys.
 * All test passwords: TestPass123!
 */
class TestAccountsSeeder extends Seeder
{
    public const PASSWORD = 'TestPass123!';

    public function run(): void
    {
        $now = now();
        $hashed = Hash::make(self::PASSWORD);

        // ---- COMPANIES ------------------------------------------------------
        DB::table('companies')->updateOrInsert(
            ['email' => 'acme@test.com'],
            ['name' => 'Acme Airlines',   'password_hash' => $hashed, 'logo_url' => null, 'created_at' => $now]
        );
        DB::table('companies')->updateOrInsert(
            ['email' => 'beta@test.com'],
            ['name' => 'Beta Airways',    'password_hash' => $hashed, 'logo_url' => null, 'created_at' => $now]
        );

        $acmeId = DB::table('companies')->where('email', 'acme@test.com')->value('id');
        $betaId = DB::table('companies')->where('email', 'beta@test.com')->value('id');

        // ---- TEAM MEMBERS — full spread on Acme, subset on Beta -------------
        $teamMembers = [
            // Acme — one of each type
            ['company_id' => $acmeId, 'email' => 'station.a@test.com', 'full_name' => 'Sara Station (Acme)',    'team_type' => 'Station Team'],
            ['company_id' => $acmeId, 'email' => 'erc.a@test.com',     'full_name' => 'Emma ERC (Acme)',        'team_type' => 'ERC Team'],
            ['company_id' => $acmeId, 'email' => 'go.a@test.com',      'full_name' => 'Gary GO (Acme)',         'team_type' => 'GO Team'],
            ['company_id' => $acmeId, 'email' => 'sat.a@test.com',     'full_name' => 'Sam SAT (Acme)',         'team_type' => 'SAT - Volunteers'],
            // Beta — subset (for multi-tenant scoping tests)
            ['company_id' => $betaId, 'email' => 'station.b@test.com', 'full_name' => 'Bobby Station (Beta)',   'team_type' => 'Station Team'],
            ['company_id' => $betaId, 'email' => 'go.b@test.com',      'full_name' => 'Grace GO (Beta)',        'team_type' => 'GO Team'],
        ];
        foreach ($teamMembers as $m) {
            DB::table('team_members')->updateOrInsert(
                ['email' => $m['email']],
                array_merge($m, ['password_hash' => $hashed, 'created_at' => $now])
            );
        }

        // ---- INCIDENT + PASSENGERS on Acme (so team members have data) ------
        DB::table('incidents')->updateOrInsert(
            ['company_id' => $acmeId, 'flight_number' => 'AC301'],
            [
                'from_location' => 'Karachi (KHI)',
                'to_location'   => 'Lahore (LHE)',
                'incident_date' => now()->toDateString(),
                'incident_time' => '08:30',
                'status'        => 'Ongoing',
                'created_at'    => $now,
            ]
        );
        $incidentId = DB::table('incidents')
            ->where(['company_id' => $acmeId, 'flight_number' => 'AC301'])
            ->value('id');

        $passengers = [
            ['name' => 'John Smith',       'seat_number' => '12A', 'cnic' => '42101-1234567-1'],
            ['name' => 'Jane Doe',         'seat_number' => '12B', 'cnic' => '42101-1234567-2'],
            ['name' => 'Ahmed Khan',       'seat_number' => '14C', 'cnic' => '42101-1234567-3'],
            ['name' => 'Fatima Ali',       'seat_number' => '14D', 'cnic' => '42101-1234567-4'],
            ['name' => 'Robert Brown',     'seat_number' => '16E', 'cnic' => '42101-1234567-5'],
            ['name' => 'Sarah Johnson',    'seat_number' => '16F', 'cnic' => '42101-1234567-6'],
            ['name' => 'Muhammad Hassan',  'seat_number' => '18A', 'cnic' => '42101-1234567-7'],
            ['name' => 'Ayesha Malik',     'seat_number' => '18B', 'cnic' => '42101-1234567-8'],
            ['name' => 'David Wilson',     'seat_number' => '20C', 'cnic' => '42101-1234567-9'],
            ['name' => 'Zainab Rehman',    'seat_number' => '20D', 'cnic' => '42101-0000000-0'],
        ];
        foreach ($passengers as $p) {
            DB::table('passengers')->updateOrInsert(
                ['incident_id' => $incidentId, 'seat_number' => $p['seat_number']],
                array_merge($p, [
                    'company_id' => $acmeId,
                    'incident_id' => $incidentId,
                    'created_at' => $now,
                ])
            );
        }

        // ---- ERP manifest details (Eloquent, so passport_number gets encrypted) --
        DB::table('incidents')->where('id', $incidentId)->update(['other_details' => 'QA sample — runway excursion on landing']);
        $manifest = [
            '12A' => ['last_name' => 'Smith',   'first_name' => 'John',     'pax_type' => 'M', 'date_of_birth' => '1980-04-12', 'nationality' => 'British',   'pnr' => 'SMT001', 'passport_number' => '533112233', 'contact_numbers' => ['+447700900123']],
            '12B' => ['last_name' => 'Doe',     'first_name' => 'Jane',     'pax_type' => 'F', 'date_of_birth' => '1982-09-30', 'nationality' => 'British',   'pnr' => 'SMT001', 'passport_number' => '533445566'],
            '14C' => ['last_name' => 'Khan',    'first_name' => 'Ahmed',    'pax_type' => 'M', 'date_of_birth' => '1975-01-05', 'nationality' => 'Pakistani', 'pnr' => 'KHN002', 'passport_number' => 'AB1234567', 'contact_numbers' => ['+923001234567'], 'nok_name' => 'Bilal Khan', 'nok_relationship' => 'Brother', 'nok_phone' => '+923331234567'],
            '14D' => ['last_name' => 'Ali',     'first_name' => 'Fatima',   'pax_type' => 'F', 'date_of_birth' => '1979-07-21', 'nationality' => 'Pakistani', 'pnr' => 'KHN002'],
            '16E' => ['last_name' => 'Brown',   'first_name' => 'Robert',   'pax_type' => 'M', 'date_of_birth' => '1966-11-02', 'nationality' => 'American'],
            '16F' => ['last_name' => 'Johnson', 'first_name' => 'Sarah',    'pax_type' => 'F', 'date_of_birth' => '1990-02-14', 'nationality' => 'Canadian',  'route_from' => 'Karachi (KHI)', 'route_to' => 'Toronto (YYZ)', 'route_via' => 'Lahore (LHE)'],
            '18A' => ['last_name' => 'Hassan',  'first_name' => 'Muhammad', 'pax_type' => 'M', 'date_of_birth' => '1988-05-09', 'nationality' => 'Pakistani', 'pnr' => 'HSN003'],
            '18B' => ['last_name' => 'Malik',   'first_name' => 'Ayesha',   'pax_type' => 'F', 'date_of_birth' => '1991-12-01', 'nationality' => 'Pakistani', 'pnr' => 'HSN003'],
            '20C' => ['last_name' => 'Wilson',  'first_name' => 'David',    'pax_type' => 'M', 'date_of_birth' => '1972-03-18', 'nationality' => 'Australian'],
            '20D' => ['last_name' => 'Rehman',  'first_name' => 'Zainab',   'pax_type' => 'C', 'date_of_birth' => '2017-08-25', 'nationality' => 'Pakistani', 'pnr' => 'HSN003'],
        ];
        foreach ($manifest as $seat => $fields) {
            Passenger::where(['incident_id' => $incidentId, 'seat_number' => $seat])->first()?->update($fields);
        }

        // ---- One sample PRC (Station) and contact person / PIF (ERC) -----------
        $station = TeamMember::where('email', 'station.a@test.com')->first();
        $erc     = TeamMember::where('email', 'erc.a@test.com')->first();
        $john    = Passenger::where(['incident_id' => $incidentId, 'seat_number' => '12A'])->first();
        $ahmed   = Passenger::where(['incident_id' => $incidentId, 'seat_number' => '14C'])->first();
        if ($station && $john) {
            PassengerRecordCard::updateOrCreate(
                ['passenger_id' => $john->id, 'team_member_id' => $station->id],
                ['submitted_by' => $station->full_name, 'nok_details' => 'Mary Smith (mother) — +447700900456, 1 High St, Leeds', 'contact_made' => true,
                 'contact_details' => 'Called his mother from own mobile', 'family_assist_offered' => true, 'family_assist_accepted' => false,
                 'speaks_english' => true, 'primary_language' => 'English', 'intention_local_address' => true,
                 'intention_local_address_details' => 'Pearl Continental, Lahore', 'occupation' => 'Engineer']
            );
        }
        if ($erc && $ahmed) {
            PassengerInformationForm::updateOrCreate(
                ['passenger_id' => $ahmed->id, 'team_member_id' => $erc->id],
                ['submitted_by' => $erc->full_name, 'informant_last_name' => 'Khan', 'informant_first_name' => 'Bilal',
                 'informant_contact_numbers' => ['+923331234567'], 'informant_relationship' => 'Brother', 'informant_is_relative_or_meeter' => true,
                 'pax_flight_details' => 'AC301 Karachi to Lahore, morning flight', 'informant_languages' => 'Urdu / English',
                 'informant_contacted_pax' => false, 'has_medical_problem' => true, 'medical_details' => 'Diabetic (insulin)',
                 'other_information' => 'Scar on left forearm; grey hair; brown eyes']
            );
        }

        // ---- Sample status updates (so lists aren't empty) -----------------
        $stationMember = DB::table('team_members')->where('email', 'station.a@test.com')->first();
        if ($stationMember) {
            $firstThree = DB::table('passengers')
                ->where('incident_id', $incidentId)
                ->orderBy('id')
                ->limit(3)
                ->get(['id', 'name']);
            foreach ($firstThree as $i => $pax) {
                $status = ['Safe', 'Injured', 'Critical'][$i] ?? 'Safe';
                DB::table('passenger_updates')->updateOrInsert(
                    ['passenger_id' => $pax->id, 'team_member_id' => $stationMember->id, 'status' => $status],
                    [
                        'submitted_by' => $stationMember->full_name,
                        'remarks'      => 'Initial status set during QA seeding',
                        'created_at'   => $now,
                    ]
                );
            }
        }

        $this->command?->info('TestAccountsSeeder: seeded 2 companies, 6 team members, 1 incident, 10 passengers (with ERP manifest data), 1 PRC, 1 PIF, 3 status updates.');
    }
}

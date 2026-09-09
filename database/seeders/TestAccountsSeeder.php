<?php

namespace Database\Seeders;

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

        $this->command?->info('TestAccountsSeeder: seeded 2 companies, 6 team members, 1 incident, 10 passengers, 3 status updates.');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('super_admins')->updateOrInsert(
            ['email' => 'admin@companysuite.local'],
            ['password_hash' => Hash::make('ChangeMe1234!')]
        );
    }
}

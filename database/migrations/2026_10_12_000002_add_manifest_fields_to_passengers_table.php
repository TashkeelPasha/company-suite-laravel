<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Passenger data the airline already has BEFORE the incident (booking / APIS),
| uploaded by the Company Admin in the Excel manifest. PRC (ERP-001) and
| PIF (ERP-002) read these instead of asking for them again.
|
| `name` stays as the display name ("First Last") so existing screens keep working.
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('pax_type', 1)->nullable();          // M | F | C | I
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('pnr', 20)->nullable();              // groups "Travelling With"
            // Passenger's own route — NULL means "same as the incident route".
            $table->string('route_from')->nullable();
            $table->string('route_to')->nullable();
            $table->string('route_via')->nullable();
            $table->json('contact_numbers')->nullable();        // up to 3, with country code
            $table->text('primary_address')->nullable();
            $table->text('passport_number')->nullable();        // encrypted at rest (model cast)
            $table->date('passport_issue_date')->nullable();
            $table->string('passport_issue_place')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('award_plus_number')->nullable();
            // Emergency contact captured at booking, if the airline has it.
            $table->string('nok_name')->nullable();
            $table->string('nok_relationship')->nullable();
            $table->string('nok_phone')->nullable();

            $table->index(['incident_id', 'pnr']);
        });
    }

    public function down(): void
    {
        // MySQL drops the incident_id FK's own index once (incident_id, pnr) can serve it,
        // so it must come back before the composite index can be dropped.
        if (!Schema::hasIndex('passengers', ['incident_id'])) {
            Schema::table('passengers', fn (Blueprint $table) => $table->index('incident_id', 'passengers_incident_id_foreign'));
        }
        Schema::table('passengers', function (Blueprint $table) {
            $table->dropIndex(['incident_id', 'pnr']);
            $table->dropColumn([
                'last_name', 'first_name', 'pax_type', 'date_of_birth', 'nationality', 'pnr',
                'route_from', 'route_to', 'route_via', 'contact_numbers', 'primary_address',
                'passport_number', 'passport_issue_date', 'passport_issue_place', 'birth_place',
                'award_plus_number', 'nok_name', 'nok_relationship', 'nok_phone',
            ]);
        });
    }
};

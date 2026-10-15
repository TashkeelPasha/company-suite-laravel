<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| ERP-002 Passenger Information Form — filled by ERC when a relative / meeter
| calls in with information about a passenger. Append-only: one row per call.
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::create('passenger_information_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnDelete();
            $table->foreignId('team_member_id')
                ->nullable()
                ->constrained('team_members')
                ->nullOnDelete();
            $table->string('submitted_by');

            // Person providing the information
            $table->string('informant_last_name');
            $table->string('informant_first_name');
            $table->json('informant_contact_numbers');
            $table->text('informant_address')->nullable();
            $table->string('informant_email')->nullable();
            $table->string('informant_relationship');
            $table->boolean('informant_is_relative_or_meeter');
            $table->text('pax_flight_details');
            $table->string('informant_languages')->nullable();
            $table->boolean('informant_contacted_pax');
            $table->text('informant_contact_details')->nullable();

            // Passenger details as reported by the caller
            $table->text('travelling_with')->nullable();
            $table->text('nok_details')->nullable();
            $table->boolean('has_medical_problem')->nullable();
            $table->text('medical_details')->nullable();       // encrypted at rest (model cast)
            $table->text('other_information')->nullable();     // skin/hair/eye colour, scars, tattoos
            $table->timestamp('created_at')->useCurrent();

            $table->index(['passenger_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_information_forms');
    }
};

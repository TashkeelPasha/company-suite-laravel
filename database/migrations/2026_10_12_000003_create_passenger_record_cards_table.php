<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| ERP-001 Passenger Record Card — the Station / SAT / GO part, answered with
| their status updates. The Company's part (manifest) lives on `passengers`.
| Append-only: every save is a new version.
| Pre-incident fields (name, DOB, passport…) live on `passengers`, not here.
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::create('passenger_record_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnDelete();
            // Nullable + nullOnDelete: an emergency record must survive the
            // staff account being removed. `submitted_by` keeps the name.
            $table->foreignId('team_member_id')
                ->nullable()
                ->constrained('team_members')
                ->nullOnDelete();
            $table->string('submitted_by');
            $table->string('source', 10)->default('form');

            $table->text('travelling_with')->nullable();
            $table->text('nok_details')->nullable();
            $table->text('relatives_meeters')->nullable();
            $table->boolean('contact_made')->nullable();
            $table->text('contact_details')->nullable();
            $table->text('local_address')->nullable();
            $table->text('other_contacts')->nullable();

            $table->boolean('life_threatening')->nullable();
            $table->text('injury_details')->nullable();

            $table->boolean('intention_local_address')->nullable();
            $table->text('intention_local_address_details')->nullable();
            $table->boolean('intention_continue_journey')->nullable();
            $table->text('intention_continue_journey_details')->nullable();
            $table->boolean('intention_src')->nullable();
            $table->text('intention_other')->nullable();

            $table->boolean('family_assist_offered')->nullable();
            $table->boolean('family_assist_accepted')->nullable();
            $table->boolean('family_assist_team_advised')->nullable();

            $table->boolean('speaks_english')->nullable();
            $table->string('primary_language')->nullable();
            $table->string('secondary_language')->nullable();

            $table->string('occupation')->nullable();
            $table->text('other_information')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['passenger_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_record_cards');
    }
};

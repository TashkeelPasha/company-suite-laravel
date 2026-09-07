<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('seat_number');
            $table->string('cnic')->nullable();
            // Legacy per-passenger flight fields — nullable when passenger belongs to an incident.
            $table->string('flight_number')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};

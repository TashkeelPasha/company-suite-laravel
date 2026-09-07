<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->string('flight_number');
            $table->string('from_location');
            $table->string('to_location');
            $table->date('incident_date');
            $table->string('incident_time');   // "HH:MM"
            $table->string('status')->default('Ongoing'); // "Ongoing" | "Operation Completed"
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

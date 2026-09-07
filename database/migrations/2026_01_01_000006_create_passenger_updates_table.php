<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passenger_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnDelete();
            $table->foreignId('team_member_id')
                ->constrained('team_members')
                ->cascadeOnDelete();
            // Denormalised — survives team_member deletion.
            $table->string('submitted_by');
            $table->string('status');   // Safe | Injured | Critical | Deceased | Unconfirmed
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['passenger_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_updates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('relative_info_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')
                ->constrained('passengers')
                ->cascadeOnDelete();
            $table->foreignId('team_member_id')
                ->constrained('team_members')
                ->cascadeOnDelete();
            // Denormalised — history survives team_member deletion.
            $table->string('updated_by_name');
            $table->string('contact_name');
            $table->string('relationship');
            $table->text('telephone_numbers');
            $table->text('address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['passenger_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relative_info_updates');
    }
};

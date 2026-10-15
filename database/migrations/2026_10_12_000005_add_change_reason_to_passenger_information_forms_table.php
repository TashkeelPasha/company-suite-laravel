<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| ERP-002 is now one contact person per passenger: ERC sets them once, later
| saves are edits (new versions). Replacing the person needs a reason.
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::table('passenger_information_forms', function (Blueprint $table) {
            $table->text('change_reason')->nullable()->after('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::table('passenger_information_forms', function (Blueprint $table) {
            $table->dropColumn('change_reason');
        });
    }
};

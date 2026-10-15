<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| ERP-001 / ERP-002 header: "Flt No · Date · From · To · Via · Other Details".
| Via = intermediate stop (e.g. LHE → KHI → JED), NOT the airline.
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('via')->nullable()->after('to_location');
            $table->text('other_details')->nullable()->after('via');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['via', 'other_details']);
        });
    }
};

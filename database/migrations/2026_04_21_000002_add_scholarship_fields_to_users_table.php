<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_scholarship')->default(false)->after('achievements');
            $table->string('scholarship_name', 255)->nullable()->after('has_scholarship');
            $table->unsignedSmallInteger('scholarship_year_awarded')->nullable()->after('scholarship_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_scholarship', 'scholarship_name', 'scholarship_year_awarded']);
        });
    }
};

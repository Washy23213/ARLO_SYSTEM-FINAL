<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('data_privacy_consent')->default(false)->after('profile_visibility');
            $table->timestamp('data_privacy_consented_at')->nullable()->after('data_privacy_consent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['data_privacy_consent', 'data_privacy_consented_at']);
        });
    }
};

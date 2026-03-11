<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('user')->after('password');
            $table->boolean('is_active')->default(false)->after('role');
        });

        // Mevcut kullanıcıları aktif yap; en az bir admin olsun
        $count = \DB::table('users')->count();
        if ($count > 0) {
            \DB::table('users')->update(['is_active' => true]);
            \DB::table('users')->orderBy('id')->limit(1)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};

<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')->default(User::ROLE_ADMIN)->after('password');
            });
        }

        DB::table('users')
            ->whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => User::ROLE_ADMIN]);

        DB::table('users')
            ->where('username', 'MYHS')
            ->update(['role' => User::ROLE_DEVELOPER]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }
};

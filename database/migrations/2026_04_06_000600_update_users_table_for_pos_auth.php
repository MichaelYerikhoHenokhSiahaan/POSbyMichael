<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username')->nullable()->after('name');
            });
        }

        DB::table('users')
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                if (filled($user->username)) {
                    return;
                }

                $baseUsername = $user->email
                    ? Str::before((string) $user->email, '@')
                    : Str::slug((string) $user->name, '_');

                $baseUsername = Str::lower($baseUsername ?: 'user');
                $username = $baseUsername;
                $suffix = 1;

                while (
                    DB::table('users')
                        ->where('id', '!=', $user->id)
                        ->whereRaw('LOWER(username) = ?', [$username])
                        ->exists()
                ) {
                    $username = "{$baseUsername}{$suffix}";
                    $suffix++;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'username' => $username,
                        'updated_at' => now(),
                    ]);
            });

        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username')->nullable(false)->change();
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username');
            });
        }

        $now = now();

        DB::table('users')->updateOrInsert(
            ['username' => 'MYHS'],
            [
                'name' => 'UD PINDO Admin',
                'email' => 'myhs@udpindo.local',
                'password' => Hash::make('udindo123'),
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_username_unique');
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('username');
            });
        }
    }
};

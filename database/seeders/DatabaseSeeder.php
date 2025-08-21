<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        $superadminRole = Role::firstOrCreate(['slug' => 'superadmin'],['name' => 'Superadmin']);

        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@potensi-utama.ac.id'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('potensiutama'),
                'auth_provider' => 'local',
            ]
        );

        $superadmin->roles()->attach($superadminRole->id);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = DB::table('roles')->where('slug', 'superadmin')->first();

        $superadmin = User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@potensi-utama.ac.id',
            'password' => Hash::make('potensiutama'),
            'auth_provider' => 'local',
        ]);

        $superadmin->roles()->attach($superadminRole->id);
    }
}

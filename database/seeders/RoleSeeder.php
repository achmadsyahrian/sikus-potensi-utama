<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Superadmin', 'slug' => 'superadmin'],
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Dosen', 'slug' => 'dosen'],
            ['name' => 'Pegawai', 'slug' => 'pegawai'],
            ['name' => 'Mahasiswa', 'slug' => 'mahasiswa'],
            // ['name' => 'Mitra', 'slug' => 'mitra'],
        ];

        DB::table('roles')->insert($roles);
    }
}

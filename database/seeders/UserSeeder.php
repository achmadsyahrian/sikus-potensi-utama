<?php

namespace Database\Seeders;

use App\Models\LecturerDetail;
use App\Models\Role;
use App\Models\User;
use App\Models\StudentDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        $roles = Role::pluck('id', 'slug');
        $studyProgramCodes = [
            '60206', '90221', '56201', '57403', '61201', '57401', '64201X', '74201X', '91362X',
            '58201', '55202', '90241', '88203', '55101', '62201', '55201', '57201', '61206',
            '73201', '26201', '91362', '74201', '64201'
        ];

        if ($roles->isEmpty()) {
            echo "Pastikan RolesSeeder sudah dijalankan terlebih dahulu.\n";
            return;
        }

        // Hapus data pengguna lama, kecuali superadmin (user ID 1)
        User::where('id', '>', 1)->delete();
        DB::table('lecturer_details')->truncate();
        DB::table('student_details')->truncate();

        // Cek dan buat Superadmin jika belum ada
        $superadmin = User::find(1);
        if (!$superadmin) {
            $superadminRole = $roles['superadmin'];
            $superadmin = User::create([
                'name' => 'Superadmin',
                'email' => 'superadmin@potensi-utama.ac.id',
                'password' => Hash::make('potensiutama'),
                'auth_provider' => 'local',
            ]);
            $superadmin->roles()->attach($superadminRole);
        }

        // --- Buat Data Admin ---
        $this->createUser($faker, 'Admin', 'admin@example.com', $roles['admin']);

        // --- Buat Data Dosen ---
        $dosenRole = $roles['dosen'];
        for ($i = 0; $i < 5; $i++) {
            $user = $this->createUser($faker, 'Dosen ' . ($i + 1), $faker->unique()->safeEmail, $dosenRole);
            LecturerDetail::create([
                'user_id' => $user->id,
                'nidn' => $faker->unique()->numerify('##########'),
                'work_unit' => $faker->randomElement(['Fakultas Ilmu Komputer', 'Fakultas Ekonomi Bisnis', 'Fakultas Hukum']),
                'functional_position' => $faker->randomElement(['Lektor Kepala', 'Lektor', 'Asisten Ahli']),
            ]);
        }

        // --- Buat Data Pegawai ---
        $pegawaiRole = $roles['pegawai'];
        for ($i = 0; $i < 5; $i++) {
            $this->createUser($faker, 'Pegawai ' . ($i + 1), $faker->unique()->safeEmail, $pegawaiRole);
        }

        // --- Buat Data Mitra ---
        $mitraRole = $roles['mitra'];
        for ($i = 0; $i < 5; $i++) {
            $this->createUser($faker, 'Mitra ' . ($i + 1), $faker->unique()->safeEmail, $mitraRole);
        }

        // --- Buat Data Mahasiswa ---
        $mahasiswaRole = $roles['mahasiswa'];
        for ($i = 0; $i < 50; $i++) {
            $user = $this->createUser($faker, 'Mahasiswa ' . ($i + 1), $faker->unique()->safeEmail, $mahasiswaRole);
            StudentDetail::create([
                'user_id' => $user->id,
                'nim' => $faker->unique()->numerify('##########'),
                'study_program' => $faker->randomElement($studyProgramCodes),
                'domicile_address' => $faker->address,
                'phone_number' => $faker->phoneNumber,
            ]);
        }

        echo "Berhasil membuat data pengguna dummy untuk Superadmin, Admin, Dosen, Pegawai, Mitra, dan Mahasiswa.\n";
    }

    /**
     * Helper function untuk membuat user.
     *
     * @param \Faker\Generator $faker
     * @param string $name
     * @param string $email
     * @param int $roleId
     * @return \App\Models\User
     */
    private function createUser($faker, $name, $email, $roleId)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'auth_provider' => 'local',
        ]);
        $user->roles()->attach($roleId);
        return $user;
    }
}

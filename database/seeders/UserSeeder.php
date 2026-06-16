<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name'              => 'Super Admin',
            'username'          => 'superadmin',           // username
            'password'          => Hash::make('suadmin26'), // password default
            'role'              => 'superadmin',
            'gender'            => 'male',
            'phone'             => '081234567890',
            'is_active'         => true,
            'nik'               => '1234567890123456',
            'birth_place_date'  => 'Jakarta, 01-01-1990',
            'last_education'    => 'S1 Teknik Informatika',
        ]);

        // Admin
        User::create([
            'name'              => 'Administrator',
            'username'          => 'admin',                // username
            'password'          => Hash::make('admin2026'),
            'role'              => 'admin',
            'gender'            => 'male',
            'phone'             => '081234567891',
            'is_active'         => true,
            'nik'               => '1234567890123457',
            'birth_place_date'  => 'Bandung, 15-05-1992',
            'last_education'    => 'S1 Manajemen',
        ]);

        User::create([
            'name'              => 'Super Admin 2',
            'username'          => 'superadmin2',           // username
            'password'          => Hash::make('adminglc26'), // password default
            'role'              => 'superadmin',
            'gender'            => 'male',
            'phone'             => '081234567890',
            'is_active'         => true,
            'nik'               => '1234567890123456',
            'birth_place_date'  => 'Jakarta, 01-01-1990',
            'last_education'    => 'S1 Teknik Informatika',
        ]);

        User::create([
            'name'              => 'Super Admin 3',
            'username'          => 'superadmin3',           // username
            'password'          => Hash::make('adminglc26'), // password default
            'role'              => 'superadmin',
            'gender'            => 'male',
            'phone'             => '081234567890',
            'is_active'         => true,
            'nik'               => '1234567890123456',
            'birth_place_date'  => 'Jakarta, 01-01-1990',
            'last_education'    => 'S1 Teknik Informatika',
        ]);

        User::create([
            'name'              => 'Super Admin 4',
            'username'          => 'superadmin4',           // username
            'password'          => Hash::make('adminglc26'), // password default
            'role'              => 'superadmin',
            'gender'            => 'male',
            'phone'             => '081234567890',
            'is_active'         => true,
            'nik'               => '1234567890123456',
            'birth_place_date'  => 'Jakarta, 01-01-1990',
            'last_education'    => 'S1 Teknik Informatika',
        ]);

        $this->command->info('✅ Dua user berhasil dibuat:');
        $this->command->info('   • superadmin / password123');
        $this->command->info('   • admin / password123');
    }
}

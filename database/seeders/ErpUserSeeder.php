<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ErpUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@consultiser.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'phone' => '+1-555-0100',
                'department' => 'Management',
                'designation' => 'System Administrator',
                'employee_id' => 'EMP-0001',
            ]
        );
        $admin->roles()->sync([Role::where('name', 'super_admin')->first()->id]);

        $hr = User::firstOrCreate(
            ['email' => 'hr@consultiser.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('hr12345'),
                'phone' => '+1-555-0101',
                'department' => 'Human Resources',
                'designation' => 'HR Manager',
                'employee_id' => 'EMP-0002',
            ]
        );
        $hr->roles()->sync([Role::where('name', 'hr')->first()->id]);

        $finance = User::firstOrCreate(
            ['email' => 'finance@consultiser.com'],
            [
                'name' => 'Finance Officer',
                'password' => Hash::make('finance123'),
                'phone' => '+1-555-0102',
                'department' => 'Finance',
                'designation' => 'Finance Officer',
                'employee_id' => 'EMP-0003',
            ]
        );
        $finance->roles()->sync([Role::where('name', 'finance')->first()->id]);

        $csr = User::firstOrCreate(
            ['email' => 'csr@consultiser.com'],
            [
                'name' => 'CSR Manager',
                'password' => Hash::make('csr12345'),
                'phone' => '+1-555-0103',
                'department' => 'Operations',
                'designation' => 'CSR Manager',
                'employee_id' => 'EMP-0004',
            ]
        );
        $csr->roles()->sync([Role::where('name', 'csr_manager')->first()->id]);

        $associate = User::firstOrCreate(
            ['email' => 'associate@consultiser.com'],
            [
                'name' => 'Team Associate',
                'password' => Hash::make('assoc123'),
                'phone' => '+1-555-0104',
                'department' => 'Operations',
                'designation' => 'Team Associate',
                'employee_id' => 'EMP-0005',
            ]
        );
        $associate->roles()->sync([Role::where('name', 'team_associate')->first()->id]);

        $this->command?->info('ERP users created: admin@consultiser.com / admin123');
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = (string) config('tanish.admin.email', 'tanish_admin@tanish.local');
        $adminPassword = (string) config('tanish.admin.password', '');

        if ($adminPassword !== '') {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'tanish_admin',
                    'password' => $adminPassword,
                    'role' => User::ROLE_ADMIN,
                ]
            );
        }

        if (app()->environment(['local', 'testing'])) {
            User::updateOrCreate(
                ['email' => 'admin@tanish.test'],
                [
                    'name' => 'Administrador TANISH',
                    'password' => 'password',
                    'role' => User::ROLE_ADMIN,
                ]
            );

            User::updateOrCreate(
                ['email' => 'almacen@tanish.test'],
                [
                    'name' => 'Personal de Almacén',
                    'password' => 'password',
                    'role' => User::ROLE_ALMACEN,
                ]
            );

            User::updateOrCreate(
                ['email' => 'ventas@tanish.test'],
                [
                    'name' => 'Personal de Ventas',
                    'password' => 'password',
                    'role' => User::ROLE_VENTAS,
                ]
            );
        }
    }
}

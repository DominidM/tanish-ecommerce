<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = (string) config('tanish.admin.email', 'tanish_admin@tanish.local');
        $adminUsername = (string) config('tanish.admin.username', 'tanish_admin');
        $adminPassword = (string) config('tanish.admin.password', '');

        if ($adminPassword !== '') {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'tanish_admin',
                    'username' => $adminUsername,
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
                    'username' => 'admin',
                    'password' => 'password',
                    'role' => User::ROLE_ADMIN,
                ]
            );

            User::updateOrCreate(
                ['email' => 'almacen@tanish.test'],
                [
                    'name' => 'Personal de Almacén',
                    'username' => 'almacen',
                    'password' => 'password',
                    'role' => User::ROLE_ALMACEN,
                ]
            );

            User::updateOrCreate(
                ['email' => 'ventas@tanish.test'],
                [
                    'name' => 'Personal de Ventas',
                    'username' => 'ventas',
                    'password' => 'password',
                    'role' => User::ROLE_VENTAS,
                ]
            );
        }
    }
}

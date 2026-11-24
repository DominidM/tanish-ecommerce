<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Iniciar sesión';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Accede al panel administrativo de TANISH.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Usuario')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    /**
     * Permite iniciar sesión con el nombre de usuario (tanish_admin) o,
     * como respaldo, con el correo completo.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim((string) ($data['email'] ?? ''));
        $field = str_contains($login, '@') ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $data['password'] ?? '',
        ];
    }
}

<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Ignora el "intended URL" antiguo (que podía apuntar al origen con IP)
        // y siempre entra al panel en el dominio público configurado.
        return redirect()->to(Filament::getUrl());
    }
}

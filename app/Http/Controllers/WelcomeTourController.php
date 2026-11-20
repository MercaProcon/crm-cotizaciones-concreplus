<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WelcomeTourController extends Controller
{
    /**
     * Marca el tutorial de bienvenida como visto para que no vuelva a
     * aparecer automaticamente. No borra nada ni cambia otros datos del
     * usuario.
     */
    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['welcomed_at' => now()]);

        return back();
    }
}

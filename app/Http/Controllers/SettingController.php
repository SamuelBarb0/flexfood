<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // GET /r/{restaurante:slug}/settings
    public function edit(Restaurante $restaurante)
    {
        // Uno por restaurante: si no existe, devolvemos un modelo “en blanco”
        $settings = SiteSetting::firstOrNew([
            'restaurante_id' => $restaurante->id,
        ]);

        return view('settings.edit', compact('settings', 'restaurante'));
    }

    // POST /r/{restaurante:slug}/settings
    public function update(Restaurante $restaurante, Request $request)
    {
        $request->validate([
            'site_name'    => ['required', 'string', 'max:120'],
            'logo_path'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon_path' => ['nullable', 'image', 'mimes:png,ico', 'max:512'],
        ]);

        $settings = SiteSetting::firstOrNew([
            'restaurante_id' => $restaurante->id,
        ]);

        $settings->site_name      = $request->site_name;
        $settings->restaurante_id = $restaurante->id; // asegurar clave

        if ($request->hasFile('logo_path')) {
            $settings->logo_path = $request->file('logo_path')->store('uploads', 'public');
        }

        if ($request->hasFile('favicon_path')) {
            $settings->favicon_path = $request->file('favicon_path')->store('uploads', 'public');
        }

        $settings->save();

        return back()->with('ok', 'Configuración actualizada correctamente.');
    }
}

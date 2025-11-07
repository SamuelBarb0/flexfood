<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    // GET /r/{restaurante:slug}/settings
    public function edit(Restaurante $restaurante)
    {
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
            // Ojo: .ico a veces falla con "image", por eso NO usamos 'image' aquí.
            'favicon_path' => ['nullable', 'mimes:png,ico', 'max:512'],
            'notification_sound' => ['nullable', 'mimes:mp3,wav,ogg', 'max:1024'],
        ]);

        $settings = SiteSetting::firstOrNew([
            'restaurante_id' => $restaurante->id,
        ]);

        $settings->site_name      = $request->site_name;
        $settings->restaurante_id = $restaurante->id;

        // Rutas en public/ para desarrollo local
        $logoAbsDir    = public_path('images/logos');
        $faviconAbsDir = public_path('images/favicons');
        $soundAbsDir   = public_path('sounds');
        $logoWebDir    = 'images/logos';
        $faviconWebDir = 'images/favicons';
        $soundWebDir   = 'sounds';

        // Asegurar que existan los directorios
        File::ensureDirectoryExists($logoAbsDir, 0755, true);
        File::ensureDirectoryExists($faviconAbsDir, 0755, true);
        File::ensureDirectoryExists($soundAbsDir, 0755, true);

        // Subir LOGO (si viene)
        if ($request->hasFile('logo_path')) {
            $file = $request->file('logo_path');
            $ext  = strtolower($file->getClientOriginalExtension());
            $name = 'logo_rest_' . $restaurante->id . '_' . time() . '.' . $ext;

            // (Opcional) borrar anterior si estaba en la misma carpeta
            if (!empty($settings->logo_path)) {
                $oldAbs = public_path(ltrim($settings->logo_path, '/'));
                if (str_starts_with($settings->logo_path, $logoWebDir) && File::exists($oldAbs)) {
                    @File::delete($oldAbs);
                }
            }

            // Mover archivo
            $file->move($logoAbsDir, $name);

            // Guardar ruta web relativa
            $settings->logo_path = $logoWebDir . '/' . $name;
        }

        // Subir FAVICON (si viene)
        if ($request->hasFile('favicon_path')) {
            $file = $request->file('favicon_path');
            $ext  = strtolower($file->getClientOriginalExtension());
            $name = 'favicon_rest_' . $restaurante->id . '_' . time() . '.' . $ext;

            // (Opcional) borrar anterior si estaba en la misma carpeta
            if (!empty($settings->favicon_path)) {
                $oldAbs = public_path(ltrim($settings->favicon_path, '/'));
                if (str_starts_with($settings->favicon_path, $faviconWebDir) && File::exists($oldAbs)) {
                    @File::delete($oldAbs);
                }
            }

            // Mover archivo
            $file->move($faviconAbsDir, $name);

            // Guardar ruta web relativa
            $settings->favicon_path = $faviconWebDir . '/' . $name;
        }

        // Subir SONIDO DE NOTIFICACIÓN (si viene)
        if ($request->hasFile('notification_sound')) {
            $file = $request->file('notification_sound');
            $ext  = strtolower($file->getClientOriginalExtension());
            $name = 'notification_rest_' . $restaurante->id . '_' . time() . '.' . $ext;

            // (Opcional) borrar anterior si estaba en la misma carpeta
            if (!empty($restaurante->notification_sound_path)) {
                $oldAbs = public_path(ltrim($restaurante->notification_sound_path, '/'));
                if (str_starts_with($restaurante->notification_sound_path, $soundWebDir) && File::exists($oldAbs)) {
                    @File::delete($oldAbs);
                }
            }

            // Mover archivo
            $file->move($soundAbsDir, $name);

            // Guardar ruta web relativa en el modelo Restaurante
            $restaurante->notification_sound_path = $soundWebDir . '/' . $name;
            $restaurante->save();
        }

        $settings->save();

        return back()->with('ok', 'Configuración actualizada correctamente.');
    }
}

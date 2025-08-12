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
        ]);

        $settings = SiteSetting::firstOrNew([
            'restaurante_id' => $restaurante->id,
        ]);

        $settings->site_name      = $request->site_name;
        $settings->restaurante_id = $restaurante->id;

        // Rutas absolutas (servidor) y web (para asset())
        $logoAbsDir    = '/home/u194167774/domains/flexfood.es/public_html/images/logos';
        $faviconAbsDir = '/home/u194167774/domains/flexfood.es/public_html/images/favicons';
        $logoWebDir    = 'images/logos';
        $faviconWebDir = 'images/favicons';

        // Asegurar que existan los directorios
        File::ensureDirectoryExists($logoAbsDir, 0755, true);
        File::ensureDirectoryExists($faviconAbsDir, 0755, true);

        // Subir LOGO (si viene)
        if ($request->hasFile('logo_path')) {
            $file = $request->file('logo_path');
            $ext  = strtolower($file->getClientOriginalExtension());
            $name = 'logo_rest_' . $restaurante->id . '_' . time() . '.' . $ext;

            // (Opcional) borrar anterior si estaba en la misma carpeta
            if (!empty($settings->logo_path)) {
                $oldAbs = '/home/u194167774/domains/flexfood.es/public_html/' . ltrim($settings->logo_path, '/');
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
                $oldAbs = '/home/u194167774/domains/flexfood.es/public_html/' . ltrim($settings->favicon_path, '/');
                if (str_starts_with($settings->favicon_path, $faviconWebDir) && File::exists($oldAbs)) {
                    @File::delete($oldAbs);
                }
            }

            // Mover archivo
            $file->move($faviconAbsDir, $name);

            // Guardar ruta web relativa
            $settings->favicon_path = $faviconWebDir . '/' . $name;
        }

        $settings->save();

        return back()->with('ok', 'Configuración actualizada correctamente.');
    }
}

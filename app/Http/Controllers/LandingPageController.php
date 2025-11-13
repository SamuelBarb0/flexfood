<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\ContactMessage;
use App\Services\ImageService;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /* =========================
     *  LANDING
     * ========================= */

    // Vista pública (sin edición)
    public function show()
    {
        $data = optional(LandingPage::first())->data ?? [];
        return view('landing.edit', [
            'serverData' => $data,
            'canEdit'    => false,
        ]);
    }

    // Vista con edición
    public function edit()
    {
        $data = optional(LandingPage::first())->data ?? [];
        return view('landing.edit', [
            'serverData' => $data,
            'canEdit'    => true,
        ]);
    }

    // API: devolver JSON crudo
    public function data()
    {
        return response()->json(optional(LandingPage::first())->data ?? []);
    }

    // Guardar landing completa
    public function update(Request $request)
    {
        $incoming = $request->all();

        $page     = LandingPage::first() ?? new LandingPage();
        $existing = $page->data ?? [];

        // Protege la rama "terms" si no viene en el payload (evita borrarla sin querer)
        if (!array_key_exists('terms', $incoming) && isset($existing['terms'])) {
            $incoming['terms'] = $existing['terms'];
        }

        $page->data = $incoming;
        $page->save();

        return response()->json(['ok' => true]);
    }

    // Subir imágenes (landing) - Convertidas a WebP
    public function upload(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:4096'], // 4MB
        ]);

        // Ruta física en tu server
        $destino = '/home/u194167774/domains/flexfood.es/public_html/images/landing';

        // Convertir y guardar como WebP
        $nombreBase = $this->imageService->generarNombreUnico('landing_');
        $nombreWebp = $this->imageService->convertirYGuardarWebP(
            $request->file('image'),
            $destino,
            $nombreBase,
            calidad: 85,
            maxAncho: 1920 // Máximo Full HD para imágenes de landing
        );

        // URL pública
        $url = asset('images/landing/' . $nombreWebp);

        return response()->json(['url' => $url]);
    }

    // Contacto (landing)
    public function contact(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:50',
            'message' => 'required|string|min:10|max:5000',
        ]);

        // Guardar en BD
        ContactMessage::create($data);

        // (Opcional) enviar email…
        return response()->json(['ok' => true]);
    }

    /* =========================
     *  TÉRMINOS Y CONDICIONES
     *  (guardados dentro de data['terms'])
     * ========================= */

    // Vista pública
    public function terms()
    {
        $page = LandingPage::first();
        $data = $page->data ?? [];

        // Estructura por defecto si no existe
        $default = [
            'title'        => 'Términos y Condiciones',
            'intro'        => 'Estos términos regulan el uso de la plataforma FlexFood.',
            'sections'     => [
                ['heading' => '1. Uso del servicio',       'body' => 'La plataforma está destinada únicamente a...'],
                ['heading' => '2. Cuentas de usuario',      'body' => 'El administrador del restaurante es responsable...'],
                ['heading' => '3. Pagos y suscripciones',   'body' => 'Los planes pueden ser mensuales o anuales...'],
            ],
            'lastUpdated'     => now()->format('Y-m-d'),
            'lastUpdatedNote' => '',
            'seo' => [
                'title'       => 'Términos y Condiciones – FlexFood',
                'description' => 'Términos que rigen el uso de FlexFood.',
                'ogImage'     => '',
            ],
        ];

        $terms = array_merge($default, data_get($data, 'terms', []));

        return view('landing.terminos', [
            'serverData' => ['content' => $terms],
            'canEdit'    => false,
        ]);
    }

    // Vista con edición
    public function termsEdit()
    {
        $page = LandingPage::first();
        $data = $page->data ?? [];

        $default = [
            'title'        => 'Términos y Condiciones',
            'intro'        => 'Estos términos regulan el uso de la plataforma FlexFood.',
            'sections'     => [
                ['heading' => '1. Uso del servicio',       'body' => 'La plataforma está destinada únicamente a...'],
                ['heading' => '2. Cuentas de usuario',      'body' => 'El administrador del restaurante es responsable...'],
                ['heading' => '3. Pagos y suscripciones',   'body' => 'Los planes pueden ser mensuales o anuales...'],
            ],
            'lastUpdated'     => now()->format('Y-m-d'),
            'lastUpdatedNote' => '',
            'seo' => [
                'title'       => 'Términos y Condiciones – FlexFood',
                'description' => 'Términos que rigen el uso de FlexFood.',
                'ogImage'     => '',
            ],
        ];

        $terms = array_merge($default, data_get($data, 'terms', []));

        return view('landing.terminos', [
            'serverData' => ['content' => $terms],
            'canEdit'    => true,
        ]);
    }

    // Guardar SOLO la rama "terms" dentro de data
    public function termsUpdate(Request $request)
    {
        $validated = $request->validate([
            'content.title'                   => 'required|string|max:200',
            'content.intro'                   => 'nullable|string',
            'content.sections'                => 'array',
            'content.sections.*.heading'      => 'required|string|max:200',
            'content.sections.*.body'         => 'required|string',
            'content.lastUpdated'             => 'nullable|date',
            'content.lastUpdatedNote'         => 'nullable|string|max:200',
            'content.seo'                     => 'array',
            'content.seo.title'               => 'nullable|string|max:200',
            'content.seo.description'         => 'nullable|string|max:300',
            'content.seo.ogImage'             => 'nullable|string|max:1000',
        ]);

        $page = LandingPage::first() ?? new LandingPage();
        $data = $page->data ?? [];

        // Sobrescribe solo la rama "terms"
        data_set($data, 'terms', $validated['content']);

        $page->data = $data;
        $page->save();

        return response()->json(['ok' => true]);
    }

    // Subir OG Image (carpeta separada para términos) - Convertida a WebP
    public function termsUpload(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:4096'],
        ]);

        $destino = '/home/u194167774/domains/flexfood.es/public_html/images/terminos';

        // Convertir y guardar como WebP
        $nombreBase = $this->imageService->generarNombreUnico('terminos_');
        $nombreWebp = $this->imageService->convertirYGuardarWebP(
            $request->file('image'),
            $destino,
            $nombreBase,
            calidad: 85,
            maxAncho: 1200 // Tamaño típico de OG image
        );

        $url = asset('images/terminos/' . $nombreWebp);

        return response()->json(['url' => $url]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function show()
    {
        // vista pública (sin edición)
        $data = optional(LandingPage::first())->data ?? [];
        return view('landing.edit', [
            'serverData' => $data,
            'canEdit'    => false,
        ]);
    }

    public function edit()
    {
        // vista con edición (protege con auth si quieres)
        $data = optional(LandingPage::first())->data ?? [];
        return view('landing.edit', [
            'serverData' => $data,
            'canEdit'    => true,
        ]);
    }

    public function data()
    {
        return response()->json(optional(LandingPage::first())->data ?? []);
    }

    public function update(Request $request)
    {
        // todo lo que te mande el front lo guardas tal cual en JSON
        $page = LandingPage::first() ?? new LandingPage();
        $page->data = $request->all();
        $page->save();

        return response()->json(['ok' => true]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:4096'], // 4MB
        ]);

        // Ruta física en tu server
        $destino = '/home/u194167774/domains/flexfood.es/public_html/images/landing';

        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }

        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();
        $name = uniqid('landing_') . '.' . $ext;

        // Mover archivo al destino
        $file->move($destino, $name);

        // URL pública (sirviendo desde public_html/images/productos)
        $url = asset('images/landing/' . $name);

        return response()->json(['url' => $url]);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:50',
            'message' => 'required|string|min:10|max:5000',
        ]);

        // Guardar en BD
        $msg = ContactMessage::create($data);

        // (Opcional) enviar email
        // Mail::to(config('mail.from.address'))
        //     ->send(new \App\Mail\ContactMessageReceived($msg));

        return response()->json(['ok' => true]);
    }
}

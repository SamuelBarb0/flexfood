<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class MesaController extends Controller
{
    public function index()
    {
        $mesas = Mesa::orderBy('nombre')->get();
        return view('mesas.index', compact('mesas'));
    }

    public function crearAjax(Request $request)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        $nuevaCantidad = $request->cantidad;
        $mesasActuales = Mesa::orderBy('nombre')->get();
        $cantidadActual = $mesasActuales->count();

        // 🔴 Si hay más mesas de las que se desean, eliminamos las sobrantes
        if ($cantidadActual > $nuevaCantidad) {
            $sobrantes = $mesasActuales->slice($nuevaCantidad); // desde la posición deseada
            foreach ($sobrantes as $mesa) {
                // eliminar archivo QR si existe
                if ($mesa->codigo_qr && Storage::disk('public')->exists($mesa->codigo_qr)) {
                    Storage::disk('public')->delete($mesa->codigo_qr);
                }
                $mesa->delete();
            }
        }

        // 🟢 Si faltan mesas, las creamos
        if ($cantidadActual < $nuevaCantidad) {
            for ($i = $cantidadActual + 1; $i <= $nuevaCantidad; $i++) {
                $mesa = Mesa::create([
                    'nombre' => (string) $i,
                ]);

                $url = route('menu.publico', ['mesa_id' => $mesa->id]);
                $qrNombre = 'qr_mesa_' . $mesa->id . '.png';
                $qrPath = 'qrs/' . $qrNombre;

                Storage::disk('public')->put($qrPath, QrCode::format('png')->size(300)->generate($url));
                $mesa->update(['codigo_qr' => $qrPath]);
            }
        }

        // 🔁 Cargar todas las mesas actualizadas
        $mesas = Mesa::orderBy('nombre')->get();
        $datos = [];

        foreach ($mesas as $mesa) {
            $datos[] = [
                'nombre' => $mesa->nombre,
                'qr_url' => asset('storage/' . $mesa->codigo_qr),
            ];
        }

        return response()->json([
            'message' => 'Cantidad de mesas actualizada.',
            'mesas' => $datos,
        ]);
    }


    public function vistaImprimirHoja()
    {
        $mesas = Mesa::orderBy('nombre')->get();
        return view('mesas.imprimir-hoja', compact('mesas'));
    }
}

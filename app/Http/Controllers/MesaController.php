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

        // 🔴 Eliminar mesas sobrantes
        if ($cantidadActual > $nuevaCantidad) {
            $sobrantes = $mesasActuales->slice($nuevaCantidad);
            foreach ($sobrantes as $mesa) {
                $qrPath = '/home/u194167774/domains/flexfood.es/public_html/images/qrmesas/' . $mesa->codigo_qr;
                if ($mesa->codigo_qr && file_exists($qrPath)) {
                    unlink($qrPath);
                }
                $mesa->delete();
            }
        }

        // 🟢 Crear mesas faltantes
        if ($cantidadActual < $nuevaCantidad) {
            for ($i = $cantidadActual + 1; $i <= $nuevaCantidad; $i++) {
                $mesa = Mesa::create([
                    'nombre' => (string) $i,
                ]);

                $url = route('menu.publico', ['mesa_id' => $mesa->id]);
                $qrNombre = 'qr_mesa_' . $mesa->id . '.png';
                $carpeta = '/home/u194167774/domains/flexfood.es/public_html/images/qrmesas/';

                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }

                file_put_contents($carpeta . $qrNombre, QrCode::format('png')->size(300)->generate($url));

                $mesa->update(['codigo_qr' => $qrNombre]);
            }
        }

        // 🔁 Cargar todas las mesas actualizadas
        $mesas = Mesa::orderBy('nombre')->get();
        $datos = [];

        foreach ($mesas as $mesa) {
            $datos[] = [
                'nombre' => $mesa->nombre,
                'qr_url' => asset('images/qrmesas/' . $mesa->codigo_qr),
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

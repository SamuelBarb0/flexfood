<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MesaController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        return view('mesas.index', compact('mesas', 'restaurante'));
    }

    public function crearAjax(Restaurante $restaurante, Request $request)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:0',
        ]);

        $nuevaCantidad  = (int) $request->cantidad;

        // Todas las mesas del restaurante, ordenadas por nombre (número visible)
        $mesasActuales = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $cantidadActual = $mesasActuales->count();

        // 🔴 Eliminar mesas sobrantes (solo de ESTE restaurante)
        if ($cantidadActual > $nuevaCantidad) {
            $sobrantes = $mesasActuales->slice($nuevaCantidad);
            foreach ($sobrantes as $mesa) {
                $qrPath = '/home/u194167774/domains/flexfood.es/public_html/images/qrmesas/' . $mesa->codigo_qr;
                if ($mesa->codigo_qr && file_exists($qrPath)) {
                    @unlink($qrPath);
                }
                $mesa->delete();
            }
        }

        // 🟢 Crear mesas faltantes (solo para ESTE restaurante)
        if ($cantidadActual < $nuevaCantidad) {
            for ($i = $cantidadActual + 1; $i <= $nuevaCantidad; $i++) {
                // Unicidad por (restaurante_id, nombre)
                $mesa = Mesa::firstOrCreate(
                    ['restaurante_id' => $restaurante->id, 'nombre' => (int) $i],
                    ['codigo_qr' => null]
                );

                // URL pública con el restaurante + mesa_id (ID real)
                $url = route('menu.publico', [
                    'restaurante' => $restaurante->slug,
                    'mesa_id'     => $mesa->id,
                ]);

                $qrNombre = 'qr_mesa_' . $restaurante->id . '_' . $mesa->id . '.png';
                $carpeta  = '/home/u194167774/domains/flexfood.es/public_html/images/qrmesas/';

                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }

                file_put_contents(
                    $carpeta . $qrNombre,
                    QrCode::format('png')->size(300)->generate($url)
                );

                if ($mesa->codigo_qr !== $qrNombre) {
                    $mesa->update(['codigo_qr' => $qrNombre]);
                }
            }
        }

        // 🔁 Responder solo con mesas de ESTE restaurante
        //     -> ordenadas por nombre, pero enviando también el ID
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $datos = $mesas->map(fn($m) => [
            'id'      => (int) $m->id,                     // <-- ID real para usar en el front
            'nombre'  => (int) $m->nombre,                 // número visible
            'qr_file' => $m->codigo_qr,                    // por si necesitas el nombre del archivo
            'qr_url'  => asset('images/qrmesas/' . $m->codigo_qr),
        ])->values();

        return response()->json([
            'message' => 'Cantidad de mesas actualizada.',
            'mesas'   => $datos,
        ]);
    }


    public function vistaImprimirHoja(Restaurante $restaurante)
    {
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        return view('mesas.imprimir-hoja', compact('mesas', 'restaurante'));
    }
}

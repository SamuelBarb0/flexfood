<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MesaController extends Controller
{
    /**
     * Resuelve límites a partir del plan del restaurante.
     * Usa config('planes_restaurante') si existe; si no, aplica un fallback.
     */
    private function planFor(Restaurante $restaurante): array
    {
        $key = $restaurante->plan ?: 'legacy';

        // Preferir configuración centralizada
        $cfg = config('planes_restaurante');
        if (is_array($cfg) && isset($cfg[$key])) {
            return $cfg[$key]; // debe traer ['max_qr' => ...] entre otros
        }

        // Fallback por si no cargaste el config:
        return match ($key) {
            'basic'    => ['max_qr' => 15],
            'advanced' => ['max_qr' => null],
            default    => ['max_qr' => null], // legacy / ilimitado
        };
    }

    public function index(Restaurante $restaurante)
    {
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        // (Opcional) pasa contadores a la vista para mostrar banner
        $plan       = $this->planFor($restaurante);
        $maxQr      = $plan['max_qr'] ?? null;
        $qrActuales = $mesas->count();

        return view('mesas.index', compact('mesas', 'restaurante', 'maxQr', 'qrActuales'));
    }

    public function crearAjax(Restaurante $restaurante, Request $request)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:0',
        ]);

        $plan          = $this->planFor($restaurante);
        $nuevaCantidad = (int) $request->cantidad;

        // Todas las mesas del restaurante (ordenadas por nombre)
        $mesasActuales = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $cantidadActual = $mesasActuales->count();

        // 🔒 Límite por plan: si piden más del tope, NO cambiamos nada y devolvemos 422
        if (!is_null($plan['max_qr']) && $nuevaCantidad > $plan['max_qr']) {
            $datos = $mesasActuales->map(fn($m) => [
                'id'      => (int) $m->id,
                'nombre'  => (int) $m->nombre,
                'qr_file' => $m->codigo_qr,
                'qr_url'  => asset('images/qrmesas/' . $m->codigo_qr),
            ])->values();

            return response()->json([
                'message' => "Has alcanzado el límite de códigos QR para tu plan ({$plan['max_qr']}).",
                'limit'   => $plan['max_qr'],
                'mesas'   => $datos,
            ], 422);
        }

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
                $mesa = Mesa::firstOrCreate(
                    ['restaurante_id' => $restaurante->id, 'nombre' => (int) $i],
                    ['codigo_qr' => null]
                );

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
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $datos = $mesas->map(fn($m) => [
            'id'      => (int) $m->id,
            'nombre'  => (int) $m->nombre,
            'qr_file' => $m->codigo_qr,
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

    /**
     * Fusionar múltiples mesas bajo una mesa principal
     */
    public function fusionar(Request $request, Restaurante $restaurante)
    {
        $validated = $request->validate([
            'mesa_principal_id' => 'required|exists:mesas,id',
            'mesas_secundarias' => 'required|array|min:1',
            'mesas_secundarias.*' => 'exists:mesas,id',
        ]);

        $principal = Mesa::findOrFail($validated['mesa_principal_id']);

        // Validar que la mesa principal pertenece al restaurante
        if ($principal->restaurante_id !== $restaurante->id) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa principal no pertenece a este restaurante'
            ], 403);
        }

        // Actualizar mesas secundarias
        Mesa::whereIn('id', $validated['mesas_secundarias'])
            ->where('restaurante_id', $restaurante->id)
            ->update(['mesa_grupo_id' => $principal->id]);

        return response()->json([
            'success' => true,
            'message' => 'Mesas fusionadas correctamente',
            'mesa_principal' => $principal->nombre,
        ]);
    }

    /**
     * Desfusionar una mesa o grupo de mesas
     */
    public function desfusionar(Restaurante $restaurante, Mesa $mesa)
    {
        // Validar que la mesa pertenece al restaurante
        if ($mesa->restaurante_id !== $restaurante->id) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no pertenece a este restaurante'
            ], 403);
        }

        // Si es mesa principal, desfusionar todas las secundarias
        Mesa::where('mesa_grupo_id', $mesa->id)
            ->update(['mesa_grupo_id' => null]);

        // Desfusionar la mesa actual si está fusionada
        $mesa->mesa_grupo_id = null;
        $mesa->save();

        return response()->json([
            'success' => true,
            'message' => 'Mesa(s) desfusionada(s) correctamente',
        ]);
    }
}

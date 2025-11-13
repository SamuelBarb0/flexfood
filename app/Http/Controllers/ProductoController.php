<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Adicion;
use App\Models\Restaurante;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Obtiene límites y políticas desde el plan del restaurante.
     * Usa config('planes_restaurante') si existe; de lo contrario aplica fallback.
     */
    private function planFor(Restaurante $restaurante): array
    {
        $key = $restaurante->plan ?: 'legacy';

        // Si tienes config centralizada:
        $cfg = config('planes_restaurante');
        if (is_array($cfg) && isset($cfg[$key])) {
            return $cfg[$key]; // ['only_photos', 'max_platos', 'max_qr', 'max_perfiles']
        }

        // Fallback si no cargaste la config
        return match ($key) {
            'basic'    => ['only_photos' => true,  'max_platos' => 50,  'max_qr' => 15, 'max_perfiles' => 3],
            'advanced' => ['only_photos' => false, 'max_platos' => null,'max_qr' => null, 'max_perfiles' => null],
            default    => ['only_photos' => false, 'max_platos' => null,'max_qr' => null,'max_perfiles' => null], // legacy
        };
    }

    public function create(Restaurante $restaurante)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)->get();
        $adiciones  = Adicion::where('restaurante_id', $restaurante->id)->get();

        $plan = $this->planFor($restaurante);
        $soloFotos = $plan['only_photos'] ?? false;

        return view('productos.create', compact('categorias', 'adiciones', 'restaurante', 'soloFotos'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $plan = $this->planFor($restaurante);

        // 🔒 Límite de platos por plan (independiente de categorías)
        if (!is_null($plan['max_platos'])) {
            $totalActual = Producto::where('restaurante_id', $restaurante->id)->count();
            if ($totalActual >= $plan['max_platos']) {
                return back()
                    ->with('error', 'Has alcanzado el máximo de platos permitidos para tu plan.')
                    ->withInput();
            }
        }

        $request->validate([
            'nombre'       => ['required','string','max:255'],
            'descripcion'  => ['nullable','string'],
            'precio'       => ['required','numeric'],
            'categoria_id' => [
                'required',
                Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
            ],
            // “Solo fotos”: imagen obligatoria y video prohibido
            'imagen'       => ($plan['only_photos'] ?? false)
                                ? ['required','image','max:2048']
                                : ['nullable','image','max:2048'],
            'video'        => ($plan['only_photos'] ?? false)
                                ? ['prohibited']
                                : ['nullable','mimes:mp4,webm,avi,mov'], // ← SIN max
            'adiciones'    => ['array'],
            'adiciones.*'  => [
                Rule::exists('adiciones','id')->where('restaurante_id', $restaurante->id),
            ],
        ]);

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible']      = $request->has('disponible');
        $data['restaurante_id']  = $restaurante->id;

        // Ruta de producción
        $rutaPublica = '/home/u194167774/domains/flexfood.es/public_html/images/productos';

        // Guardar imagen (convertida a WebP)
        if ($request->hasFile('imagen')) {
            $nombreBase = $this->imageService->generarNombreUnico('img_');
            $nombreImagen = $this->imageService->convertirYGuardarWebP(
                $request->file('imagen'),
                $rutaPublica,
                $nombreBase,
                calidad: 85,
                maxAncho: 2000 // Limitar ancho a 2000px para optimizar
            );
            $data['imagen'] = 'productos/' . $nombreImagen;
        }

        // Guardar video (solo si el plan lo permite)
        if (!($plan['only_photos'] ?? false) && $request->hasFile('video')) {
            $video       = $request->file('video');
            $nombreVideo = uniqid('video_') . '.' . $video->getClientOriginalExtension();
            $video->move($rutaPublica, $nombreVideo);
            $data['video'] = 'productos/' . $nombreVideo;
        }

        $producto = Producto::create($data);

        // Sincronizar adiciones SOLO del restaurante actual
        $ids = Adicion::where('restaurante_id', $restaurante->id)
            ->whereIn('id', $request->input('adiciones', []))
            ->pluck('id');
        $producto->adiciones()->sync($ids);

        return redirect()->route('menu.index', $restaurante)->with('success', 'Producto creado correctamente.');
    }

    public function edit(Restaurante $restaurante, Producto $producto)
    {
        abort_unless($producto->restaurante_id === $restaurante->id, 403);

        $categorias = Categoria::where('restaurante_id', $restaurante->id)->get();
        $adiciones  = Adicion::where('restaurante_id', $restaurante->id)
            ->whereHas('categorias', function ($q) use ($producto) {
                $q->where('categorias.id', $producto->categoria_id);
            })->get();

        $plan = $this->planFor($restaurante);
        $soloFotos = $plan['only_photos'] ?? false;

        return view('productos.edit', compact('producto', 'categorias', 'adiciones', 'restaurante', 'soloFotos'));
    }

    public function update(Request $request, Restaurante $restaurante, Producto $producto)
    {
        abort_unless($producto->restaurante_id === $restaurante->id, 403);
        Log::info('Iniciando actualización del producto', ['producto_id' => $producto->id]);

        $plan = $this->planFor($restaurante);

        try {
            $request->validate([
                'nombre'       => ['required','string','max:255'],
                'descripcion'  => ['nullable','string'],
                'precio'       => ['required','numeric'],
                'categoria_id' => [
                    'required',
                    Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
                ],
                // En update la imagen puede venir o no
                'imagen'       => ($plan['only_photos'] ?? false)
                                    ? ['sometimes','image','max:2048']
                                    : ['nullable','image','max:2048'],
                // Video prohibido si es solo-fotos / permitido sin límite de tamaño si no
                'video'        => ($plan['only_photos'] ?? false)
                                    ? ['prohibited']
                                    : ['nullable','mimes:mp4,webm,avi,mov'], // ← SIN max
                'adiciones'    => ['array'],
                'adiciones.*'  => [
                    Rule::exists('adiciones','id')->where('restaurante_id', $restaurante->id),
                ],
            ]);
            Log::info('Validación pasada correctamente');
        } catch (ValidationException $e) {
            Log::error('Error de validación', [
                'errores' => $e->errors(),
                'mensaje' => $e->getMessage(),
            ]);
            return back()->withErrors($e->errors())->withInput();
        }

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible'] = $request->has('disponible');

        Log::info('Datos recibidos para actualización', $data);

        // Ruta de producción
        $rutaPublica = '/home/u194167774/domains/flexfood.es/public_html/images/productos';

        // Imagen (reemplazo con conversión a WebP)
        if ($request->hasFile('imagen')) {
            Log::info('Nueva imagen detectada');

            // Eliminar imagen anterior si existe
            if ($producto->imagen) {
                $rutaAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->imagen;
                $this->imageService->eliminarImagen($rutaAnterior);
                Log::info('Imagen anterior eliminada');
            }

            try {
                $nombreBase = $this->imageService->generarNombreUnico('img_');
                $nombreImagen = $this->imageService->convertirYGuardarWebP(
                    $request->file('imagen'),
                    $rutaPublica,
                    $nombreBase,
                    calidad: 85,
                    maxAncho: 2000
                );
                $data['imagen'] = 'productos/' . $nombreImagen;
                Log::info('Imagen subida y convertida a WebP', ['ruta' => $data['imagen']]);
            } catch (\Exception $e) {
                Log::error('Error al subir imagen', ['error' => $e->getMessage()]);
            }
        }

        // Política de VIDEO según plan
        if ($plan['only_photos'] ?? false) {
            // Si tenía video, lo borramos y dejamos null
            if ($producto->video) {
                $rutaVideoAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->video;
                if (file_exists($rutaVideoAnterior)) {
                    @unlink($rutaVideoAnterior);
                    Log::info('Video anterior eliminado por política SOLO fotos');
                } else {
                    Log::warning('Video anterior no encontrado', ['ruta' => $rutaVideoAnterior]);
                }
            }
            $data['video'] = null; // aseguramos null
        } else {
            // Plan permite video: si suben nuevo, reemplazamos
            if ($request->hasFile('video')) {
                Log::info('Nuevo video detectado');

                if ($producto->video) {
                    $rutaVideoAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->video;
                    if (file_exists($rutaVideoAnterior)) {
                        @unlink($rutaVideoAnterior);
                        Log::info('Video anterior eliminado');
                    } else {
                        Log::warning('Video anterior no encontrado', ['ruta' => $rutaVideoAnterior]);
                    }
                }

                try {
                    $video       = $request->file('video');
                    $nombreVideo = uniqid('video_') . '.' . $video->getClientOriginalExtension();
                    $video->move($rutaPublica, $nombreVideo);
                    $data['video'] = 'productos/' . $nombreVideo;
                    Log::info('Video subido', ['ruta' => $data['video']]);
                } catch (\Exception $e) {
                    Log::error('Error al subir video', ['error' => $e->getMessage()]);
                }
            }
        }

        try {
            $producto->update($data);
            Log::info('Producto actualizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el producto', ['error' => $e->getMessage()]);
        }

        try {
            $ids = Adicion::where('restaurante_id', $restaurante->id)
                ->whereIn('id', $request->input('adiciones', []))
                ->pluck('id');
            $producto->adiciones()->sync($ids);
            Log::info('Adiciones sincronizadas');
        } catch (\Exception $e) {
            Log::error('Error al sincronizar adiciones', ['error' => $e->getMessage()]);
        }

        return redirect()->route('menu.index', $restaurante)->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Restaurante $restaurante, Producto $producto)
    {
        abort_unless($producto->restaurante_id === $restaurante->id, 403);

        try {
            // Eliminar imagen
            if ($producto->imagen) {
                $ruta = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->imagen;
                if (file_exists($ruta)) {
                    @unlink($ruta);
                }
            }

            // Eliminar video
            if ($producto->video) {
                $rutaVideo = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->video;
                if (file_exists($rutaVideo)) {
                    @unlink($rutaVideo);
                }
            }

            // Eliminar relaciones con adiciones
            $producto->adiciones()->detach();

            // Eliminar producto
            $producto->delete();

            return redirect()->route('menu.index', $restaurante)->with('success', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('menu.index', $restaurante)->with('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
    }
}

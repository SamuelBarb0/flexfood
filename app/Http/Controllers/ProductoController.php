<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Adicion;
use App\Models\Restaurante;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function create(Restaurante $restaurante)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)->get();
        $adiciones  = Adicion::where('restaurante_id', $restaurante->id)->get();

        return view('productos.create', compact('categorias', 'adiciones', 'restaurante'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $request->validate([
            'nombre'       => ['required','string','max:255'],
            'descripcion'  => ['nullable','string'],
            'precio'       => ['required','numeric'],
            'categoria_id' => [
                'required',
                Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
            ],
            'imagen'       => ['nullable','image','max:2048'],
            'video'        => ['nullable','mimes:mp4,webm,avi,mov','max:20480'],
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
        if (!file_exists($rutaPublica)) {
            mkdir($rutaPublica, 0755, true);
        }

        // Guardar imagen
        if ($request->hasFile('imagen')) {
            $imagen       = $request->file('imagen');
            $nombreImagen = uniqid('img_') . '.' . $imagen->getClientOriginalExtension();
            $imagen->move($rutaPublica, $nombreImagen);
            $data['imagen'] = 'productos/' . $nombreImagen;
        }

        // Guardar video
        if ($request->hasFile('video')) {
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

        return view('productos.edit', compact('producto', 'categorias', 'adiciones', 'restaurante'));
    }

    public function update(Request $request, Restaurante $restaurante, Producto $producto)
    {
        abort_unless($producto->restaurante_id === $restaurante->id, 403);
        Log::info('Iniciando actualización del producto', ['producto_id' => $producto->id]);

        try {
            $request->validate([
                'nombre'       => ['required','string','max:255'],
                'descripcion'  => ['nullable','string'],
                'precio'       => ['required','numeric'],
                'categoria_id' => [
                    'required',
                    Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
                ],
                'imagen'       => ['nullable','image','max:2048'],
                'video'        => ['nullable','mimes:mp4,webm,avi,mov','max:20480'],
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

        // Imagen
        if ($request->hasFile('imagen')) {
            Log::info('Nueva imagen detectada');

            if ($producto->imagen) {
                $rutaAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->imagen;
                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                    Log::info('Imagen anterior eliminada');
                } else {
                    Log::warning('Imagen anterior no encontrada', ['ruta' => $rutaAnterior]);
                }
            }

            try {
                $imagen       = $request->file('imagen');
                $nombreImagen = uniqid('img_') . '.' . $imagen->getClientOriginalExtension();
                $imagen->move($rutaPublica, $nombreImagen);
                $data['imagen'] = 'productos/' . $nombreImagen;
                Log::info('Imagen subida', ['ruta' => $data['imagen']]);
            } catch (\Exception $e) {
                Log::error('Error al subir imagen', ['error' => $e->getMessage()]);
            }
        }

        // Video
        if ($request->hasFile('video')) {
            Log::info('Nuevo video detectado');

            if ($producto->video) {
                $rutaVideoAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->video;
                if (file_exists($rutaVideoAnterior)) {
                    unlink($rutaVideoAnterior);
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

        $producto->delete();

        return redirect()->route('menu.index', $restaurante)->with('success', 'Producto eliminado.');
    }
}

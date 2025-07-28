<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Adicion;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function create()
    {
        $categorias = Categoria::all();
        $adiciones = Adicion::all();
        return view('productos.create', compact('categorias', 'adiciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|max:2048',
            'adiciones' => 'array',
            'adiciones.*' => 'exists:adiciones,id',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible'] = $request->has('disponible');

        // Guardar imagen
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $nombreImagen = uniqid('producto_') . '.' . $imagen->getClientOriginalExtension();
            $ruta = '/home/u194167774/domains/flexfood.es/public_html/images/productos';

            if (!file_exists($ruta)) {
                mkdir($ruta, 0755, true);
            }

            $imagen->move($ruta, $nombreImagen);
            $data['imagen'] = 'productos/' . $nombreImagen;
        }

        $producto = Producto::create($data);

        // Sincronizar adiciones
        if ($request->filled('adiciones')) {
            $producto->adiciones()->sync($request->input('adiciones'));
        }

        return redirect()->route('menu.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::all();
        $adiciones = Adicion::whereHas('categorias', function ($q) use ($producto) {
            $q->where('categorias.id', $producto->categoria_id);
        })->get();

        return view('productos.edit', compact('producto', 'categorias', 'adiciones'));
    }


    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|max:2048',
            'adiciones' => 'array',
            'adiciones.*' => 'exists:adiciones,id',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible'] = $request->has('disponible');

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                $rutaAnterior = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->imagen;
                if (file_exists($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }

            $imagen = $request->file('imagen');
            $nombreImagen = uniqid('producto_') . '.' . $imagen->getClientOriginalExtension();
            $ruta = '/home/u194167774/domains/flexfood.es/public_html/images/productos';

            if (!file_exists($ruta)) {
                mkdir($ruta, 0755, true);
            }

            $imagen->move($ruta, $nombreImagen);
            $data['imagen'] = 'productos/' . $nombreImagen;
        }

        $producto->update($data);

        // Sincronizar adiciones
        $producto->adiciones()->sync($request->input('adiciones', []));

        return redirect()->route('menu.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->imagen) {
            $ruta = '/home/u194167774/domains/flexfood.es/public_html/images/' . $producto->imagen;
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }

        $producto->delete();

        return redirect()->route('menu.index')->with('success', 'Producto eliminado.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function create()
    {
        $categorias = Categoria::all();
        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible'] = $request->has('disponible');

        // Guardar imagen en carpeta física
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

        Producto::create($data);

        return redirect()->route('menu.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::all();
        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'categoria_id' => 'required|exists:categorias,id',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['nombre', 'descripcion', 'precio', 'categoria_id']);
        $data['disponible'] = $request->has('disponible');

        if ($request->hasFile('imagen')) {
            // Eliminar anterior si existe
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

        return redirect()->route('menu.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        // Eliminar imagen física si existe
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

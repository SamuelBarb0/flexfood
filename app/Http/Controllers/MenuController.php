<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Models\Adicion;

class MenuController extends Controller
{
    public function index()
    {
        $categorias = Categoria::with('productos')->get();
        $adiciones = Adicion::all(); // Asegúrate de importar el modelo arriba

        return view('menu.index', compact('categorias', 'adiciones'));
    }


    public function publico()
    {
        $categorias = Categoria::with('productos')->get();
        $adiciones = Adicion::all(); // importar modelo si no está
        return view('menu.menupublico', compact('categorias', 'adiciones'));
    }

    public function publicoConMesa($mesa_id)
    {
        $categorias = Categoria::with('productos')->get();
        $adiciones = Adicion::all(); // importar modelo si no está
        return view('menu.menupublico', compact('categorias', 'adiciones', 'mesa_id'));
    }
}

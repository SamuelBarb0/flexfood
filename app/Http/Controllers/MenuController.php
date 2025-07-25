<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        // Obtiene todas las categorías con sus productos relacionados
        $categorias = Categoria::with('productos')->get();

        return view('menu.index', compact('categorias'));
    }

    public function publico()
    {
        $categorias = Categoria::with('productos')->get();
        return view('menu.menupublico', compact('categorias'));
    }

    public function publicoConMesa($mesa_id)
    {
        $categorias = Categoria::with('productos')->get();
        return view('menu.menupublico', compact('categorias', 'mesa_id'));
    }
}

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
}

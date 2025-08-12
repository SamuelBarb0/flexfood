<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('current_restaurante_id')) {
    function current_restaurante_id()
    {
        return Auth::check() ? Auth::user()->restaurante_id : null;
    }
}
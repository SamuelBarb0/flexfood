<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VeriFactu API URL
    |--------------------------------------------------------------------------
    |
    | URL base de la API de VeriFactu
    |
    */

    'api_url' => env('VERIFACTU_API_URL', 'https://api.verifacti.com'),

    /*
    |--------------------------------------------------------------------------
    | Modo de Prueba
    |--------------------------------------------------------------------------
    |
    | Indica si se están enviando facturas en modo de prueba (true) o producción (false)
    | En la API se envía como test_production: 't' (test) o 'p' (production)
    |
    */

    'test_mode' => env('VERIFACTU_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Credenciales por Defecto (Opcional)
    |--------------------------------------------------------------------------
    |
    | Puedes configurar credenciales por defecto aquí, aunque se recomienda
    | almacenarlas por restaurante en la base de datos
    |
    */

    'default_username' => env('VERIFACTU_USERNAME', null),
    'default_api_key' => env('VERIFACTU_API_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Timeout de Requests
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo de espera para las peticiones HTTP en segundos
    |
    */

    'timeout' => env('VERIFACTU_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | IVA por Defecto para Restauración
    |--------------------------------------------------------------------------
    |
    | Porcentaje de IVA aplicable a servicios de hostelería y restauración
    |
    */

    'iva_restauracion' => 10, // 10% según normativa española

];

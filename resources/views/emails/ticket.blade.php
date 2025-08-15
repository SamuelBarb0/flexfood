<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: monospace, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New"; font-size: 12px; }
    .ticket { width: 300px; margin: auto; border: 1px dashed #ccc; padding: 10px; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .list { list-style: none; padding: 0; margin: 0; }
    .list li { margin-bottom: 3px; }
    .row { display: flex; justify-content: space-between; }
    .line { border-top: 1px dashed #bbb; margin: 6px 0; }
</style>
</head>
<body>
<div class="ticket">
    <div class="center">
        <p class="bold">{{ $orden->restaurante->nombre ?? 'Restaurante' }}</p>
        <p>Recibo Mesa {{ $orden->mesa->nombre ?? 'No definida' }}</p>
        <p>Fecha: {{ optional($orden->created_at)->format('d/m/Y H:i') }}</p>
    </div>

    <div class="line"></div>

    <div class="bold row">
        <span>Cant.</span>
        <span>Artículo</span>
        <span>Total</span>
    </div>

    <div class="line"></div>

    @php
        $subtotal = 0.0;
    @endphp

    @foreach ((array) $orden->productos as $producto)
        @php
            $precioBase = (float) ($producto['precio_base'] ?? ($producto['precio'] ?? 0));
            $cantidad   = (int)   ($producto['cantidad'] ?? 1);
            $adiciones  = collect($producto['adiciones'] ?? []);
            $adicionesTotal = (float) $adiciones->sum('precio');
            $totalLinea = ($precioBase + $adicionesTotal) * $cantidad;
            $subtotal += $totalLinea;
        @endphp

        <div class="row" style="align-items: flex-start;">
            <span>{{ $cantidad }}</span>
            <span style="flex:1; text-align:center; padding: 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $producto['nombre'] ?? 'Producto' }}
            </span>
            <span>{{ number_format($totalLinea, 2, ',', '.') }}</span>
        </div>

        @if ($adiciones->isNotEmpty())
            <ul class="list" style="margin-left: 18px; color:#666;">
                @foreach ($adiciones as $adic)
                    <li>+ {{ $adic['nombre'] ?? 'Extra' }} (+{{ number_format((float)($adic['precio'] ?? 0), 2, ',', '.') }})</li>
                @endforeach
            </ul>
        @endif
    @endforeach

    <div class="line"></div>

    @php
        $iva = $subtotal * 0.10; // 10%
        $totalConIva = $subtotal + $iva;
    @endphp

    <div class="row">
        <span>Subtotal</span>
        <span>{{ number_format($subtotal, 2, ',', '.') }} €</span>
    </div>
    <div class="row">
        <span>IVA (10%)</span>
        <span>{{ number_format($iva, 2, ',', '.') }} €</span>
    </div>
    <div class="row bold" style="margin-top: 4px;">
        <span>TOTAL</span>
        <span>{{ number_format($totalConIva, 2, ',', '.') }} €</span>
    </div>

    <p class="center" style="margin-top: 10px; color: gray;">¡Gracias por su visita!</p>
</div>
</body>
</html>

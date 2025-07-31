@if(is_array($orden->productos))
    <ul class="list-disc ml-5 text-gray-700 text-sm">
        @foreach($orden->productos as $producto)
            <li class="mb-1 break-words">
                <strong>{{ $producto['nombre'] }}</strong> - {{ $producto['cantidad'] }} uds. - {{ number_format($producto['precio_base'], 2) }} €
                @if(!empty($producto['adiciones']))
                    <ul class="ml-4 list-square text-xs text-gray-500">
                        @foreach($producto['adiciones'] as $adicion)
                            <li>{{ $adicion['nombre'] }} (+{{ number_format((float) $adicion['precio'], 2) }} €)</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="text-sm text-gray-500">Sin productos registrados.</p>
@endif

<div class="relative {{ $bg }} rounded-lg p-4 text-center shadow-sm cursor-pointer transition-all"
     :class="{'ring-4 ring-purple-500': modoFusion && esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))}"
     @click="modoFusion ? toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null)) : clickMesa(
         @js($mesa['numero'] ?? null),
         @js($estadoTexto),
         @js($mesa['cuenta'] ?? []),
         @js($mesa['orden_id'] ?? null),
         @js($mesa['id'] ?? ($mesa['mesa_id'] ?? null))
     )">

    {{-- Checkbox para modo fusión --}}
    <div x-show="modoFusion" class="absolute top-1 left-1">
        <input type="checkbox"
               :checked="esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))"
               class="w-5 h-5 rounded border-2 border-white"
               @click.stop="toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null))">
    </div>

    <div class="text-2xl font-bold">{{ $mesa['numero'] ?? '-' }}</div>

    <div class="text-sm font-semibold mb-1 capitalize">{{ $estadoTexto }}</div>
    <div class="text-sm">{{ $mesa['tiempo'] ?? '-' }}</div>
    <div class="text-md font-bold mt-1">
        {{ (($mesa['total'] ?? 0) > 0) ? number_format($mesa['total'], 2, ',', '.') . ' €' : '- €' }}
    </div>
</div>

{{-- Vista de Videos estilo TikTok --}}
<div
    x-data="{
        ...scrollSpyCategorias(),
        alturaDisponible: window.innerHeight,
    }"
    x-init="
        init();
        window.addEventListener('resize', () => alturaDisponible = window.innerHeight)
    "
    x-show="mostrarVideos"
    @scroll="onScroll"
    x-effect="mostrarVideos && $nextTick(() => onScroll())"
    class="fixed inset-0 z-50 bg-transparent overflow-y-auto snap-y snap-mandatory scroll-smooth"
    :style="'height: ' + alturaDisponible + 'px'"
    id="contenedorVideos"
>


{{-- Carrusel de categorías --}}
@php
    $categoriasConProductos = $categorias->filter(fn($cat) =>
        $cat->productos->where('disponible', true)->count() > 0
    );
    $tieneCarruselVideos = $categoriasConProductos->count() > 3;
@endphp

<div class="sticky top-0 z-[60] bg-transparent py-4 px-4">
    @if($tieneCarruselVideos)
        <div class="relative">
            <div class="overflow-x-auto scrollbar-hide">
                <div class="flex gap-2 px-4">
                    @foreach ($categoriasConProductos as $categoria)
                        <a href="#"
                           @click.prevent="scrollToCategoria('{{ $categoria->id }}')"
                           :class="categoriaActiva === '{{ $categoria->id }}'
                               ? 'bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white shadow-lg shadow-[#3CB28B]/30 border border-[#3CB28B]/50'
                               : 'bg-gray-200 text-gray-800 hover:bg-[#3CB28B]/80 hover:text-white border border-transparent'"
                           class="flex-shrink-0 w-[100px] text-center px-2 py-2 rounded-full text-xs font-medium transition-all duration-300 hover:scale-105 hover:shadow-md whitespace-nowrap overflow-hidden text-ellipsis">
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="flex justify-center gap-2 flex-wrap px-2">
            @foreach ($categoriasConProductos as $categoria)
                <a href="#"
                   @click.prevent="scrollToCategoria('{{ $categoria->id }}')"
                   :class="categoriaActiva === '{{ $categoria->id }}'
                           ? 'bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white shadow-lg shadow-[#3CB28B]/30 border-[#3CB28B]/50'
                           : 'bg-white/10 text-white/90 hover:bg-gradient-to-r hover:from-[#3CB28B]/80 hover:to-[#2A9C75]/80 border-white/20'"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 border backdrop-blur-sm hover:scale-105 hover:shadow-md">
                    {{ $categoria->nombre }}
                </a>
            @endforeach
        </div>
    @endif
</div>



{{-- Productos por categoría --}}
@foreach ($categorias as $categoria)
    @php
        $productosConMediaDisponibles = $categoria->productos->filter(fn ($p) =>
            ($p->video || $p->imagen) && $p->disponible == true
        );
    @endphp

    @if ($productosConMediaDisponibles->count())
        <div id="categoria-{{ $categoria->id }}">
            @foreach ($productosConMediaDisponibles as $producto)
                <div class="w-full flex flex-col justify-between snap-start relative bg-black" :style="`height: ${alturaDisponible}px`">
                    
                    {{-- VIDEO o IMAGEN --}}
                    @if ($producto->video)
                        <video
                            src="{{ asset('images/' . $producto->video) }}"
                            autoplay
                            muted
                            loop
                            playsinline
                            class="absolute top-0 left-0 w-full h-full object-cover z-0">
                        </video>
                    @elseif ($producto->imagen)
                        <img
                            src="{{ asset('images/' . $producto->imagen) }}"
                            alt="{{ $producto->nombre }}"
                            class="absolute top-0 left-0 w-full h-full object-cover z-0"
                            onerror="this.style.display='none'; document.getElementById('fallback-{{ $producto->id }}').classList.remove('hidden');">
                        {{-- Fallback por si la imagen falla --}}
                        <div id="fallback-{{ $producto->id }}"
                             class="absolute top-0 left-0 w-full h-full flex items-center justify-center text-white text-lg font-bold bg-black hidden z-0">
                            Imagen no disponible
                        </div>
                    @else
                        {{-- Fallback si no hay ni video ni imagen --}}
                        <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center text-white text-lg font-bold bg-black z-0">
                            Imagen no disponible
                        </div>
                    @endif

                    {{-- INFORMACIÓN DEL PRODUCTO --}}
                    <div class="relative z-10 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-6 text-white mt-auto pb-20">
                        <div class="flex justify-between items-start mb-3">
                            <h2 class="text-xl font-bold uppercase tracking-wide">{{ $producto->nombre }}</h2>
                            <span class="bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white text-sm font-bold px-3 py-1.5 rounded-lg shadow-lg">
                                €{{ number_format($producto->precio, 2) }}
                            </span>
                        </div>

                        <p class="text-sm mb-4 text-white/90 leading-relaxed">{{ \Illuminate\Support\Str::limit($producto->descripcion, 100) }}</p>

                        <div class="flex justify-center items-center">
                            <button
                                @click='abrirDetalle(JSON.parse(`{!! json_encode([
                                    "id" => $producto->id,
                                    "nombre" => $producto->nombre,
                                    "descripcion" => $producto->descripcion,
                                    "precio" => (float) $producto->precio,
                                    "imagen" => $producto->imagen ? asset("images/" . $producto->imagen) : null,
                                    "adiciones_disponibles" => $producto->adiciones,
                                ]) !!}`)); mostrarVideos = false'
                                class="bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] hover:from-[#2A9C75] hover:to-[#238B63] text-white font-bold px-8 py-3 rounded-full shadow-lg shadow-[#3CB28B]/30 transition-all duration-300 hover:scale-105 hover:shadow-xl border border-white/20 backdrop-blur-sm flex items-center gap-2">
                                <span class="text-lg">➕</span>
                                <span>Añadir al carrito</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endforeach

</div>

{{-- Script ScrollSpy --}}
<script>
function scrollSpyCategorias() {
    return {
        categoriaActiva: null,
        categorias: [],
        init() {
            this.categorias = [...document.querySelectorAll('#contenedorVideos [id^="categoria-"]')];
            this.onScroll();
        },
        scrollToCategoria(id) {
            const contenedor = document.getElementById('contenedorVideos');
            const categoria = contenedor.querySelector(`#categoria-${id}`);
            const producto = categoria?.querySelector('.snap-start');

            if (producto && contenedor) {
                const contenedorRect = contenedor.getBoundingClientRect();
                const productoRect = producto.getBoundingClientRect();
                const scrollActual = contenedor.scrollTop;
                const posicionProducto = productoRect.top - contenedorRect.top + scrollActual;

                contenedor.scrollTo({ top: posicionProducto, behavior: 'smooth' });
                this.categoriaActiva = id;
            }
        },
        onScroll() {
            const contenedor = document.getElementById('contenedorVideos');
            const scrollTop = contenedor.scrollTop;
            const containerHeight = contenedor.clientHeight;
            const puntoReferencia = scrollTop + (containerHeight * 0.7);

            let categoriaActual = null;

            for (let i = 0; i < this.categorias.length; i++) {
                const categoria = this.categorias[i];
                const siguienteCategoria = this.categorias[i + 1];
                const inicioCategoria = categoria.offsetTop;
                const finalCategoria = siguienteCategoria
                    ? siguienteCategoria.offsetTop
                    : categoria.offsetTop + categoria.offsetHeight;

                if (puntoReferencia >= inicioCategoria && puntoReferencia < finalCategoria) {
                    categoriaActual = categoria.getAttribute('id').replace('categoria-', '');
                    break;
                }
            }

            if (categoriaActual && categoriaActual !== this.categoriaActiva) {
                this.categoriaActiva = categoriaActual;
            }
        }
    };
}
</script>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>

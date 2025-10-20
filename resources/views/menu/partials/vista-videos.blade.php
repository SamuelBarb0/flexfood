{{-- Vista de videos completa y funcional - SOLO SE RENDERIZA CUANDO ES NECESARIA --}}
<template x-if="mostrarVideos">
  <div class="fixed inset-0 z-50"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    
    <div
        x-data="{
            ...scrollSpyCategorias(),
            alturaDisponible: 0,
            calcularAltura() {
                const menu = document.getElementById('menu-inferior');
                const menuHeight = menu ? menu.offsetHeight : 60;
                this.alturaDisponible = window.innerHeight - menuHeight;
            },
            snapToNearestVideo() {
                const container = document.getElementById('contenedorVideos');
                if (!container) return;

                const videos = container.querySelectorAll('.snap-start');
                const scrollTop = container.scrollTop;
                const containerHeight = container.clientHeight;
                const centerPoint = scrollTop + (containerHeight / 2);

                let closestVideo = null;
                let closestDistance = Infinity;

                videos.forEach(video => {
                    const videoCenter = video.offsetTop + (video.offsetHeight / 2);
                    const distance = Math.abs(centerPoint - videoCenter);

                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closestVideo = video;
                    }
                });

                if (closestVideo) {
                    container.scrollTo({
                        top: closestVideo.offsetTop,
                        behavior: 'smooth'
                    });
                }
            },
        }"
        x-init="
            init();
            calcularAltura();
            window.addEventListener('resize', () => calcularAltura());
            window.addEventListener('orientationchange', () => setTimeout(() => calcularAltura(), 100));
            $nextTick(() => {
                calcularAltura();
                onScroll();
            });
        "
        @scroll="onScroll"
        class="fixed inset-x-0 top-0 z-[40] bg-black overflow-y-auto snap-y snap-mandatory scroll-smooth"
        :style="'height: ' + alturaDisponible + 'px; bottom: ' + (window.innerHeight - alturaDisponible) + 'px;'"
        id="contenedorVideos"
    >

    {{-- Carrusel de categorías --}}
    @php
        $categoriasConProductos = $categorias->filter(fn($cat) =>
            $cat->productos->where('disponible', true)->count() > 0
        );
        $tieneCarruselVideos = $categoriasConProductos->count() > 3;
    @endphp

    {{-- Botón cerrar y carrusel --}}
    <div class="sticky top-0 z-[45] bg-transparent py-4 px-4">
        {{-- Botón cerrar videos --}}
        <div class="flex justify-between items-center mb-4">
        </div>

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
                                data-src="{{ asset('images/' . $producto->video) }}"
                                data-video-id="{{ $producto->id }}"
                                preload="none"
                                muted
                                loop
                                playsinline
                                class="absolute top-0 left-0 w-full h-full object-cover z-0 lazy-video"
                                poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3Crect fill='%23000000' width='1' height='1'/%3E%3C/svg%3E">
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
                        <div class="relative z-10 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-6 text-white mt-auto">
                            <div class="flex justify-between items-start mb-3">
                                <h2 class="text-xl font-bold uppercase tracking-wide">{{ $producto->nombre }}</h2>
                                <span class="bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white text-sm font-bold px-3 py-1.5 rounded-lg shadow-lg">
                                    €{{ number_format($producto->precio, 2) }}
                                </span>
                            </div>

                            <p class="text-sm mb-4 text-white/90 leading-relaxed">{{ \Illuminate\Support\Str::limit($producto->descripcion, 100) }}</p>

                            <div class="flex justify-center items-center">
                                <button
                                    @click.stop="
                                        abrirDetalle(@js([
                                            'id' => $producto->id,
                                            'nombre' => $producto->nombre,
                                            'descripcion' => $producto->descripcion,
                                            'precio' => (float) $producto->precio,
                                            'imagen' => $producto->imagen ? asset('images/' . $producto->imagen) : null,
                                            'video' => $producto->video ? asset('images/' . $producto->video) : null,
                                            'adiciones_disponibles' => $producto->adiciones,
                                        ]));
                                    "
                                    style="-webkit-tap-highlight-color: transparent;"
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
  </div>
</template>

{{-- Estilos CSS necesarios para scrollbar-hide --}}
<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Mejorar scroll en vista de videos para móvil */
#contenedorVideos {
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    scroll-snap-type: y mandatory;
    touch-action: pan-y;
    scroll-behavior: smooth;
}

#contenedorVideos .snap-start {
    scroll-snap-align: start;
    scroll-snap-stop: always;
}

/* Prevenir selección de texto pero permitir scroll */
#contenedorVideos * {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Permitir interacción en botones */
#contenedorVideos button {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    touch-action: manipulation;
}

/* Permitir scroll en el contenedor sticky */
#contenedorVideos .sticky {
    touch-action: pan-x;
}

/* Mejorar el snap scrolling */
@media (max-width: 768px) {
    #contenedorVideos {
        scroll-snap-type: y mandatory;
        -webkit-scroll-snap-type: y mandatory;
    }

    #contenedorVideos .snap-start {
        scroll-snap-align: start;
        scroll-snap-stop: always;
        -webkit-scroll-snap-coordinate: 0 0;
    }
}

/* Suavizar transiciones entre videos */
#contenedorVideos .snap-start {
    transition: transform 0.2s ease-out;
}

/* Reducir el scroll momentum en iOS */
#contenedorVideos {
    -webkit-overflow-scrolling: auto;
    overflow-scrolling: auto;
}
</style>

<script>
// ScrollSpy para vista de videos
function scrollSpyCategorias() {
  return {
    categoriaActiva: null,
    categorias: [],
    botonesCarrusel: [],
    scrollTimeout: null,
    animatingCarousel: false,
    loadedVideos: new Set(),
    currentPlayingVideo: null,
    init() {
      this.categorias = [...document.querySelectorAll('#contenedorVideos [id^="categoria-"]')];
      this.botonesCarrusel = [...document.querySelectorAll('#contenedorVideos .overflow-x-auto a, #contenedorVideos .flex.justify-center a')];
      this.onScroll();

      // Inicializar lazy loading de videos
      this.initLazyVideos();

      // Mejorar el comportamiento del scroll en móviles
      const contenedor = document.getElementById('contenedorVideos');
      if (contenedor && 'ontouchstart' in window) {
        contenedor.style.scrollSnapType = 'y mandatory';
        contenedor.style.scrollBehavior = 'smooth';

        // Agregar control simple post-scroll
        let scrollTimeout;
        contenedor.addEventListener('scroll', () => {
          clearTimeout(scrollTimeout);
          scrollTimeout = setTimeout(() => {
            this.snapToClosestVideo();
            this.checkVisibleVideos(); // Verificar qué videos cargar
          }, 100);
        });
      }

      // Cargar el primer video inmediatamente
      this.$nextTick(() => {
        this.checkVisibleVideos();
      });
    },
    initLazyVideos() {
      // Configurar todos los videos como lazy
      const videos = document.querySelectorAll('.lazy-video');
      videos.forEach(video => {
        video.addEventListener('loadeddata', () => {
          this.loadedVideos.add(video.dataset.videoId);
        });
      });
    },
    checkVisibleVideos() {
      const contenedor = document.getElementById('contenedorVideos');
      if (!contenedor) return;

      const videos = contenedor.querySelectorAll('.lazy-video');
      const scrollTop = contenedor.scrollTop;
      const containerHeight = contenedor.clientHeight;

      videos.forEach(video => {
        const videoContainer = video.closest('.snap-start');
        if (!videoContainer) return;

        const videoTop = videoContainer.offsetTop;
        const videoBottom = videoTop + videoContainer.offsetHeight;

        // Calcular distancia del centro
        const videoCenterY = videoTop + (videoContainer.offsetHeight / 2);
        const containerCenterY = scrollTop + (containerHeight / 2);
        const distance = Math.abs(videoCenterY - containerCenterY);

        // Video visible o próximo (dentro de 1.5 pantallas)
        const isNearby = distance < containerHeight * 1.5;
        const isVisible = videoTop < scrollTop + containerHeight && videoBottom > scrollTop;

        if (isNearby && !video.src) {
          // Cargar video
          video.src = video.dataset.src;
          video.load();
        }

        if (isVisible && distance < containerHeight * 0.3) {
          // Reproducir video visible en el centro
          if (this.currentPlayingVideo && this.currentPlayingVideo !== video) {
            this.currentPlayingVideo.pause();
          }
          video.play().catch(() => {});
          this.currentPlayingVideo = video;
        } else {
          // Pausar video no visible
          video.pause();
        }
      });
    },
    scrollToCategoria(id) {
      const contenedor = document.getElementById('contenedorVideos');
      const categoria = contenedor?.querySelector(`#categoria-${id}`);
      const producto = categoria?.querySelector('.snap-start');

      if (producto && contenedor) {
        const contenedorRect = contenedor.getBoundingClientRect();
        const productoRect = producto.getBoundingClientRect();
        const scrollActual = contenedor.scrollTop;
        const posicionProducto = productoRect.top - contenedorRect.top + scrollActual;

        contenedor.scrollTo({ top: posicionProducto, behavior: 'smooth' });
        this.categoriaActiva = id;
        this.scrollCarruselHorizontal(id);
      }
    },
    scrollCarruselHorizontal(id) {
      const categoriaIndex = this.categorias.findIndex(categoria =>
        categoria.getAttribute('id') === `categoria-${id}`
      );

      if (categoriaIndex === -1) return;

      const botonActivo = this.botonesCarrusel[categoriaIndex];
      if (!botonActivo) return;

      let carrusel = document.querySelector('#contenedorVideos .overflow-x-auto');

      if (!carrusel) {
        carrusel = botonActivo.closest('.overflow-x-auto');
      }

      if (!carrusel) {
        const contenedorCategorias = document.querySelector('#contenedorVideos .sticky.top-0');
        if (contenedorCategorias) {
          carrusel = contenedorCategorias.querySelector('.overflow-x-auto');
        }
      }

      if (!carrusel) return;
      if (carrusel.scrollWidth <= carrusel.clientWidth) return;

      // Evitar animaciones múltiples simultáneas
      if (this.animatingCarousel) return;
      this.animatingCarousel = true;

      const carruselRect = carrusel.getBoundingClientRect();
      const botonRect = botonActivo.getBoundingClientRect();

      const scrollActual = carrusel.scrollLeft;
      const posicionBotonRelativa = botonRect.left - carruselRect.left + scrollActual;

      const mitadCarrusel = carrusel.clientWidth / 2;
      const mitadBoton = botonActivo.offsetWidth / 2;
      const scrollObjetivo = posicionBotonRelativa - mitadCarrusel + mitadBoton;

      const scrollMaximo = carrusel.scrollWidth - carrusel.clientWidth;
      const scrollFinal = Math.max(0, Math.min(scrollObjetivo, scrollMaximo));

      // Solo animar si realmente necesita moverse
      if (Math.abs(scrollFinal - scrollActual) > 5) {
        try {
          carrusel.scrollTo({
            left: scrollFinal,
            behavior: 'smooth'
          });

          setTimeout(() => {
            if (Math.abs(carrusel.scrollLeft - scrollFinal) > 5) {
              carrusel.scrollLeft = scrollFinal;
            }
            this.animatingCarousel = false;
          }, 300);

        } catch (error) {
          carrusel.scrollLeft = scrollFinal;
          this.animatingCarousel = false;
        }
      } else {
        this.animatingCarousel = false;
      }
    },
    onScroll() {
      const contenedor = document.getElementById('contenedorVideos');
      if (!contenedor) return;

      // Verificar videos visibles inmediatamente (sin throttle para mejor UX)
      this.checkVisibleVideos();

      // Throttle para categorías (optimización)
      if (this.scrollTimeout) {
        clearTimeout(this.scrollTimeout);
      }

      this.scrollTimeout = setTimeout(() => {
        const scrollTop = contenedor.scrollTop;
        const containerHeight = contenedor.clientHeight;
        const puntoReferencia = scrollTop + (containerHeight * 0.5);

        let categoriaActual = null;

        for (let i = 0; i < this.categorias.length; i++) {
          const categoria = this.categorias[i];
          const siguienteCategoria = this.categorias[i + 1];
          const inicio = categoria.offsetTop;
          const fin = siguienteCategoria ? siguienteCategoria.offsetTop : inicio + categoria.offsetHeight;

          if (puntoReferencia >= inicio && puntoReferencia < fin) {
            categoriaActual = categoria.getAttribute('id').replace('categoria-', '');
            break;
          }
        }

        if (categoriaActual && categoriaActual !== this.categoriaActiva) {
          this.categoriaActiva = categoriaActual;
          this.scrollCarruselHorizontal(categoriaActual);
        }
      }, 50);
    },
    snapToClosestVideo() {
      const container = document.getElementById('contenedorVideos');
      if (!container) return;

      const videos = container.querySelectorAll('.snap-start');
      const scrollTop = container.scrollTop;
      const containerHeight = container.clientHeight;
      const centerPoint = scrollTop + (containerHeight / 2);

      let closestVideo = null;
      let closestDistance = Infinity;

      videos.forEach(video => {
        const videoTop = video.offsetTop;
        const videoBottom = videoTop + video.offsetHeight;
        const videoCenter = videoTop + (video.offsetHeight / 2);

        // Priorizar videos que están parcialmente visibles
        if (videoTop <= scrollTop + containerHeight && videoBottom >= scrollTop) {
          const distance = Math.abs(centerPoint - videoCenter);
          if (distance < closestDistance) {
            closestDistance = distance;
            closestVideo = video;
          }
        }
      });

      if (closestVideo) {
        const targetTop = closestVideo.offsetTop;
        const currentTop = container.scrollTop;

        // Solo hacer snap si la diferencia es significativa
        if (Math.abs(targetTop - currentTop) > 20) {
          container.scrollTo({
            top: targetTop,
            behavior: 'smooth'
          });
        }
      }
    }
  };
}
</script>
<!DOCTYPE html>
<html lang="es" x-data="termsPage()" x-init="init()">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
    $seo = $serverData['seo'] ?? [];
    $fallbackTitle = 'Términos y Condiciones – FlexFood';
    $fallbackDesc  = 'Condiciones de uso del servicio FlexFood.';
    $fallbackImg   = asset('img/og-default.jpg');
    $pageUrl       = url('/terminos');
  @endphp

  <title x-text="content.seo?.title || content.title || 'Términos y Condiciones'">
    {{ $seo['title'] ?? ($serverData['content']['title'] ?? $fallbackTitle) }}
  </title>
  <meta name="description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="content.seo?.description || '{{ $fallbackDesc }}'">
  <link rel="canonical" href="{{ $pageUrl }}">

  <!-- OG/Twitter básicos -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ $pageUrl }}" />
  <meta property="og:title" content="{{ $seo['title'] ?? $fallbackTitle }}" x-bind:content="content.seo?.title || content.title || '{{ $fallbackTitle }}'"/>
  <meta property="og:description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="content.seo?.description || '{{ $fallbackDesc }}'"/>
  <meta property="og:image" content="{{ $seo['ogImage'] ?? $fallbackImg }}" x-bind:content="content.seo?.ogImage || '{{ $fallbackImg }}'"/>
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $seo['title'] ?? $fallbackTitle }}" x-bind:content="content.seo?.title || content.title || '{{ $fallbackTitle }}'"/>
  <meta name="twitter:description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="content.seo?.description || '{{ $fallbackDesc }}'"/>
  <meta name="twitter:image" content="{{ $seo['ogImage'] ?? $fallbackImg }}" x-bind:content="content.seo?.ogImage || '{{ $fallbackImg }}'"/>

  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📄</text></svg>">

  <!-- Tailwind + Alpine -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>
    .container-max { max-width: 1200px; }
    [x-cloak] { display: none !important; }
    .glass { backdrop-filter: blur(8px); background: rgba(255,255,255,.65); }
  </style>
</head>
<body class="bg-white text-slate-800">

  {{-- Barra de edición (solo admin) --}}
  @role('administrador')
  <div class="fixed z-50 bottom-6 right-6 flex flex-col gap-3" x-cloak>
    <button @click="edit = !edit"
      class="px-4 py-2 rounded-xl shadow-lg text-white font-semibold"
      :class="edit ? 'bg-rose-500 hover:bg-rose-600' : 'bg-slate-900 hover:bg-slate-800'">
      <span x-show="!edit">✏️ Modo edición</span>
      <span x-show="edit">✅ Terminar edición</span>
    </button>

    <template x-if="edit">
      <div class="glass rounded-xl shadow p-3 flex flex-col gap-2 border">
        <button @click="openSeo = true" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded">⚙️ SEO</button>
        <button @click="save()" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded">💾 Guardar</button>
        <button @click="resetToDefaults()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded">♻️ Reset</button>
      </div>
    </template>
  </div>
  @endrole

  <!-- Header / Navbar (match landing) -->
  <header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-sky-50 -z-10"></div>

    <nav class="container container-max mx-auto flex items-center justify-between px-6 py-5">
      <div class="flex items-center gap-3">
        <a href="{{ route('landing.show') }}" class="flex items-center gap-3">
          <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood" class="h-14 w-auto">
          <span class="sr-only">Volver a la Landing</span>
        </a>
      </div>
      <div class="hidden md:flex items-center gap-6 text-slate-600">
        <a href="{{ route('landing.show') }}#features" class="hover:text-slate-900">Características</a>
        <a href="{{ route('landing.show') }}#pricing" class="hover:text-slate-900">Planes</a>
        <a href="{{ route('landing.show') }}#faq" class="hover:text-slate-900">FAQ</a>
        <a href="{{ route('terminos') }}" class="hover:text-slate-900">Términos y Condiciones</a>
          <!-- Nuevo botón: Ingresar -->
  <a href="{{ route('login') }}"
     class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50">
    Ingresar
  </a>

        <a href="{{ route('landing.show') }}#contacto" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800">Contacto</a>
      </div>
    </nav>

    <!-- Hero compacto -->
    <section class="container container-max mx-auto px-6 pt-6 pb-12 grid md:grid-cols-2 gap-10 items-center">
      <div>
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight" x-show="!edit" x-text="content.title"></h1>
        <input x-show="edit" x-model="content.title" class="w-full border rounded p-3 text-2xl font-bold" placeholder="Título de la página">

        <p class="mt-4 text-lg text-slate-600" x-show="!edit" x-text="content.intro"></p>
        <textarea x-show="edit" x-model="content.intro" class="w-full border rounded p-3 mt-3" rows="3" placeholder="Introducción a los términos"></textarea>

        <div class="mt-4 text-sm text-slate-500">
          <span>Última actualización:</span>
          <span x-show="!edit" x-text="formatDate(content.lastUpdated)"></span>
          <div x-show="edit" class="flex gap-2 mt-1">
            <input type="date" x-model="content.lastUpdated" class="border rounded p-2 text-sm">
            <input type="text" x-model="content.lastUpdatedNote" class="flex-1 border rounded p-2 text-sm" placeholder="Nota visible opcional (ej: Revisado legalmente)">
          </div>
          <div class="text-xs text-slate-400 mt-1" x-show="content.lastUpdatedNote" x-text="content.lastUpdatedNote"></div>
        </div>
      </div>

      <div class="relative">
        <div class="absolute -top-8 -left-8 h-28 w-28 bg-emerald-200 rounded-full blur-2xl opacity-70 -z-10"></div>
        <div class="absolute -bottom-8 -right-8 h-28 w-28 bg-sky-200 rounded-full blur-2xl opacity-70 -z-10"></div>
      </div>
    </section>
  </header>

  <!-- Contenido (Secciones) -->
  <main class="py-8">
    <div class="container container-max mx-auto px-6 space-y-6">
      <template x-for="(section, i) in content.sections" :key="i">
        <article class="rounded-2xl border bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <h2 class="text-xl font-semibold mb-2" x-show="!edit" x-text="section.heading"></h2>
              <input x-show="edit" x-model="section.heading" class="w-full border rounded p-2 mb-2 font-semibold" placeholder="Título de sección">

              <div class="prose prose-sm max-w-none whitespace-pre-line" x-show="!edit" x-text="section.body"></div>
              <textarea x-show="edit" x-model="section.body" class="w-full border rounded p-2" rows="6" placeholder="Contenido de la sección (texto plano o saltos de línea)"></textarea>
            </div>

            <div x-show="edit" class="flex flex-col gap-2 shrink-0">
              <button @click="moveUp(i)" class="px-2 py-1 text-xs rounded border hover:bg-slate-50">⬆️ Subir</button>
              <button @click="moveDown(i)" class="px-2 py-1 text-xs rounded border hover:bg-slate-50">⬇️ Bajar</button>
              <button @click="content.sections.splice(i,1)" class="px-2 py-1 text-xs rounded border text-rose-600 hover:bg-rose-50">🗑️ Eliminar</button>
            </div>
          </div>
        </article>
      </template>

      <button x-show="edit"
              @click="content.sections.push({heading:'Nuevo apartado', body:'Texto del nuevo apartado...'})"
              class="px-4 py-2 border rounded-lg hover:bg-slate-50">
        + Añadir sección
      </button>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-10 mt-10 border-t">
    <div class="container container-max mx-auto px-6 text-sm text-slate-500 flex flex-col md:flex-row items-center justify-between gap-3">
      <div>© <span x-text="new Date().getFullYear()"></span> FlexFood. Todos los derechos reservados.</div>
      <div class="flex items-center gap-5">
        <a href="{{ route('landing.show') }}" class="hover:text-slate-900">Inicio</a>
        <a href="{{ route('landing.show') }}#features" class="hover:text-slate-900">Características</a>
        <a href="{{ route('landing.show') }}#pricing" class="hover:text-slate-900">Planes</a>
        <a href="{{ route('landing.show') }}#faq" class="hover:text-slate-900">FAQ</a>
        <a href="{{ route('terminos') }}" class="hover:text-slate-900">Términos y Condiciones</a>
      </div>
    </div>
  </footer>

  <!-- SEO Modal (con subida OG) -->
  <div x-show="openSeo" x-transition
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
       style="display:none" @keydown.escape.window="openSeo=false">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-6" @click.away="openSeo=false">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-slate-900">Editar SEO (Términos)</h3>
        <button class="text-slate-500 hover:text-slate-700" @click="openSeo=false">✖</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Título (title)</label>
          <input x-model="content.seo.title" class="w-full border rounded-lg p-2 mt-1" placeholder="Título SEO">
        </div>

        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Descripción (meta description)</label>
          <textarea x-model="content.seo.description" class="w-full border rounded-lg p-2 mt-1" rows="3" placeholder="Descripción SEO"></textarea>
        </div>

        <!-- OG Image URL + Subida -->
        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">OG Image</label>
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <!-- URL directa -->
            <input x-model="content.seo.ogImage" class="flex-1 border rounded-lg p-2" placeholder="https://... (URL de la imagen OG)" />

            <!-- Subir archivo -->
            <input type="file" accept="image/*" class="hidden" x-ref="ogFile" @change="uploadOg($event)">
            <button type="button"
                    @click="$refs.ogFile.click()"
                    class="px-3 py-2 rounded bg-slate-900 hover:bg-slate-800 text-white text-sm">
              ⬆️ Subir imagen
            </button>
          </div>

          <!-- Estados -->
          <div class="mt-1 flex items-center gap-3">
            <span x-show="uploadingOg" class="text-sm text-slate-500">Subiendo…</span>
            <span x-show="uploadErrorOg" class="text-sm text-rose-600" x-text="uploadErrorOg"></span>
          </div>
        </div>

        <!-- Vista previa -->
        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Vista previa OG</label>
          <div class="mt-2 border rounded-xl p-3 bg-slate-50">
            <template x-if="content.seo.ogImage">
              <img :src="content.seo.ogImage" alt="OG Preview" class="max-h-48 rounded-lg object-cover" />
            </template>
            <p class="text-xs text-slate-500 mt-2">* Previsualización local.</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300" @click="openSeo=false">Cancelar</button>
        <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700" @click="save(); openSeo=false">Guardar</button>
      </div>
    </div>
  </div>

  <script>
  function termsPage() {
    const defaults = {
      title: 'Términos y Condiciones',
      intro: 'Este documento regula el uso de FlexFood. Léelo atentamente antes de utilizar la plataforma.',
      sections: [
        { heading: '1. Aceptación de términos', body: 'Al usar la plataforma, aceptas estos términos.' },
        { heading: '2. Uso permitido', body: 'Usa la plataforma de forma legal y respetuosa.' },
        { heading: '3. Privacidad', body: 'Tratamos tus datos según nuestra política de privacidad.' },
      ],
      lastUpdated: new Date().toISOString().slice(0,10),
      lastUpdatedNote: '',
      seo: {
        title: 'Términos y Condiciones – FlexFood',
        description: 'Condiciones de uso del servicio FlexFood.',
        ogImage: ''
      }
    };

    return {
      edit: @json($canEdit ?? false),
      openSeo: false,
      content: @json(($serverData['content'] ?? [])),

      // estados de subida OG
      uploadingOg: false,
      uploadErrorOg: '',

      init() {
        // Merge seguro
        this.content = { ...defaults, ...(this.content || {}) };
        if (!Array.isArray(this.content.sections)) this.content.sections = [...defaults.sections];
        this.content.seo = { ...defaults.seo, ...(this.content.seo || {}) };
        if (!this.content.lastUpdated) this.content.lastUpdated = new Date().toISOString().slice(0,10);
      },

      async save() {
        const payload = { content: this.content };

        const res = await fetch(@json(route('terminos.update')), {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token())
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok) {
          alert('Error al guardar');
          return;
        }
        alert('Guardado ✔');
        this.edit = false;
      },

      resetToDefaults() {
        if (!confirm('¿Restablecer a contenido por defecto?')) return;
        this.content = JSON.parse(JSON.stringify(defaults));
      },

      moveUp(i) {
        if (i <= 0) return;
        const arr = this.content.sections;
        [arr[i-1], arr[i]] = [arr[i], arr[i-1]];
      },
      moveDown(i) {
        const arr = this.content.sections;
        if (i >= arr.length - 1) return;
        [arr[i+1], arr[i]] = [arr[i], arr[i+1]];
      },

      formatDate(iso) {
        try {
          const d = new Date(iso);
          return d.toLocaleDateString('es-ES', { day:'2-digit', month:'long', year:'numeric' });
        } catch { return iso; }
      },

      // SUBIR OG IMAGE (reutiliza landing.upload)
      async uploadOg(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        this.uploadErrorOg = '';
        this.uploadingOg = true;

        try {
          const fd = new FormData();
          fd.append('image', file);

          const res = await fetch(@json(route('landing.upload')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
            body: fd
          });

          if (!res.ok) {
            const text = await res.text();
            throw new Error(text || 'Error al subir la imagen');
          }

          const { url } = await res.json();
          this.content.seo.ogImage = url;
        } catch (err) {
          console.error(err);
          this.uploadErrorOg = 'No se pudo subir la imagen';
        } finally {
          this.uploadingOg = false;
          e.target.value = '';
        }
      }
    };
  }
  </script>
</body>
</html>

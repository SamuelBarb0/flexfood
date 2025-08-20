<!DOCTYPE html>
<html lang="es" x-data="landing()" x-init="init()">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
  $seo = $serverData['seo'] ?? [];
  $fallbackTitle = 'FlexFood – Gestión moderna para tu restaurante';
  $fallbackDesc = 'Toma pedidos, gestiona mesas y cobra más rápido con una plataforma pensada para restaurantes modernos.';
  $fallbackKW = 'restaurante, comandas, menú digital, POS';
  $fallbackImg = asset('img/og-default.jpg');
  $pageUrl = url('/landing');
  @endphp

  <!-- SEO (SSR + Live preview con Alpine) -->
  <title x-text="seo.title">{{ $seo['title'] ?? $fallbackTitle }}</title>

  <meta name="description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="seo.description">
  <meta name="keywords" content="{{ $seo['keywords'] ?? $fallbackKW }}" x-bind:content="seo.keywords">

  <link rel="canonical" href="{{ $pageUrl }}">

  <!-- Open Graph -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ $pageUrl }}" />
  <meta property="og:title" content="{{ $seo['title'] ?? $fallbackTitle }}" x-bind:content="seo.title" />
  <meta property="og:description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="seo.description" />
  <meta property="og:image" content="{{ $seo['ogImage'] ?? $fallbackImg }}" x-bind:content="seo.ogImage" />

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $seo['title'] ?? $fallbackTitle }}" x-bind:content="seo.title">
  <meta name="twitter:description" content="{{ $seo['description'] ?? $fallbackDesc }}" x-bind:content="seo.description">
  <meta name="twitter:image" content="{{ $seo['ogImage'] ?? $fallbackImg }}" x-bind:content="seo.ogImage">

  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>🍽️</text></svg>">

  <!-- Tailwind + Alpine -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>
    .container-max {
      max-width: 1200px;
    }

    .glass {
      backdrop-filter: blur(8px);
      background: rgba(255, 255, 255, .65);
    }
  </style>
</head>

<body class="bg-white text-slate-800">
  <!-- Barra de edición / admin -->
  <div class="fixed z-50 bottom-6 right-6 flex flex-col gap-3">
    <button @click="edit = !edit"
      class="px-4 py-2 rounded-xl shadow-lg text-white font-semibold"
      :class="edit ? 'bg-rose-500 hover:bg-rose-600' : 'bg-slate-900 hover:bg-slate-800'">
      <span x-show="!edit">✏️ Modo edición</span>
      <span x-show="edit">✅ Terminar edición</span>
    </button>


    <template x-if="edit">
      <div class="glass rounded-xl shadow p-3 flex flex-col gap-2">
        <button @click="openSeo = true" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded">⚙️ SEO</button>
        <button @click="save()" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded">💾 Guardar</button>
        <button @click="reset()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded">♻️ Reset</button>
      </div>
    </template>
  </div>

  <!-- Hero -->
  <header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-sky-50 -z-10"></div>
    <nav class="container container-max mx-auto flex items-center justify-between px-6 py-5">
<div class="flex items-center gap-3">
  <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood" class="h-20 w-auto">
</div>

      <div class="hidden md:flex items-center gap-6 text-slate-600">
        <a href="#features" class="hover:text-slate-900">Características</a>
        <a href="#pricing" class="hover:text-slate-900">Planes</a>
        <a href="#faq" class="hover:text-slate-900">FAQ</a>
        <a :href="cta.href" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800"><span x-text="cta.text"></span></a>
      </div>
    </nav>

    <section class="container container-max mx-auto px-6 pt-10 pb-20 grid md:grid-cols-2 gap-10 items-center">
      <div>
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight" contenteditable="false" x-text="hero.title" x-show="!edit"></h1>
        <textarea x-show="edit" x-model="hero.title" class="w-full border rounded p-3 text-xl font-bold"></textarea>

        <p class="mt-4 text-lg text-slate-600" x-text="hero.subtitle" x-show="!edit"></p>
        <textarea x-show="edit" x-model="hero.subtitle" class="w-full border rounded p-3 mt-3"></textarea>

        <div class="mt-6 flex flex-wrap gap-3">
          <a :href="cta.href" class="px-5 py-3 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 font-semibold"><span x-text="cta.text"></span></a>
          <a :href="cta.secondaryHref" class="px-5 py-3 rounded-xl bg-white border hover:bg-slate-50 font-semibold"><span x-text="cta.secondaryText"></span></a>
        </div>

        <div class="mt-6 text-sm text-slate-500">Sin tarjeta · Cancela cuando quieras</div>
      </div>

      <div class="relative">
        <div class="absolute -top-8 -left-8 h-28 w-28 bg-emerald-200 rounded-full blur-2xl opacity-70 -z-10"></div>
        <div class="absolute -bottom-8 -right-8 h-28 w-28 bg-sky-200 rounded-full blur-2xl opacity-70 -z-10"></div>

        <div class="rounded-2xl shadow-xl ring-1 ring-slate-100 overflow-hidden bg-white">
          <template x-if="hero.image">
            <img :src="hero.image" alt="Demo FlexFood" class="w-full object-cover">
          </template>

          <div class="p-5">
            <!-- Controles de edición (solo en modo edit) -->
            <div x-show="edit" class="flex flex-wrap items-center gap-2 mb-3">
              <input type="file" accept="image/*" class="hidden" x-ref="heroFile" @change="uploadHero($event)">
              <button type="button"
                @click="$refs.heroFile.click()"
                class="px-3 py-2 rounded bg-slate-900 hover:bg-slate-800 text-white text-sm">
                ⬆️ Subir imagen
              </button>
              <span x-show="uploadingHero" class="text-sm text-slate-500">Subiendo…</span>
              <span x-show="uploadErrorHero" class="text-sm text-rose-600" x-text="uploadErrorHero"></span>
            </div>

            <!-- URL manual + caption -->
            <input x-show="edit" x-model="hero.image" placeholder="URL imagen"
              class="w-full border rounded p-2 mb-2" />
            <p class="text-sm text-slate-500" x-text="hero.caption" x-show="!edit"></p>
            <input x-show="edit" x-model="hero.caption" placeholder="Leyenda"
              class="w-full border rounded p-2" />
          </div>
        </div>
      </div>
    </section>
  </header>

  <!-- Features -->
  <section id="features" class="py-16 bg-white">
    <div class="container container-max mx-auto px-6">
      <div class="flex items-end justify-between mb-10">
        <h2 class="text-3xl font-extrabold">Todo lo que tu restaurante necesita</h2>
        <button x-show="edit" @click="features.push({icon:'🍽️',title:'Nuevo',text:'Descripción'})" class="text-sm px-3 py-2 border rounded">+ Añadir</button>
      </div>

      <div class="grid md:grid-cols-3 gap-6">
        <template x-for="(f, i) in features" :key="i">
          <div class="rounded-2xl p-6 border hover:shadow-sm transition bg-white">
            <div class="text-3xl" x-text="f.icon"></div>
            <h3 class="mt-3 font-bold text-xl" x-show="!edit" x-text="f.title"></h3>
            <input x-show="edit" x-model="f.title" class="w-full border rounded p-2 mt-2" />
            <p class="mt-2 text-slate-600" x-show="!edit" x-text="f.text"></p>
            <textarea x-show="edit" x-model="f.text" class="w-full border rounded p-2 mt-2"></textarea>
            <button x-show="edit" @click="features.splice(i,1)" class="mt-3 text-sm text-rose-600">Eliminar</button>
          </div>
        </template>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section id="pricing" class="py-16 bg-slate-50">
    <div class="container container-max mx-auto px-6">
      <div class="flex items-end justify-between mb-8">
        <h2 class="text-3xl font-extrabold">Planes simples y transparentes</h2>
        <button x-show="edit" @click="addPlan()" class="text-sm px-3 py-2 border rounded">+ Añadir plan</button>
      </div>

      <div class="grid md:grid-cols-3 gap-6">
        <template x-for="(p, i) in pricing" :key="i">
          <div class="rounded-2xl bg-white border p-6 flex flex-col">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-bold" x-show="!edit" x-text="p.name"></h3>
              <input x-show="edit" x-model="p.name" class="border rounded p-2 text-sm" />
              <span class="px-2 py-1 text-xs rounded bg-emerald-50 text-emerald-700" x-show="p.highlight">Popular</span>
            </div>

            <div class="mt-4">
              <span class="text-4xl font-extrabold" x-text="p.price"></span>
              <span class="text-slate-500" x-text="p.period"></span>
            </div>

            <div class="mt-3" x-show="!edit" x-text="p.description"></div>
            <textarea x-show="edit" x-model="p.description" class="w-full border rounded p-2 mt-2"></textarea>

            <ul class="mt-5 space-y-2 text-slate-700">
              <template x-for="(feat, j) in p.features" :key="j">
                <li class="flex items-start gap-2">
                  <span>✅</span>
                  <span x-show="!edit" x-text="feat"></span>
                  <input x-show="edit" x-model="p.features[j]" class="w-full border rounded p-1" />
                  <button x-show="edit" @click="p.features.splice(j,1)" class="text-rose-600 text-xs ml-2">Quitar</button>
                </li>
              </template>
            </ul>

            <div x-show="edit" class="mt-2">
              <button @click="p.features.push('Nueva característica')" class="text-xs px-2 py-1 border rounded">+ Añadir característica</button>
            </div>

            <a :href="p.cta.href" class="mt-6 px-4 py-3 rounded-lg text-center font-semibold"
              :class="p.highlight ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-900 text-white hover:bg-slate-800'">
              <span x-text="p.cta.text"></span>
            </a>

            <div x-show="edit" class="grid grid-cols-2 gap-2 mt-4 text-sm">
              <input x-model="p.price" class="border rounded p-2" placeholder="Precio" />
              <input x-model="p.period" class="border rounded p-2" placeholder="/mes" />
              <input x-model="p.cta.text" class="border rounded p-2 col-span-2" placeholder="Texto botón" />
              <input x-model="p.cta.href" class="border rounded p-2 col-span-2" placeholder="URL botón" />
              <label class="col-span-2 inline-flex items-center gap-2 text-slate-600">
                <input type="checkbox" x-model="p.highlight" /> Destacar plan
              </label>
              <button @click="pricing.splice(i,1)" class="col-span-2 text-rose-600">Eliminar plan</button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </section>

 <!-- Testimonios -->
<section class="py-16 bg-white">
  <div class="container container-max mx-auto px-6">

    <!-- Título centrado + botón añadir en modo edición -->
    <div class="mb-10 text-center">
      <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight">
        Restaurantes que ya confían
      </h2>
      <div class="mt-4" x-show="edit">
        <button @click="testimonials.push({quote:'Nuevo testimonio', author:'Nombre', role:'Cargo'})"
                class="text-sm px-3 py-2 border rounded">
          + Añadir
        </button>
      </div>
    </div>

    <!-- Tarjetas -->
    <div class="grid md:grid-cols-3 gap-6">
      <template x-for="(t, i) in testimonials" :key="i">
        <div class="rounded-2xl p-6 border bg-white text-center">
          <!-- SIN comillas y SIN italic -->
          <p class="text-slate-700 text-base md:text-lg leading-relaxed" x-show="!edit" x-text="t.quote"></p>
          <textarea x-show="edit" x-model="t.quote" class="w-full border rounded p-2"></textarea>

          <div class="mt-4">
            <p class="font-semibold text-slate-900" x-show="!edit" x-text="t.author"></p>
            <p class="text-sm text-slate-500" x-show="!edit" x-text="t.role"></p>

            <div x-show="edit" class="flex flex-col gap-2 items-center">
              <input x-model="t.author" class="border rounded p-1 w-full" placeholder="Autor" />
              <input x-model="t.role" class="border rounded p-1 w-full" placeholder="Cargo" />
            </div>
          </div>

          <button x-show="edit" @click="testimonials.splice(i,1)" class="mt-3 text-rose-600 text-sm">
            Eliminar
          </button>
        </div>
      </template>
    </div>
  </div>
</section>


  <!-- FAQ -->
  <section id="faq" class="py-16 bg-slate-50">
    <div class="container container-max mx-auto px-6">
      <div class="flex items-end justify-between mb-8">
        <h2 class="text-3xl font-extrabold">Preguntas frecuentes</h2>
        <button x-show="edit" @click="faqs.push({q:'Nueva pregunta', a:'Nueva respuesta'})" class="text-sm px-3 py-2 border rounded">+ Añadir</button>
      </div>

      <div class="space-y-4">
        <template x-for="(f, i) in faqs" :key="i">
          <div class="rounded-xl border bg-white p-5">
            <p class="font-semibold" x-show="!edit" x-text="f.q"></p>
            <input x-show="edit" x-model="f.q" class="border rounded p-2 w-full" />
            <p class="mt-2 text-slate-600" x-show="!edit" x-text="f.a"></p>
            <textarea x-show="edit" x-model="f.a" class="border rounded p-2 w-full mt-2"></textarea>
            <button x-show="edit" @click="faqs.splice(i,1)" class="mt-3 text-rose-600 text-sm">Eliminar</button>
          </div>
        </template>
      </div>
    </div>
  </section>

 

<!-- Contacto (editable) -->
<section id="contacto" class="py-20 relative">
  <div class="absolute inset-0 -z-10 bg-gradient-to-b from-emerald-50 via-white to-sky-50"></div>

  <div class="container container-max mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-8 items-stretch">

      <!-- Lado info / branding -->
      <div class="relative rounded-3xl p-8 lg:p-10 overflow-hidden bg-gradient-to-br from-emerald-600 to-sky-600 text-white">
        <div class="absolute -top-10 -right-10 h-40 w-40 bg-white/10 blur-2xl rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 bg-white/10 blur-2xl rounded-full"></div>

        <!-- Título / Subtítulo (editables) -->
        <h2 class="text-3xl md:text-4xl font-extrabold leading-tight" x-show="!edit" x-text="contact.title"></h2>
        <input x-show="edit" x-model="contact.title" class="w-full mt-1 rounded-xl p-3 text-slate-900" placeholder="Título del bloque">

        <p class="mt-3 text-white/90" x-show="!edit" x-text="contact.subtitle"></p>
        <textarea x-show="edit" x-model="contact.subtitle" rows="3" class="w-full mt-2 rounded-xl p-3 text-slate-900"></textarea>

        <!-- Bullets -->
        <ul class="mt-6 space-y-3 text-white/90" x-show="!edit">
          <template x-for="(b, i) in contact.bullets" :key="i">
            <li class="flex items-center gap-3">
              <span class="grid place-items-center h-8 w-8 rounded-xl bg-white/15">✔️</span>
              <span x-text="b"></span>
            </li>
          </template>
        </ul>
        <div x-show="edit" class="mt-4 space-y-2">
          <template x-for="(b, i) in contact.bullets" :key="i">
            <div class="flex gap-2">
              <input x-model="contact.bullets[i]" class="flex-1 rounded-xl p-2 text-slate-900">
              <button type="button" class="px-3 rounded-lg bg-rose-100 text-rose-700" @click="contact.bullets.splice(i,1)">Quitar</button>
            </div>
          </template>
          <button type="button" class="mt-2 text-sm px-3 py-2 border border-white/40 rounded-lg"
                  @click="contact.bullets.push('Nuevo beneficio')">+ Añadir bullet</button>
        </div>

        <div class="mt-auto hidden lg:flex items-center gap-3 pt-10 opacity-90">
          <img src="{{ asset('img/flexfood.png') }}" class="h-8" alt="FlexFood">
          <span class="text-sm">Tu menú, comandas y cobro en un solo lugar</span>
        </div>
      </div>

      <!-- Formulario -->
      <div class="relative">
        <div class="h-full rounded-3xl bg-white/80 backdrop-blur-lg border border-slate-200 shadow-xl p-6 md:p-8"
             x-data="contactForm()">

          <!-- Config del formulario (solo en modo edición) -->
          <div x-show="edit" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid md:grid-cols-2 gap-3">
              <label class="text-sm text-slate-600">Texto botón
                <input x-model="contact.buttonText" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600">Mostrar teléfono
                <div class="mt-2">
                  <label class="inline-flex items-center gap-2 text-slate-700">
                    <input type="checkbox" x-model="contact.showPhone">
                    <span>Activar campo teléfono</span>
                  </label>
                </div>
              </label>

              <label class="text-sm text-slate-600">Label Nombre
                <input x-model="contact.labels.name" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600">Placeholder Nombre
                <input x-model="contact.placeholders.name" class="w-full mt-1 rounded-lg border p-2">
              </label>

              <label class="text-sm text-slate-600">Label Email
                <input x-model="contact.labels.email" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600">Placeholder Email
                <input x-model="contact.placeholders.email" class="w-full mt-1 rounded-lg border p-2">
              </label>

              <template x-if="contact.showPhone">
                <div class="md:col-span-2 grid md:grid-cols-2 gap-3">
                  <label class="text-sm text-slate-600">Label Teléfono
                    <input x-model="contact.labels.phone" class="w-full mt-1 rounded-lg border p-2">
                  </label>
                  <label class="text-sm text-slate-600">Placeholder Teléfono
                    <input x-model="contact.placeholders.phone" class="w-full mt-1 rounded-lg border p-2">
                  </label>
                </div>
              </template>

              <label class="text-sm text-slate-600 md:col-span-2">Label Mensaje
                <input x-model="contact.labels.message" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600 md:col-span-2">Placeholder Mensaje
                <input x-model="contact.placeholders.message" class="w-full mt-1 rounded-lg border p-2">
              </label>

              <label class="text-sm text-slate-600 md:col-span-2">Texto consentimiento
                <input x-model="contact.consent" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600 md:col-span-2">Mensaje de éxito
                <input x-model="contact.successMessage" class="w-full mt-1 rounded-lg border p-2">
              </label>
              <label class="text-sm text-slate-600 md:col-span-2">Mensaje de error genérico
                <input x-model="contact.errorMessage" class="w-full mt-1 rounded-lg border p-2">
              </label>
            </div>
          </div>

          <!-- Alertas -->
          <template x-if="ok">
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M16.707 5.293a1 1 0 0 1 0 1.414l-7.778 7.778a1 1 0 0 1-1.414 0L3.293 11.263a1 1 0 1 1 1.414-1.414l3.1 3.1 7.071-7.07a1 1 0 0 1 1.414 0Z"/></svg>
              <div x-text="ok"></div>
            </div>
          </template>
          <template x-if="error">
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-rose-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.401 1.59a1.5 1.5 0 0 1 2.197 0l7.5 8a1.5 1.5 0 0 1-1.098 2.51H3a1.5 1.5 0 0 1-1.1-2.51l7.5-8Zm.599 5.41a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1Zm0 7a1.25 1.25 0 1 0 0 2.5A1.25 1.25 0 0 0 10 14Z" clip-rule="evenodd"/></svg>
              <div x-text="error"></div>
            </div>
          </template>

          <h3 class="text-2xl font-bold text-slate-900" x-text="contact.formTitle"></h3>
          <p class="mt-1 text-slate-600" x-text="contact.formSubtitle"></p>

          <form class="mt-6 grid grid-cols-1 gap-4" @submit.prevent="submit">
            <!-- Nombre -->
            <label class="group block">
              <span class="text-sm text-slate-600" x-text="contact.labels.name"></span>
              <div class="mt-1 relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">👤</span>
                <input type="text" x-model="name"
                       class="w-full rounded-xl border border-slate-200 bg-white px-10 py-2.5 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       :placeholder="contact.placeholders.name" required>
              </div>
            </label>

            <!-- Email -->
            <label class="group block">
              <span class="text-sm text-slate-600" x-text="contact.labels.email"></span>
              <div class="mt-1 relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">@</span>
                <input type="email" x-model="email"
                       class="w-full rounded-xl border border-slate-200 bg-white px-10 py-2.5 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       :placeholder="contact.placeholders.email" required>
              </div>
            </label>

            <!-- Teléfono (opcional) -->
            <template x-if="contact.showPhone">
              <label class="group block">
                <span class="text-sm text-slate-600" x-text="contact.labels.phone"></span>
                <div class="mt-1 relative">
                  <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">📞</span>
                  <input type="text" x-model="phone"
                         class="w-full rounded-xl border border-slate-200 bg-white px-10 py-2.5 shadow-sm
                                focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                         :placeholder="contact.placeholders.phone">
                </div>
              </label>
            </template>

            <!-- Mensaje -->
            <label class="group block">
              <span class="text-sm text-slate-600" x-text="contact.labels.message"></span>
              <div class="mt-1 relative">
                <span class="pointer-events-none absolute left-3 top-3 text-slate-400">✍️</span>
                <textarea rows="5" x-model="message"
                          class="w-full rounded-xl border border-slate-200 bg-white px-10 py-3 shadow-sm
                                 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                          :placeholder="contact.placeholders.message" required></textarea>
              </div>
            </label>

            <!-- Botón -->
            <div class="flex items-center justify-between pt-2">
              <p class="text-xs text-slate-500" x-text="contact.consent"></p>
              <button type="submit"
                      class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white
                             hover:bg-slate-800 disabled:opacity-60"
                      :disabled="loading">
                <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="loading ? 'Enviando…' : contact.buttonText"></span>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>




  <footer class="py-10 border-t">
    <div class="container container-max mx-auto px-6 text-sm text-slate-500 flex flex-col md:flex-row items-center justify-between gap-3">
      <div>© <span x-text="new Date().getFullYear()"></span> FlexFood. Todos los derechos reservados.</div>
      <div class="flex items-center gap-5">
        <a href="#features" class="hover:text-slate-900">Características</a>
        <a href="#pricing" class="hover:text-slate-900">Planes</a>
        <a href="#faq" class="hover:text-slate-900">FAQ</a>
      </div>
    </div>
  </footer>

  <!-- SEO Modal -->
  <div x-show="openSeo" x-transition
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    style="display:none" @keydown.escape.window="openSeo=false">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-6"
      @click.away="openSeo=false">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-slate-900">Editar SEO</h3>
        <button class="text-slate-500 hover:text-slate-700" @click="openSeo=false">✖</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Título (title)</label>
          <input x-model="seo.title" class="w-full border rounded-lg p-2 mt-1" placeholder="Título de la página" />
        </div>

        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Descripción (meta description)</label>
          <textarea x-model="seo.description" class="w-full border rounded-lg p-2 mt-1" rows="3"
            placeholder="Descripción corta para buscadores"></textarea>
        </div>

        <div>
          <label class="text-sm text-slate-600">Keywords (coma separadas)</label>
          <input x-model="seo.keywords" class="w-full border rounded-lg p-2 mt-1" placeholder="menú digital, comandas, POS" />
        </div>

        <div>
          <label class="text-sm text-slate-600">OG Image URL</label>
          <input x-model="seo.ogImage" class="w-full border rounded-lg p-2 mt-1" placeholder="https://..." />
        </div>

        <div class="col-span-1 md:col-span-2">
          <label class="text-sm text-slate-600">Vista previa OG</label>
          <div class="mt-2 border rounded-xl p-3 bg-slate-50">
            <template x-if="seo.ogImage">
              <img :src="seo.ogImage" alt="OG Preview" class="max-h-48 rounded-lg object-cover" />
            </template>
            <p class="text-xs text-slate-500 mt-2">
              * La previsualización es local. Bots verán lo que renderiza el servidor.
            </p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300" @click="openSeo=false">Cancelar</button>
        <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"
          @click="save(); openSeo=false">Guardar</button>
      </div>
    </div>
  </div>

<script>
  function landing() {
    const KEY = 'flexfood_landing_v1';

    // Defaults
    const defaults = {
      seo: {
        title: 'FlexFood – Gestión moderna para tu restaurante',
        description: 'Toma pedidos, gestiona mesas y cobra más rápido con una plataforma pensada para restaurantes modernos.',
        keywords: 'restaurante, comandas, menú digital, POS',
        ogImage: 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1600&auto=format&fit=crop'
      },
      hero: {
        title: 'Menú digital, comandas y cobro en un solo lugar',
        subtitle: 'Simplifica la operación: menos errores, más ingresos y clientes felices.',
        image: 'https://images.unsplash.com/photo-1541542684-4a6c05c32922?q=80&w=1600&auto=format&fit=crop',
        caption: 'FlexFood en acción dentro de un local real.'
      },
      cta: {
        text: 'Probar gratis',
        href: '#pricing',
        secondaryText: 'Ver cómo funciona',
        secondaryHref: '#features',
        blockTitle: '¿Listo para modernizar tu restaurante?',
        blockSubtitle: 'Empieza hoy mismo. Sin tarjetas ni contratos de permanencia.'
      },
      features: [
        { icon: '📱', title: 'Menú QR', text: 'Actualiza precios y fotos en segundos. Sin imprimir.' },
        { icon: '🧾', title: 'Comandas al instante', text: 'Pedidos directos a cocina y barra con historial.' },
        { icon: '⚡', title: 'Cobro rápido', text: 'Vende más con menos fricción y menos errores.' }
      ],
      pricing: [
        {
          name: 'Starter', price: '€9', period: '/mes', highlight: false,
          description: 'Para locales pequeños que quieren empezar.',
          features: ['Menú QR ilimitado', 'Gestión básica de productos', 'Soporte por email'],
          cta: { text: 'Empezar', href: '#' }
        },
        {
          name: 'Pro', price: '€29', period: '/mes', highlight: true,
          description: 'La mejor relación calidad-precio.',
          features: ['Comandas a cocina', 'Historial de mesas', 'Reportes y analíticas'],
          cta: { text: 'Elegir Pro', href: '#' }
        },
        {
          name: 'Business', price: '€79', period: '/mes', highlight: false,
          description: 'Para grupos y cadenas con alto volumen.',
          features: ['Usuarios y roles', 'Integraciones', 'Soporte prioritario'],
          cta: { text: 'Contactar', href: '#' }
        }
      ],
      testimonials: [
        { quote: 'Desde que usamos FlexFood atendemos más rápido y sin confusiones.', author: 'Laura M.', role: 'Dueña · Casa Laura' },
        { quote: 'Los reportes nos ayudaron a ajustar precios y mejorar márgenes.', author: 'Carlos R.', role: 'Gerente · El Nopal' },
        { quote: 'La implementación fue en una tarde. Súper simple.', author: 'Ana G.', role: 'Chef · Kōji' }
      ],
      faqs: [
        { q: '¿Necesito equipamiento especial?', a: 'No. Funciona en cualquier navegador moderno (móvil o desktop).' },
        { q: '¿Puedo cancelar cuando quiera?', a: 'Sí. No hay permanencia mínima.' },
        { q: '¿Tienen soporte?', a: 'Claro, email y chat en planes Pro o superior.' }
      ],
      contact: {
        title: 'Hablemos de tu restaurante',
        subtitle: 'Cuéntanos qué necesitas y te contactaremos muy pronto. Sin compromisos.',
        formTitle: 'Envíanos un mensaje',
        formSubtitle: 'Te responderemos lo antes posible.',
        bullets: ['Respuesta en menos de 24h', 'Demo guiada y setup inicial', 'Soporte por email y chat'],
        showPhone: true,
        buttonText: 'Enviar',
        consent: 'Al enviar aceptas nuestra política de privacidad.',
        successMessage: '¡Gracias! Te contactaremos muy pronto.',
        errorMessage: 'No se pudo enviar el mensaje. Inténtalo nuevamente.',
        labels: {
          name: 'Nombre',
          email: 'Email',
          phone: 'Teléfono (opcional)',
          message: 'Mensaje'
        },
        placeholders: {
          name: 'Tu nombre',
          email: 'tu@email.com',
          phone: '+34 600 000 000',
          message: '¿En qué podemos ayudarte?'
        }
      }
    };

    return {
      edit: @json($canEdit),
      openSeo: false,

      // Estado inicial SEMBRADO con defaults
      seo: { ...defaults.seo },
      hero: { ...defaults.hero },
      cta:  { ...defaults.cta },
      features: [...defaults.features],
      pricing:  [...defaults.pricing],
      testimonials: [...defaults.testimonials],
      faqs: [...defaults.faqs],
      contact: JSON.parse(JSON.stringify(defaults.contact)),

      // flags de subida (hero)
      uploadingHero: false,
      uploadErrorHero: '',

      init() {
        const fromServer = @json($serverData ?? (object)[]);
        const savedLS = localStorage.getItem(KEY);

        const source = (fromServer && Object.keys(fromServer).length)
          ? fromServer
          : (savedLS ? JSON.parse(savedLS) : {});

        // Mezcla superficial
        Object.assign(this, source);

        // Normalización profunda para no romper bindings
        this.seo  = { ...defaults.seo,  ...(this.seo  || {}) };
        this.hero = { ...defaults.hero, ...(this.hero || {}) };
        this.cta  = { ...defaults.cta,  ...(this.cta  || {}) };

        this.features = Array.isArray(this.features) ? this.features : [...defaults.features];
        this.pricing  = Array.isArray(this.pricing)  ? this.pricing  : [...defaults.pricing];
        this.testimonials = Array.isArray(this.testimonials) ? this.testimonials : [...defaults.testimonials];
        this.faqs = Array.isArray(this.faqs) ? this.faqs : [...defaults.faqs];

        this.contact = { ...defaults.contact, ...(this.contact || {}) };
        this.contact.labels = {
          ...defaults.contact.labels,
          ...(this.contact.labels || {})
        };
        this.contact.placeholders = {
          ...defaults.contact.placeholders,
          ...(this.contact.placeholders || {})
        };
        this.contact.bullets = Array.isArray(this.contact.bullets)
          ? this.contact.bullets
          : [...defaults.contact.bullets];

        // Exponer estado para el formulario de contacto (Alpine store)
        if (window.Alpine && Alpine.store) {
          Alpine.store('landing', this);
        } else {
          document.addEventListener('alpine:init', () => Alpine.store('landing', this), { once: true });
        }

        this.setMeta();
      },

      setMeta() {
        document.title = this.seo?.title ?? 'FlexFood';
        const set = (sel, attr, val) => { const el = document.querySelector(sel); if (el) el.setAttribute(attr, val ?? ''); };
        set('meta[name="description"]', 'content', this.seo?.description ?? '');
        set('meta[property="og:title"]', 'content', this.seo?.title ?? '');
        set('meta[property="og:description"]', 'content', this.seo?.description ?? '');
        set('meta[property="og:image"]', 'content', this.seo?.ogImage ?? '');
        set('meta[name="twitter:title"]', 'content', this.seo?.title ?? '');
        set('meta[name="twitter:description"]', 'content', this.seo?.description ?? '');
        set('meta[name="twitter:image"]', 'content', this.seo?.ogImage ?? '');
      },

      async save() {
        const payload = {
          seo: this.seo,
          hero: this.hero,
          cta: this.cta,
          features: this.features,
          pricing: this.pricing,
          testimonials: this.testimonials,
          faqs: this.faqs,
          contact: this.contact
        };

        const res = await fetch(@json(route('landing.update')), {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token())
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok) {
          alert('Error al guardar 😢');
          return;
        }

        localStorage.setItem(KEY, JSON.stringify(payload));
        this.setMeta();
        this.edit = false;
        alert('Guardado ✔');
      },

      reset() {
        localStorage.removeItem(KEY);
        location.reload();
      },

      exportJSON() {
        const current = {
          seo: this.seo,
          hero: this.hero,
          cta: this.cta,
          features: this.features,
          pricing: this.pricing,
          testimonials: this.testimonials,
          faqs: this.faqs,
          contact: this.contact
        };
        const blob = new Blob([JSON.stringify(current, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'flexfood-landing.json'; a.click();
        URL.revokeObjectURL(url);
      },

      importJSON(e) {
        const f = e.target.files?.[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = ev => {
          try {
            const data = JSON.parse(ev.target.result);

            this.seo  = { ...this.seo,  ...(data.seo  || {}) };
            this.hero = { ...this.hero, ...(data.hero || {}) };
            this.cta  = { ...this.cta,  ...(data.cta  || {}) };

            if (Array.isArray(data.features)) this.features = data.features;
            if (Array.isArray(data.pricing)) this.pricing = data.pricing;
            if (Array.isArray(data.testimonials)) this.testimonials = data.testimonials;
            if (Array.isArray(data.faqs)) this.faqs = data.faqs;

            if (data.contact) {
              this.contact = { ...this.contact, ...data.contact };
              this.contact.labels = { ...this.contact.labels, ...(data.contact.labels || {}) };
              this.contact.placeholders = { ...this.contact.placeholders, ...(data.contact.placeholders || {}) };
              if (Array.isArray(data.contact.bullets)) this.contact.bullets = data.contact.bullets;
            }

            this.setMeta();
            this.save();
          } catch {
            alert('JSON inválido');
          }
        };
        r.readAsText(f);
      },

      addPlan() {
        this.pricing.push({
          name: 'Nuevo Plan',
          price: '€19',
          period: '/mes',
          highlight: false,
          description: 'Descripción del plan',
          features: ['Característica 1', 'Característica 2'],
          cta: { text: 'Elegir', href: '#' }
        });
      },

      // Subida de imagen para el HERO
      async uploadHero(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        this.uploadErrorHero = '';
        this.uploadingHero = true;

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
          this.hero.image = url;
        } catch (err) {
          console.error(err);
          this.uploadErrorHero = 'No se pudo subir la imagen';
        } finally {
          this.uploadingHero = false;
          e.target.value = '';
        }
      }
    };
  }
</script>

<script>
 function contactForm() {
  return {
    name:'', email:'', phone:'', message:'',
    loading:false, ok:null, error:null,

    validate() {
      if (!this.name?.trim()) return 'Por favor, escribe tu nombre.';
      if (!this.email?.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) return 'Email inválido.';
      if (!this.message || this.message.trim().length < 10) return 'El mensaje debe tener al menos 10 caracteres.';
      return null;
    },

    async submit() {
      this.ok = this.error = null;
      const v = this.validate();
      if (v) { this.error = v; return; }

      this.loading = true;
      try {
        const res = await fetch(@json(route('landing.contact')), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token()),
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            name: this.name, email: this.email, phone: this.phone, message: this.message
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || ($root?.contact?.errorMessage ?? 'No se pudo enviar'));

        this.ok = $root?.contact?.successMessage ?? '¡Gracias! Te contactaremos muy pronto.';
        this.name = this.email = this.phone = this.message = '';
        setTimeout(() => this.ok = null, 6000);
      } catch (e) {
        this.error = e.message || ($root?.contact?.errorMessage ?? 'Error inesperado');
      } finally {
        this.loading = false;
      }
    }
  }
}

</script>

</body>

</html>
{{-- Modal: Editar --}}
<div
    x-show="showEdit"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.escape.window="closeEdit()">
    <div class="absolute inset-0 bg-black/50" x-transition.opacity></div>

    <div class="relative bg-white w-full max-w-4xl rounded-2xl shadow p-6 max-h-[90vh] overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" @click="closeEdit()">✕</button>
        <h2 class="text-xl font-semibold text-[#153958] mb-4">Editar Restaurante</h2>

        <form method="POST" :action="editForm.updateUrl" class="space-y-6">
            @csrf @method('PUT')

            {{-- Información Básica --}}
            <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Información Básica</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" x-model="editForm.nombre" class="mt-1 w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Slug</label>
                        <input type="text" name="slug" x-model="editForm.slug" class="mt-1 w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Si lo cambias, se actualizarán las URLs con /r/{slug}.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Plan</label>
                    <select name="plan" x-model="editForm.plan" class="mt-1 w-full border rounded px-3 py-2">
                        <option value="">Legacy (sin plan)</option>
                        <option value="basic">Basic</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Basic: 50 platos, 15 QR, 3 perfiles. Advanced: platos ilimitados, 30 QR, 7 perfiles. Legacy: sin límites y permite video.
                    </p>
                </div>
            </div>

            {{-- Datos Fiscales --}}
            <div class="bg-blue-50 rounded-lg p-4 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Datos Fiscales</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Razón Social</label>
                        <input type="text" name="razon_social" x-model="editForm.razon_social" class="mt-1 w-full border rounded px-3 py-2" placeholder="Ej. Mi Restaurante S.L.">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nombre Comercial</label>
                        <input type="text" name="nombre_comercial" x-model="editForm.nombre_comercial" class="mt-1 w-full border rounded px-3 py-2" placeholder="Nombre comercial">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NIF/CIF</label>
                        <input type="text" name="nif" x-model="editForm.nif" maxlength="9" class="mt-1 w-full border rounded px-3 py-2 uppercase" placeholder="B12345678">
                    </div>
                </div>
            </div>

            {{-- Dirección Fiscal --}}
            <div class="bg-yellow-50 rounded-lg p-4 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Dirección Fiscal</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Dirección Completa</label>
                        <input type="text" name="direccion_fiscal" x-model="editForm.direccion_fiscal" class="mt-1 w-full border rounded px-3 py-2" placeholder="Calle Principal 123, 3º B">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Municipio</label>
                        <input type="text" name="municipio" x-model="editForm.municipio" class="mt-1 w-full border rounded px-3 py-2" placeholder="Madrid">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Provincia</label>
                        <input type="text" name="provincia" x-model="editForm.provincia" class="mt-1 w-full border rounded px-3 py-2" placeholder="Madrid">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Código Postal</label>
                        <input type="text" name="codigo_postal" x-model="editForm.codigo_postal" maxlength="5" class="mt-1 w-full border rounded px-3 py-2" placeholder="28001">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">País</label>
                        <input type="text" name="pais" x-model="editForm.pais" class="mt-1 w-full border rounded px-3 py-2">
                    </div>
                </div>
            </div>

            {{-- Información Fiscal --}}
            <div class="bg-green-50 rounded-lg p-4 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Información Fiscal</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Régimen de IVA</label>
                        <select name="regimen_iva" x-model="editForm.regimen_iva" class="mt-1 w-full border rounded px-3 py-2">
                            <option value="">Seleccionar...</option>
                            <option value="general">Régimen General</option>
                            <option value="simplificado">Régimen Simplificado</option>
                            <option value="criterio_caja">Criterio de Caja</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Epígrafe IAE</label>
                        <input type="text" name="epigrafe_iae" x-model="editForm.epigrafe_iae" class="mt-1 w-full border rounded px-3 py-2" placeholder="671">
                        <p class="text-xs text-gray-500 mt-1">Restaurantes suelen usar el 671</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Email Fiscal</label>
                        <input type="email" name="email_fiscal" x-model="editForm.email_fiscal" class="mt-1 w-full border rounded px-3 py-2" placeholder="facturacion@restaurante.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Teléfono Fiscal</label>
                        <input type="text" name="telefono_fiscal" x-model="editForm.telefono_fiscal" class="mt-1 w-full border rounded px-3 py-2" placeholder="912 345 678">
                    </div>
                </div>
            </div>

            {{-- Usuarios --}}
            <div class="bg-purple-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Usuarios Asignados</h3>
                <select id="edit-usuarios" name="usuarios[]" multiple class="w-full border rounded px-3 py-2 h-40">
                    @foreach($users as $u)
                        @php $rid = (int) ($u->restaurante_id ?? 0); @endphp
                        <option value="{{ $u->id }}"
                                x-show="{{ $rid === 0 ? 'true' : 'editForm.id === ' . $rid }}"
                                x-bind:disabled="{{ $rid === 0 ? 'false' : 'editForm.id !== ' . $rid }}">
                            {{ $u->name }} ({{ $u->email }}){{ $rid ? ' — asignado' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-2">Los usuarios no seleccionados serán desasignados.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="closeEdit()" class="px-4 py-2 bg-gray-100 rounded">Cancelar</button>
                <button class="px-4 py-2 bg-[#3CB28B] text-white rounded">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div
    x-show="openCreate"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded shadow-lg w-full max-w-lg p-6" @click.away="openCreate = false">
        <h2 class="text-xl font-bold mb-4">Crear Usuario</h2>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="mb-3">
                <label class="block">Nombre</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="block">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="block">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="block">Rol</label>
                <select name="role" class="form-control" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="openCreate = false" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<div
    x-show="openEdit === {{ $user->id }}"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded shadow-lg w-full max-w-lg p-6" @click.away="openEdit = null">
        <h2 class="text-xl font-bold mb-4">Editar Usuario</h2>
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="block">Nombre</label>
                <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="block">Email</label>
                <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="block">Contraseña (opcional)</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="mb-3">
                <label class="block">Rol</label>
                <select name="role" class="form-control" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @if($user->hasRole($role->name)) selected @endif>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="openEdit = null" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>

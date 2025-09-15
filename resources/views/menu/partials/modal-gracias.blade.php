<!-- resources/views/menu/partials/modal-gracias.blade.php -->
<div
    x-show="mostrarGraciasModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.away="mostrarGraciasModal = false">
    <div class="bg-white rounded-lg p-6 shadow-xl w-80 text-center">
        <h2 class="text-2xl font-bold text-[#153958] mb-4">🎉 ¡Pedido realizado!</h2>
        <p class="text-gray-600 mb-4">Tu pedido ha sido enviado exitosamente. Pronto será atendido por nuestro equipo.</p>
        <button
            @click="mostrarGraciasModal = false"
            class="bg-[#3CB28B] text-white px-4 py-2 rounded hover:bg-[#2e9e75]">
            Cerrar y volver al menú
        </button>
    </div>
</div>

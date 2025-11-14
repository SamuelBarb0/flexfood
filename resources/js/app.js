import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Crear store de órdenes ANTES de iniciar Alpine
Alpine.store('ordenes', {
    nuevas: parseInt(localStorage.getItem('ordenesNuevas') || 0),
    enPreparacion: parseInt(localStorage.getItem('ordenesEnPreparacion') || 0),

    actualizarNuevas(valor) {
        const valorNum = parseInt(valor);
        console.log('📦 Store - Actualizando nuevas:', this.nuevas, '->', valorNum);

        if (this.nuevas !== valorNum) {
            this.nuevas = valorNum;
            localStorage.setItem('ordenesNuevas', valorNum);
            console.log('✨ Store actualizado (nuevas):', valorNum);
        }
    },

    actualizarEnPreparacion(valor) {
        const valorNum = parseInt(valor);
        console.log('🔧 Store - Actualizando en preparación:', this.enPreparacion, '->', valorNum);

        if (this.enPreparacion !== valorNum) {
            this.enPreparacion = valorNum;
            localStorage.setItem('ordenesEnPreparacion', valorNum);
            console.log('✨ Store actualizado (en preparación):', valorNum);
        }
    }
});

console.log('✅ Store de órdenes creado:', {
    nuevas: Alpine.store('ordenes').nuevas,
    enPreparacion: Alpine.store('ordenes').enPreparacion
});

Alpine.start();

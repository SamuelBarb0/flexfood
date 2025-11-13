import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Crear store de órdenes ANTES de iniciar Alpine
Alpine.store('ordenes', {
    nuevas: parseInt(localStorage.getItem('ordenesNuevas') || 0),

    actualizarNuevas(valor) {
        const valorNum = parseInt(valor);
        console.log('📦 Store - Actualizando nuevas:', this.nuevas, '->', valorNum);

        if (this.nuevas !== valorNum) {
            this.nuevas = valorNum;
            localStorage.setItem('ordenesNuevas', valorNum);
            console.log('✨ Store actualizado:', valorNum);
        }
    }
});

console.log('✅ Store de órdenes creado con valor inicial:', Alpine.store('ordenes').nuevas);

Alpine.start();

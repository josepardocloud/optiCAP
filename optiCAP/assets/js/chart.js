// Placeholder para Chart.js - En producción usar la versión oficial
console.log('Chart.js cargado - En producción usar la versión oficial');

// Implementación básica de Chart para evitar errores
window.Chart = class Chart {
    constructor(ctx, config) {
        this.ctx = ctx;
        this.config = config;
        console.log('Chart inicializado', config);
    }
};
// Funcionalidades específicas para el módulo de reportes
class ReportManager {
    constructor() {
        this.currentFilters = {};
        this.charts = {};
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initDatePickers();
        this.loadInitialData();
    }

    initEventListeners() {
        // Filtros en tiempo real
        document.querySelectorAll('.filter-real-time').forEach(filter => {
            filter.addEventListener('change', () => this.applyFilters());
        });

        // Botones de exportación
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.exportReport(e));
        });

        // Botones de actualización
        document.querySelectorAll('.refresh-btn').forEach(btn => {
            btn.addEventListener('click', () => this.refreshData());
        });

        // Tooltips
        this.initTooltips();

        // Gráficos responsivos
        window.addEventListener('resize', () => this.handleResize());
    }

    initDatePickers() {
        // Inicializar datepickers si se usan librerías externas
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.addEventListener('change', () => this.validateDateRange(input));
        });
    }

    validateDateRange(changedInput) {
        const startDate = document.querySelector('input[name="fecha_inicio"]');
        const endDate = document.querySelector('input[name="fecha_fin"]');

        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);

            if (start > end) {
                if (changedInput === startDate) {
                    endDate.value = startDate.value;
                } else {
                    startDate.value = endDate.value;
                }
                this.showMessage('Las fechas se han ajustado para mantener un rango válido', 'warning');
            }
        }
    }

    initTooltips() {
        // Inicializar tooltips de Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    async loadInitialData() {
        try {
            // Cargar datos iniciales para los gráficos
            await this.loadChartsData();
            this.updateSummaryMetrics();
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showMessage('Error al cargar los datos iniciales', 'error');
        }
    }

    async loadChartsData() {
        // Cargar datos para los gráficos via AJAX
        const endpoints = {
            'slaGeneral': '<?php echo BASE_URL; ?>api/reportes/sla/general',
            'desempenoUsuarios': '<?php echo BASE_URL; ?>api/reportes/desempeno/usuarios',
            'tendencias': '<?php echo BASE_URL; ?>api/reportes/tendencias'
        };

        for (const [chartName, endpoint] of Object.entries(endpoints)) {
            try {
                const response = await fetch(endpoint);
                const data = await response.json();
                this.renderChart(chartName, data);
            } catch (error) {
                console.error(`Error loading chart ${chartName}:`, error);
            }
        }
    }

    renderChart(chartName, data) {
        const canvas = document.getElementById(`chart${chartName.charAt(0).toUpperCase() + chartName.slice(1)}`);
        if (!canvas) return;

        // Destruir gráfico existente si existe
        if (this.charts[chartName]) {
            this.charts[chartName].destroy();
        }

        const ctx = canvas.getContext('2d');
        
        switch (chartName) {
            case 'slaGeneral':
                this.charts[chartName] = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Dentro del SLA', 'Fuera del SLA'],
                        datasets: [{
                            data: [data.dentroSLA, data.fueraSLA],
                            backgroundColor: ['#28a745', '#dc3545']
                        }]
                    },
                    options: this.getChartOptions('SLA General')
                });
                break;

            case 'desempenoUsuarios':
                this.charts[chartName] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Eficiencia (%)',
                            data: data.values,
                            backgroundColor: data.values.map(v => 
                                v >= 90 ? '#28a745' : v >= 80 ? '#ffc107' : '#dc3545'
                            )
                        }]
                    },
                    options: this.getChartOptions('Desempeño por Usuario', 'bar')
                });
                break;

            case 'tendencias':
                this.charts[chartName] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Tendencia',
                            data: data.values,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: this.getChartOptions('Tendencia', 'line')
                });
                break;
        }
    }

    getChartOptions(title, type = 'doughnut') {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: type === 'doughnut' ? 'bottom' : 'top',
                },
                title: {
                    display: true,
                    text: title
                }
            }
        };

        if (type === 'bar' || type === 'line') {
            baseOptions.scales = {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + (type === 'bar' ? '%' : '');
                        }
                    }
                }
            };
        }

        return baseOptions;
    }

    async applyFilters() {
        this.showLoading();
        
        try {
            const formData = new FormData(document.querySelector('form'));
            this.currentFilters = Object.fromEntries(formData);
            
            // Simular carga de datos filtrados
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            await this.loadChartsData();
            this.updateSummaryMetrics();
            this.updateFilterSummary();
            
            this.showMessage('Filtros aplicados correctamente', 'success');
        } catch (error) {
            console.error('Error applying filters:', error);
            this.showMessage('Error al aplicar los filtros', 'error');
        } finally {
            this.hideLoading();
        }
    }

    updateSummaryMetrics() {
        // Actualizar métricas resumen
        const metrics = {
            'totalRequerimientos': 150,
            'requerimientosCompletados': 120,
            'tasaEficiencia': 80,
            'tiempoPromedio': 18.5
        };

        for (const [key, value] of Object.entries(metrics)) {
            const element = document.getElementById(key);
            if (element) {
                this.animateValue(element, 0, value, 1000);
            }
        }
    }

    animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            
            if (element.tagName === 'SPAN' || element.tagName === 'DIV') {
                element.textContent = element.id.includes('tasa') ? value + '%' : value;
            } else if (element.tagName === 'PROGRESS') {
                element.value = value;
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    updateFilterSummary() {
        const summaryElement = document.getElementById('filterSummary');
        if (!summaryElement) return;

        const activeFilters = Object.entries(this.currentFilters)
            .filter(([key, value]) => value && key !== 'csrf_token')
            .map(([key, value]) => {
                const label = this.getFilterLabel(key);
                return `${label}: ${value}`;
            });

        if (activeFilters.length > 0) {
            summaryElement.innerHTML = `
                <strong>Filtros activos:</strong> ${activeFilters.join(' | ')}
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="reportManager.clearFilters()">
                    <i class="fas fa-times me-1"></i>Limpiar
                </button>
            `;
            summaryElement.style.display = 'block';
        } else {
            summaryElement.style.display = 'none';
        }
    }

    getFilterLabel(key) {
        const labels = {
            'fecha_inicio': 'Fecha Inicio',
            'fecha_fin': 'Fecha Fin',
            'area_id': 'Área',
            'estado': 'Estado',
            'usuario_id': 'Usuario'
        };
        return labels[key] || key;
    }

    clearFilters() {
        document.querySelectorAll('select, input').forEach(element => {
            if (element.name && element.name !== 'csrf_token') {
                element.value = '';
            }
        });
        this.applyFilters();
    }

    async exportReport(event) {
        const format = event.target.dataset.format || 'pdf';
        const button = event.target;
        const originalText = button.innerHTML;

        try {
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exportando...';
            button.disabled = true;

            // Simular exportación
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // En una implementación real, aquí se haría la llamada al servidor
            const params = new URLSearchParams(this.currentFilters);
            params.append('format', format);
            
            window.open(`<?php echo BASE_URL; ?>api/reportes/exportar?${params.toString()}`, '_blank');
            
            this.showMessage(`Reporte exportado en formato ${format.toUpperCase()}`, 'success');
        } catch (error) {
            console.error('Error exporting report:', error);
            this.showMessage('Error al exportar el reporte', 'error');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    async refreshData() {
        this.showLoading();
        
        try {
            await this.loadChartsData();
            this.updateSummaryMetrics();
            this.showMessage('Datos actualizados correctamente', 'success');
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showMessage('Error al actualizar los datos', 'error');
        } finally {
            this.hideLoading();
        }
    }

    showLoading() {
        // Mostrar indicador de carga
        const loadingElement = document.getElementById('loadingIndicator') || this.createLoadingIndicator();
        loadingElement.style.display = 'block';
    }

    hideLoading() {
        const loadingElement = document.getElementById('loadingIndicator');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    createLoadingIndicator() {
        const loader = document.createElement('div');
        loader.id = 'loadingIndicator';
        loader.className = 'loading-overlay';
        loader.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Cargando datos...</p>
            </div>
        `;
        document.body.appendChild(loader);
        return loader;
    }

    showMessage(message, type = 'info') {
        // Mostrar mensaje toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.getElementById('messagesContainer') || this.createMessagesContainer();
        container.appendChild(toast);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    createMessagesContainer() {
        const container = document.createElement('div');
        container.id = 'messagesContainer';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    handleResize() {
        // Re-renderizar gráficos en redimensionamiento
        Object.values(this.charts).forEach(chart => {
            chart.resize();
        });
    }

    // Método para generar reportes personalizados
    generateCustomReport(config) {
        const {
            type,
            filters,
            columns,
            format = 'table'
        } = config;

        // Lógica para generar reportes personalizados
        console.log('Generating custom report:', config);
        
        // Retornar datos simulados
        return this.getSampleData(type);
    }

    getSampleData(reportType) {
        const sampleData = {
            'sla': {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                values: [85, 82, 88, 87, 90, 87.5]
            },
            'desempeno': {
                labels: ['Usuario 1', 'Usuario 2', 'Usuario 3', 'Usuario 4', 'Usuario 5'],
                values: [92, 85, 78, 95, 88]
            },
            'actividades': {
                labels: ['Actividad 1', 'Actividad 2', 'Actividad 3', 'Actividad 4'],
                values: [150, 120, 80, 200]
            }
        };

        return sampleData[reportType] || sampleData.sla;
    }
}

// Inicializar el manager de reportes cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.reportManager = new ReportManager();
    
    // Configuración adicional para tablas de datos
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        new DataTableManager(table);
    });
});

// Clase auxiliar para manejar tablas de datos
class DataTableManager {
    constructor(table) {
        this.table = table;
        this.init();
    }

    init() {
        this.addSearchFunctionality();
        this.addSortingFunctionality();
        this.addPaginationIfNeeded();
    }

    addSearchFunctionality() {
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar en la tabla...';
        searchInput.className = 'form-control mb-3';
        searchInput.style.maxWidth = '300px';

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = this.table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        this.table.parentNode.insertBefore(searchInput, this.table);
    }

    addSortingFunctionality() {
        const headers = this.table.querySelectorAll('th[data-sortable]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => this.sortTable(header));
        });
    }

    sortTable(header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = !header.classList.contains('sorted-asc');
        
        // Remover clases de ordenamiento
        this.table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
        });
        
        // Añadir clase actual
        header.classList.add(isAscending ? 'sorted-asc' : 'sorted-desc');
        
        // Ordenar tabla
        const tbody = this.table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            // Intentar convertir a número
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            } else {
                return isAscending ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            }
        });
        
        // Reinsertar filas ordenadas
        rows.forEach(row => tbody.appendChild(row));
    }

    addPaginationIfNeeded() {
        const rows = this.table.querySelectorAll('tbody tr');
        if (rows.length > 10) {
            this.createPagination(rows);
        }
    }

    createPagination(rows) {
        const itemsPerPage = 10;
        const pageCount = Math.ceil(rows.length / itemsPerPage);
        
        // Ocultar todas las filas inicialmente
        rows.forEach((row, index) => {
            row.style.display = index < itemsPerPage ? '' : 'none';
            row.dataset.page = Math.floor(index / itemsPerPage) + 1;
        });
        
        // Crear controles de paginación
        const pagination = document.createElement('div');
        pagination.className = 'pagination-container d-flex justify-content-between align-items-center mt-3';
        
        const pageInfo = document.createElement('span');
        pageInfo.className = 'page-info';
        
        const controls = document.createElement('div');
        controls.className = 'pagination-controls';
        
        // Botones de paginación
        const prevButton = this.createPaginationButton('Anterior', false);
        const nextButton = this.createPaginationButton('Siguiente', true);
        
        controls.appendChild(prevButton);
        controls.appendChild(nextButton);
        pagination.appendChild(pageInfo);
        pagination.appendChild(controls);
        
        this.table.parentNode.appendChild(pagination);
        
        let currentPage = 1;
        
        const updatePagination = () => {
            // Actualizar visibilidad de filas
            rows.forEach(row => {
                row.style.display = parseInt(row.dataset.page) === currentPage ? '' : 'none';
            });
            
            // Actualizar información de página
            pageInfo.textContent = `Página ${currentPage} de ${pageCount}`;
            
            // Actualizar estado de botones
            prevButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === pageCount;
        };
        
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
            }
        });
        
        nextButton.addEventListener('click', () => {
            if (currentPage < pageCount) {
                currentPage++;
                updatePagination();
            }
        });
        
        updatePagination();
    }

    createPaginationButton(text, isNext) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm btn-outline-primary ${isNext ? 'ms-1' : ''}`;
        button.innerHTML = isNext ? 
            `${text} <i class="fas fa-chevron-right ms-1"></i>` :
            `<i class="fas fa-chevron-left me-1"></i> ${text}`;
        return button;
    }
}

// Estilos CSS adicionales para reportes
const additionalStyles = `
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    text-align: center;
}

.data-table {
    width: 100%;
}

.pagination-container {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

th[data-sortable]:hover {
    background-color: #f8f9fa;
}

th.sorted-asc::after {
    content: ' ▲';
    font-size: 0.8em;
}

th.sorted-desc::after {
    content: ' ▼';
    font-size: 0.8em;
}
`;

// Añadir estilos al documento
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
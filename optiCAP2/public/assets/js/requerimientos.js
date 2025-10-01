class RequerimientosManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.initEventListeners();
        this.initFilters();
        this.initDataTable();
    }
    
    initEventListeners() {
        // Botones de acción rápida
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleAction(e);
            });
        });
        
        // Filtros en tiempo real
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('input', () => {
                this.applyFilters();
            });
        });
        
        // Exportación de reportes
        document.querySelectorAll('.btn-export').forEach(btn => {
            btn.addEventListener('click', () => {
                this.exportReport(btn.dataset.format);
            });
        });
    }
    
    initFilters() {
        // Inicializar select2 para filtros
        if (typeof jQuery !== 'undefined' && jQuery().select2) {
            $('.select2-filter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Seleccione una opción',
                allowClear: true
            });
        }
    }
    
    initDataTable() {
        // Inicializar DataTables si está disponible
        if (typeof jQuery !== 'undefined' && jQuery().DataTable) {
            $('#requerimientosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[0, 'desc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
            });
        }
    }
    
    handleAction(e) {
        e.preventDefault();
        const action = e.target.dataset.action;
        const requerimientoId = e.target.dataset.id;
        
        switch (action) {
            case 'view':
                this.viewRequerimiento(requerimientoId);
                break;
            case 'edit':
                this.editRequerimiento(requerimientoId);
                break;
            case 'print':
                this.printRequerimiento(requerimientoId);
                break;
            case 'report_incident':
                this.reportIncident(requerimientoId);
                break;
            case 'duplicate':
                this.duplicateRequerimiento(requerimientoId);
                break;
        }
    }
    
    viewRequerimiento(id) {
        window.location.href = `${SITE_URL}/requerimientos/detalle/${id}`;
    }
    
    editRequerimiento(id) {
        window.location.href = `${SITE_URL}/requerimientos/editar/${id}`;
    }
    
    printRequerimiento(id) {
        window.open(`${SITE_URL}/requerimientos/imprimir/${id}`, '_blank');
    }
    
    reportIncident(id) {
        window.location.href = `${SITE_URL}/incidencias/reportar?requerimiento_id=${id}`;
    }
    
    duplicateRequerimiento(id) {
        if (confirm('¿Está seguro de duplicar este requerimiento? Se creará una copia con un nuevo código.')) {
            this.showLoading();
            
            fetch(`${SITE_URL}/api/requerimientos/duplicar/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    this.showNotification('Requerimiento duplicado correctamente', 'success');
                    setTimeout(() => {
                        window.location.href = `${SITE_URL}/requerimientos/detalle/${data.new_id}`;
                    }, 1500);
                } else {
                    this.showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                this.hideLoading();
                this.showNotification('Error al duplicar el requerimiento', 'error');
                console.error('Error:', error);
            });
        }
    }
    
    applyFilters() {
        const filters = {};
        
        document.querySelectorAll('.filter-input').forEach(input => {
            if (input.value) {
                filters[input.name] = input.value;
            }
        });
        
        document.querySelectorAll('.select2-filter').forEach(select => {
            if (select.value) {
                filters[select.name] = select.value;
            }
        });
        
        // Construir URL con filtros
        const queryString = new URLSearchParams(filters).toString();
        window.location.href = `${SITE_URL}/requerimientos?${queryString}`;
    }
    
    exportReport(format) {
        const filters = this.getCurrentFilters();
        filters.format = format;
        
        const queryString = new URLSearchParams(filters).toString();
        window.open(`${SITE_URL}/reportes/exportar?${queryString}`, '_blank');
    }
    
    getCurrentFilters() {
        const filters = {};
        const urlParams = new URLSearchParams(window.location.search);
        
        for (const [key, value] of urlParams) {
            filters[key] = value;
        }
        
        return filters;
    }
    
    showLoading() {
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        `;
        document.body.appendChild(loading);
    }
    
    hideLoading() {
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            loading.remove();
        }
    }
    
    showNotification(message, type = 'info') {
        // Usar toast de Bootstrap si está disponible
        if (typeof bootstrap !== 'undefined') {
            const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
            const toast = this.createToast(message, type);
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        } else {
            // Fallback simple
            alert(message);
        }
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        return toast;
    }
    
    // Métodos para el dashboard de requerimientos
    updateProgressBar(requerimientoId, progress) {
        const progressBar = document.querySelector(`[data-requerimiento="${requerimientoId}"] .progress-bar`);
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.textContent = `${progress}%`;
            
            // Actualizar clase según el progreso
            progressBar.className = 'progress-bar';
            if (progress >= 100) {
                progressBar.classList.add('bg-success');
            } else if (progress >= 50) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-info');
            }
        }
    }
    
    // Métodos para la línea de tiempo
    initTimeline() {
        const timelineItems = document.querySelectorAll('.timeline-item');
        
        timelineItems.forEach(item => {
            item.addEventListener('click', () => {
                this.showActivityDetails(item.dataset.activityId);
            });
        });
    }
    
    showActivityDetails(activityId) {
        this.showLoading();
        
        fetch(`${SITE_URL}/api/actividades/detalle/${activityId}`)
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                this.displayActivityModal(data);
            })
            .catch(error => {
                this.hideLoading();
                this.showNotification('Error al cargar los detalles de la actividad', 'error');
                console.error('Error:', error);
            });
    }
    
    displayActivityModal(activity) {
        const modal = new bootstrap.Modal(document.getElementById('activityModal'));
        const modalBody = document.getElementById('activityModalBody');
        
        modalBody.innerHTML = `
            <h5>${activity.nombre}</h5>
            <p><strong>Descripción:</strong> ${activity.descripcion}</p>
            <p><strong>Estado:</strong> <span class="badge bg-${this.getStatusClass(activity.estado)}">${activity.estado}</span></p>
            <p><strong>Requisitos:</strong></p>
            <ul>
                ${activity.requisitos_obligatorios ? activity.requisitos_obligatorios.map(req => 
                    `<li>${this.getRequisitoName(req)}</li>`
                ).join('') : '<li>No hay requisitos obligatorios</li>'}
            </ul>
            ${activity.observaciones ? `<p><strong>Observaciones:</strong> ${activity.observaciones}</p>` : ''}
        `;
        
        modal.show();
    }
    
    getStatusClass(status) {
        const classes = {
            'pendiente': 'secondary',
            'en_proceso': 'primary',
            'finalizado': 'success',
            'rechazado': 'danger',
            'no_aplica': 'warning'
        };
        return classes[status] || 'secondary';
    }
    
    getRequisitoName(requisito) {
        const names = {
            'disponibilidad_presupuestal': 'Disponibilidad Presupuestal',
            'especificaciones_tecnicas': 'Especificaciones Técnicas',
            'terminos_referencia': 'Términos de Referencia',
            'pca_priorizacion': 'PCA y Priorización',
            'verificacion_especificaciones': 'Verificación de Especificaciones',
            'conformidad_servicio': 'Conformidad del Servicio',
            'informe_conformidad': 'Informe de Conformidad',
            'documentacion_contable': 'Documentación Contable'
        };
        return names[requisito] || requisito;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.requerimientosManager = new RequerimientosManager();
});

// Funciones globales para requerimientos
function confirmDeleteRequerimiento(id) {
    if (confirm('¿Está seguro de eliminar este requerimiento? Esta acción no se puede deshacer.')) {
        window.location.href = `${SITE_URL}/requerimientos/eliminar/${id}`;
    }
}

function updateRequerimientoStatus(id, status) {
    fetch(`${SITE_URL}/api/requerimientos/estado/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ estado: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error al actualizar el estado: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
    });
}
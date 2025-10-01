class ActividadesManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.initEventListeners();
        this.initValidation();
        this.initTimeline();
    }
    
    initEventListeners() {
        // Validación de requisitos antes de finalizar
        document.querySelectorAll('.btn-finalizar').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!this.validarRequisitos()) {
                    e.preventDefault();
                    this.mostrarErrorRequisitos();
                }
            });
        });
        
        // Control de evidencias
        document.querySelectorAll('.btn-agregar-evidencia').forEach(btn => {
            btn.addEventListener('click', () => {
                this.mostrarModalEvidencia();
            });
        });
        
        // Navegación entre actividades
        document.querySelectorAll('.nav-actividad').forEach(nav => {
            nav.addEventListener('click', (e) => {
                this.navegarActividad(e);
            });
        });
    }
    
    initValidation() {
        // Validación de formularios de actividad
        const forms = document.querySelectorAll('form[data-validate-actividad]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validarFormularioActividad(form)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    initTimeline() {
        // Inicializar línea de tiempo interactiva
        const timelineItems = document.querySelectorAll('.timeline-item');
        timelineItems.forEach(item => {
            item.addEventListener('click', () => {
                this.mostrarDetalleActividad(item);
            });
        });
    }
    
    validarRequisitos() {
        const requisitos = document.querySelectorAll('input[type="checkbox"][data-requisito]');
        let todosCumplidos = true;
        
        requisitos.forEach(req => {
            if (!req.checked) {
                todosCumplidos = false;
            }
        });
        
        return todosCumplidos;
    }
    
    mostrarErrorRequisitos() {
        const modal = new bootstrap.Modal(document.getElementById('errorRequisitosModal'));
        modal.show();
    }
    
    validarFormularioActividad(form) {
        const estado = form.querySelector('select[name="estado"]');
        const observaciones = form.querySelector('textarea[name="observaciones"]');
        
        if (estado.value === 'finalizado' && observaciones.value.trim().length < 10) {
            alert('Debe proporcionar observaciones detalladas al finalizar una actividad (mínimo 10 caracteres).');
            observaciones.focus();
            return false;
        }
        
        return true;
    }
    
    navegarActividad(e) {
        e.preventDefault();
        const actividadId = e.target.getAttribute('data-actividad-id');
        
        // Mostrar loading
        this.mostrarLoading();
        
        // Cargar actividad
        fetch(`${SITE_URL}/actividades/editar/${actividadId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('contenido-actividad').innerHTML = html;
                this.ocultarLoading();
            })
            .catch(error => {
                console.error('Error:', error);
                this.ocultarLoading();
                alert('Error al cargar la actividad');
            });
    }
    
    mostrarDetalleActividad(item) {
        const actividadId = item.getAttribute('data-actividad-id');
        const modal = new bootstrap.Modal(document.getElementById('detalleActividadModal'));
        
        // Cargar detalles de la actividad
        fetch(`${SITE_URL}/actividades/detalle/${actividadId}`)
            .then(response => response.json())
            .then(data => {
                this.actualizarModalDetalle(data);
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    actualizarModalDetalle(data) {
        const modal = document.getElementById('detalleActividadModal');
        modal.querySelector('.modal-title').textContent = data.nombre;
        modal.querySelector('.modal-body').innerHTML = this.generarHTMLDetalle(data);
    }
    
    generarHTMLDetalle(data) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <strong>Estado:</strong>
                    <span class="badge ${this.getClassEstado(data.estado)}">${data.estado}</span>
                </div>
                <div class="col-md-6">
                    <strong>Fecha Inicio:</strong>
                    ${data.fecha_inicio || 'No iniciada'}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <strong>Descripción:</strong>
                    <p>${data.descripcion}</p>
                </div>
            </div>
            ${data.observaciones ? `
            <div class="row mt-2">
                <div class="col-12">
                    <strong>Observaciones:</strong>
                    <p>${data.observaciones}</p>
                </div>
            </div>
            ` : ''}
        `;
    }
    
    getClassEstado(estado) {
        const clases = {
            'pendiente': 'bg-warning',
            'en_proceso': 'bg-primary',
            'finalizado': 'bg-success',
            'rechazado': 'bg-danger',
            'no_aplica': 'bg-secondary'
        };
        return clases[estado] || 'bg-secondary';
    }
    
    mostrarLoading() {
        // Implementar loading spinner
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        `;
        document.body.appendChild(loading);
    }
    
    ocultarLoading() {
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            loading.remove();
        }
    }
    
    // Métodos para el manejo de evidencias
    mostrarModalEvidencia() {
        const modal = new bootstrap.Modal(document.getElementById('evidenciaModal'));
        modal.show();
    }
    
    subirEvidencia(formData) {
        return fetch(`${SITE_URL}/api/evidencias/subir`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.agregarEvidenciaLista(data.evidencia);
                return data;
            } else {
                throw new Error(data.error);
            }
        });
    }
    
    agregarEvidenciaLista(evidencia) {
        const lista = document.getElementById('lista-evidencias');
        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-file-${this.getIconoTipo(evidencia.tipo_archivo)} me-2"></i>
                    ${evidencia.nombre_original}
                </div>
                <div>
                    <a href="${SITE_URL}${evidencia.ruta}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" onclick="actividadesManager.eliminarEvidencia(${evidencia.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        lista.appendChild(item);
    }
    
    eliminarEvidencia(evidenciaId) {
        if (confirm('¿Está seguro de eliminar esta evidencia?')) {
            fetch(`${SITE_URL}/api/evidencias/eliminar/${evidenciaId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-evidencia-id="${evidenciaId}"]`).remove();
                } else {
                    alert('Error al eliminar la evidencia');
                }
            });
        }
    }
    
    getIconoTipo(tipoArchivo) {
        const iconos = {
            'pdf': 'pdf',
            'doc': 'word',
            'docx': 'word',
            'xls': 'excel',
            'xlsx': 'excel',
            'jpg': 'image',
            'jpeg': 'image',
            'png': 'image'
        };
        
        const extension = tipoArchivo.split('/').pop();
        return iconos[extension] || 'file';
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.actividadesManager = new ActividadesManager();
});

// Funciones globales para actividades
function validarAntesDeFinalizar() {
    const manager = window.actividadesManager;
    if (!manager.validarRequisitos()) {
        manager.mostrarErrorRequisitos();
        return false;
    }
    return true;
}

function toggleRequisito(checkbox) {
    const manager = window.actividadesManager;
    // Lógica adicional si es necesario
}
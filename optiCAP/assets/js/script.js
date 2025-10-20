// Script principal para OptiCAP
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Manejo de filtros en tablas
    const filtros = document.querySelectorAll('#estado, #tipo, #fecha_desde, #fecha_hasta');
    filtros.forEach(filtro => {
        filtro.addEventListener('change', function() {
            filtrarTabla();
        });
    });

    // Función para filtrar tabla de requerimientos
    function filtrarTabla() {
        const estado = document.getElementById('estado').value.toLowerCase();
        const tipo = document.getElementById('tipo').value;
        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = document.getElementById('fecha_hasta').value;
        
        const filas = document.querySelectorAll('#tablaRequerimientos tbody tr');
        
        filas.forEach(fila => {
            let mostrar = true;
            const celdas = fila.getElementsByTagName('td');
            
            // Filtrar por estado
            if (estado) {
                const estadoFila = celdas[5].textContent.toLowerCase().replace('_', ' ');
                if (!estadoFila.includes(estado)) {
                    mostrar = false;
                }
            }
            
            // Filtrar por tipo
            if (tipo) {
                const tipoFila = celdas[1].textContent;
                if (tipoFila !== tipo) {
                    mostrar = false;
                }
            }
            
            // Filtrar por fecha
            if (fechaDesde || fechaHasta) {
                const fechaFila = celdas[4].textContent.split(' ')[0].split('/').reverse().join('-');
                const fechaFilaDate = new Date(fechaFila);
                
                if (fechaDesde) {
                    const desdeDate = new Date(fechaDesde);
                    if (fechaFilaDate < desdeDate) {
                        mostrar = false;
                    }
                }
                
                if (fechaHasta) {
                    const hastaDate = new Date(fechaHasta);
                    if (fechaFilaDate > hastaDate) {
                        mostrar = false;
                    }
                }
            }
            
            fila.style.display = mostrar ? '' : 'none';
        });
    }

    // Confirmación para acciones importantes
    const accionesPeligrosas = document.querySelectorAll('.btn-danger, .btn-warning');
    accionesPeligrosas.forEach(boton => {
        boton.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea realizar esta acción?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Manejo de formularios con validación
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                mostrarToast('Por favor, complete todos los campos obligatorios.', 'error');
            }
        });
    });

    // Función para mostrar notificaciones toast
    function mostrarToast(mensaje, tipo = 'info') {
        // Crear contenedor de toasts si no existe
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Crear toast
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${tipo === 'error' ? 'danger' : tipo} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Mostrar toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remover toast después de cerrar
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Cargar datos para gráficos si existen
    const ctx = document.getElementById('dashboardChart');
    if (ctx) {
        cargarGraficoDashboard();
    }

    function cargarGraficoDashboard() {
        // Aquí se cargarían los datos reales del dashboard
        const data = {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Requerimientos Completados',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 2,
                tension: 0.4
            }, {
                label: 'Requerimientos Pendientes',
                data: [8, 12, 6, 4, 7, 5],
                backgroundColor: 'rgba(243, 156, 18, 0.2)',
                borderColor: 'rgba(243, 156, 18, 1)',
                borderWidth: 2,
                tension: 0.4
            }]
        };
        
        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Evolución de Requerimientos'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            },
        };
        
        new Chart(ctx, config);
    }

    // Manejo de carga de archivos
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Ningún archivo seleccionado';
            const label = this.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName;
            }
        });
    });

    // Funcionalidad de búsqueda en tiempo real
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                Array.from(rows).forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });

    // Inicializar datepickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.valueAsDate = new Date();
        }
    });

    console.log('OptiCAP System initialized successfully');
});

// Funciones globales
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Mejorar interactividad
    const cards = document.querySelectorAll('.stat-card-white');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Función para vista previa de imagen
function previewImage(input) {
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Por favor, seleccione una imagen válida (JPG, PNG, GIF)');
            input.value = '';
            return;
        }
        
        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            imagePreview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    }
}

// Función para vista previa de imagen
function previewImage(input) {
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Por favor, seleccione una imagen válida (JPG, PNG, GIF)');
            input.value = '';
            return;
        }
        
        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe superar los 2MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            imagePreview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    }
}

// Función para eliminar imagen seleccionada
function removeImage() {
    const input = document.getElementById('evidencia');
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    
    input.value = '';
    preview.src = '#';
    imagePreview.style.display = 'none';
}
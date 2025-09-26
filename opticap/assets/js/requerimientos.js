// Funcionalidades específicas para el módulo de requerimientos
class RequerimientoManager {
    constructor() {
        this.initEventListeners();
        this.initDatePickers();
    }

    initEventListeners() {
        // Validación de formularios
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Contador de caracteres para descripciones
        const textareas = document.querySelectorAll('textarea[maxlength]');
        textareas.forEach(textarea => {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.innerHTML = `<span>0</span> / ${textarea.maxLength} caracteres`;
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', function() {
                const count = this.value.length;
                counter.querySelector('span').textContent = count;
                
                if (count > textarea.maxLength * 0.9) {
                    counter.classList.add('text-warning');
                } else {
                    counter.classList.remove('text-warning');
                }
            });

            // Inicializar contador
            textarea.dispatchEvent(new Event('input'));
        });

        // Preview de archivos antes de subir
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const preview = document.getElementById(this.getAttribute('data-preview'));
                if (preview && this.files.length > 0) {
                    const file = this.files[0];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });
    }

    initDatePickers() {
        // Inicializar datepickers si existen
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Establecer fecha mínima/máxima según el contexto
            if (input.name === 'fecha_inicio') {
                input.min = '2020-01-01';
                input.max = new Date().toISOString().split('T')[0];
            } else if (input.name === 'fecha_fin') {
                const fechaInicio = document.querySelector('input[name="fecha_inicio"]');
                if (fechaInicio && fechaInicio.value) {
                    input.min = fechaInicio.value;
                }
                input.max = new Date().toISOString().split('T')[0];
            }
        });
    }

    // Método para calcular días hábiles entre dos fechas
    calcularDiasHabiles(fechaInicio, fechaFin) {
        const start = new Date(fechaInicio);
        const end = new Date(fechaFin);
        let count = 0;
        
        while (start <= end) {
            const day = start.getDay();
            if (day !== 0 && day !== 6) { // Excluir sábado y domingo
                count++;
            }
            start.setDate(start.getDate() + 1);
        }
        
        return count;
    }

    // Método para actualizar el progreso de un requerimiento
    actualizarProgreso(requerimientoId) {
        fetch(`<?php echo BASE_URL; ?>api/requerimientos/${requerimientoId}/progreso`)
            .then(response => response.json())
            .then(data => {
                const progressBar = document.querySelector(`[data-requerimiento="${requerimientoId}"] .progress-bar`);
                const progressText = document.querySelector(`[data-requerimiento="${requerimientoId}"] .progress-text`);
                
                if (progressBar) {
                    progressBar.style.width = `${data.porcentaje}%`;
                    progressBar.textContent = `${data.porcentaje}%`;
                }
                
                if (progressText) {
                    progressText.textContent = `${data.completadas}/${data.total} actividades`;
                }
            })
            .catch(error => {
                console.error('Error al actualizar progreso:', error);
            });
    }

    // Método para notificar cambios en actividades
    notificarCambioActividad(actividadId, mensaje) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('OptiCAP - Cambio en Actividad', {
                body: mensaje,
                icon: '<?php echo BASE_URL; ?>assets/images/logo.png'
            });
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.requerimientoManager = new RequerimientoManager();
    
    // Configurar notificaciones si el navegador las soporta
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Funcionalidad de búsqueda en tiempo real
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            const filter = this.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        });
    });

    // Funcionalidad de ordenamiento de tablas
    const tableHeaders = document.querySelectorAll('th[data-sort]');
    tableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            const isAscending = this.classList.contains('sort-asc');
            
            // Remover clases de ordenamiento de todos los headers
            tableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Ordenar la tabla
            this.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
            sortTable(table, columnIndex, !isAscending);
        });
    });

    function sortTable(table, columnIndex, ascending) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            // Intentar convertir a número si es posible
            const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return ascending ? aNum - bNum : bNum - aNum;
            } else {
                return ascending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            }
        });
        
        // Reinsertar filas ordenadas
        rows.forEach(row => tbody.appendChild(row));
    }
});

// Funciones para el manejo de evidencias
class EvidenceManager {
    static previewEvidence(fileInput, previewContainer) {
        if (fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">`;
                } else {
                    previewContainer.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-file me-2"></i>
                            ${file.name} (${EvidenceManager.formatFileSize(file.size)})
                        </div>
                    `;
                }
            };
            
            reader.readAsDataURL(file);
        }
    }

    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    static validateFile(file, maxSize, allowedTypes) {
        if (file.size > maxSize) {
            return `El archivo excede el tamaño máximo permitido (${EvidenceManager.formatFileSize(maxSize)})`;
        }
        
        const extension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(extension)) {
            return `Tipo de archivo no permitido. Formatos aceptados: ${allowedTypes.join(', ')}`;
        }
        
        return null;
    }
}
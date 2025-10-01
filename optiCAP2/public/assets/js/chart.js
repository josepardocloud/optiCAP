// Configuración global de Chart.js
Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

class ChartManager {
    constructor() {
        this.charts = new Map();
        this.init();
    }
    
    init() {
        this.initDashboardCharts();
        this.initReportCharts();
        this.initResponsiveCharts();
    }
    
    initDashboardCharts() {
        // Gráfico de cumplimiento SLA
        this.initSLAChart();
        
        // Gráfico de requerimientos por estado
        this.initRequerimientosEstadoChart();
        
        // Gráfico de actividades por estado
        this.initActividadesEstadoChart();
        
        // Gráfico de tendencias mensuales
        this.initTendenciasChart();
    }
    
    initSLAChart() {
        const ctx = document.getElementById('slaChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Cumplimiento SLA (%)',
                    data: [85, 78, 90, 88, 92, 95, 89, 93, 96, 94, 97, 98],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `Cumplimiento: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                }
            }
        });
        
        this.charts.set('sla', chart);
    }
    
    initRequerimientosEstadoChart() {
        const ctx = document.getElementById('requerimientosEstadoChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completados', 'En Proceso', 'Pendientes', 'Cancelados'],
                datasets: [{
                    data: [45, 30, 20, 5],
                    backgroundColor: [
                        '#1cc88a',
                        '#4e73df',
                        '#f6c23e',
                        '#e74a3b'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        this.charts.set('requerimientosEstado', chart);
    }
    
    initActividadesEstadoChart() {
        const ctx = document.getElementById('actividadesEstadoChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Paso 01', 'Paso 02', 'Paso 03', 'Paso 04', 'Paso 05', 'Paso 06', 'Paso 07', 'Paso 08', 'Paso 09', 'Paso 10', 'Paso 11', 'Paso 12', 'Paso 13', 'Paso 14'],
                datasets: [{
                    label: 'Finalizadas',
                    data: [95, 85, 90, 88, 92, 87, 94, 91, 89, 93, 96, 90, 88, 85],
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }, {
                    label: 'En Proceso',
                    data: [5, 10, 8, 10, 6, 11, 4, 7, 9, 5, 3, 8, 10, 12],
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }, {
                    label: 'Pendientes',
                    data: [0, 5, 2, 2, 2, 2, 2, 2, 2, 2, 1, 2, 2, 3],
                    backgroundColor: '#f6c23e',
                    borderColor: '#f6c23e',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        this.charts.set('actividadesEstado', chart);
    }
    
    initTendenciasChart() {
        const ctx = document.getElementById('tendenciasChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Bienes',
                    data: [12, 15, 18, 14, 20, 22],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Servicios',
                    data: [8, 10, 12, 15, 18, 20],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Requerimientos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Meses'
                        }
                    }
                }
            }
        });
        
        this.charts.set('tendencias', chart);
    }
    
    initReportCharts() {
        // Inicializar gráficos específicos para reportes
        this.initComparativaChart();
        this.initTiemposPromedioChart();
        this.initSaltosCondicionalesChart();
    }
    
    initComparativaChart() {
        const ctx = document.getElementById('comparativaChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Tiempo Promedio', 'Tasa de Éxito', 'SLA Cumplido', 'Saltos Condicionales', 'Requisitos Cumplidos'],
                datasets: [{
                    label: 'Bienes',
                    data: [85, 90, 88, 75, 92],
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df'
                }, {
                    label: 'Servicios',
                    data: [78, 85, 82, 60, 88],
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: '#1cc88a',
                    pointBackgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
        
        this.charts.set('comparativa', chart);
    }
    
    initTiemposPromedioChart() {
        const ctx = document.getElementById('tiemposPromedioChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Paso 01', 'Paso 02', 'Paso 03', 'Paso 04', 'Paso 05', 'Paso 06', 'Paso 07', 'Paso 08', 'Paso 09', 'Paso 10', 'Paso 11', 'Paso 12', 'Paso 13', 'Paso 14'],
                datasets: [{
                    label: 'Tiempo Real (horas)',
                    data: [2, 8, 4, 6, 12, 8, 6, 4, 3, 2, 1, 24, 6, 8],
                    backgroundColor: '#4e73df'
                }, {
                    label: 'Tiempo Estimado (horas)',
                    data: [2, 8, 4, 6, 12, 8, 6, 4, 3, 2, 1, 24, 6, 8],
                    backgroundColor: '#1cc88a',
                    type: 'line',
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    }
                }
            }
        });
        
        this.charts.set('tiemposPromedio', chart);
    }
    
    initSaltosCondicionalesChart() {
        const ctx = document.getElementById('saltosCondicionalesChart');
        if (!ctx) return;
        
        const chart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Con Salto Condicional', 'Sin Salto Condicional'],
                datasets: [{
                    data: [65, 35],
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                }
            }
        });
        
        this.charts.set('saltosCondicionales', chart);
    }
    
    initResponsiveCharts() {
        // Hacer los gráficos responsive
        window.addEventListener('resize', () => {
            this.charts.forEach(chart => {
                chart.resize();
            });
        });
    }
    
    // Métodos para actualizar datos dinámicamente
    updateChart(chartName, newData) {
        const chart = this.charts.get(chartName);
        if (chart) {
            chart.data.datasets[0].data = newData;
            chart.update();
        }
    }
    
    updateChartLabels(chartName, newLabels) {
        const chart = this.charts.get(chartName);
        if (chart) {
            chart.data.labels = newLabels;
            chart.update();
        }
    }
    
    // Métodos para cargar datos desde API
    loadChartDataFromAPI(chartName, endpoint) {
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                this.updateChart(chartName, data);
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }
    
    // Métodos para exportar gráficos
    exportChartAsImage(chartName, format = 'png') {
        const chart = this.charts.get(chartName);
        if (chart) {
            const image = chart.toBase64Image();
            const link = document.createElement('a');
            link.download = `chart-${chartName}-${new Date().toISOString().split('T')[0]}.${format}`;
            link.href = image;
            link.click();
        }
    }
    
    // Métodos para interactividad
    addChartClickListener(chartName, callback) {
        const chart = this.charts.get(chartName);
        if (chart) {
            chart.canvas.onclick = callback;
        }
    }
    
    // Métodos para temas
    applyTheme(theme) {
        const isDark = theme === 'dark';
        const textColor = isDark ? '#ffffff' : '#6c757d';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        Chart.defaults.color = textColor;
        
        this.charts.forEach(chart => {
            chart.options.scales.x.grid.color = gridColor;
            chart.options.scales.y.grid.color = gridColor;
            chart.update();
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.chartManager = new ChartManager();
});

// Funciones utilitarias para gráficos
function formatChartNumber(value) {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    } else if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K';
    }
    return value;
}

function getChartColors(count) {
    const baseColors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
    ];
    
    if (count <= baseColors.length) {
        return baseColors.slice(0, count);
    }
    
    // Generar colores adicionales si es necesario
    const additionalColors = [];
    for (let i = baseColors.length; i < count; i++) {
        const hue = (i * 137.508) % 360; // Usar el ángulo dorado
        additionalColors.push(`hsl(${hue}, 70%, 60%)`);
    }
    
    return [...baseColors, ...additionalColors];
}

// Plugin personalizado para tooltips
const customTooltipPlugin = {
    id: 'customTooltip',
    beforeDraw: function(chart) {
        if (chart.tooltip._active && chart.tooltip._active.length) {
            const ctx = chart.ctx;
            const activePoint = chart.tooltip._active[0];
            const x = activePoint.element.x;
            const y = activePoint.element.y;
            const topY = chart.scales.y.top;
            const bottomY = chart.scales.y.bottom;
            
            ctx.save();
            ctx.beginPath();
            ctx.setLineDash([5, 5]);
            ctx.moveTo(x, topY);
            ctx.lineTo(x, bottomY);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.stroke();
            ctx.restore();
        }
    }
};

// Registrar plugin
Chart.register(customTooltipPlugin);
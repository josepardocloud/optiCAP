<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php 
                    $database = new Database();
                    $db = $database->getConnection();
                    $query = "SELECT nombre_sistema FROM configuraciones_sistema ORDER BY id DESC LIMIT 1";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $config = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo $config['nombre_sistema'] ?? 'OptiCAP';
                    ?>. Todos los derechos reservados.
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    Versión 1.0 | 
                    <i class="fas fa-user me-1"></i><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Modal para confirmaciones -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">¿Está seguro de que desea realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts comunes -->
<script>
// Función global para confirmaciones
function confirmAction(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const confirmButton = document.getElementById('confirmButton');
    
    // Remover event listeners anteriores
    const newConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
    
    newConfirmButton.addEventListener('click', function() {
        callback();
        confirmModal.hide();
    });
    
    confirmModal.show();
}

// Auto-ocultar alerts después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
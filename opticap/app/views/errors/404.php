<?php
http_response_code(404);
$pageTitle = "Página No Encontrada";
require_once 'app/views/layouts/header.php';
?>

<div class="container text-center py-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <h1 class="display-1 text-muted">404</h1>
            <h2 class="mb-4">Página No Encontrada</h2>
            <p class="lead mb-4">La página que estás buscando no existe o ha sido movida.</p>
            <a href="<?php echo BASE_URL; ?>dashboard" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Volver al Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
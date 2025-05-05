<?php
session_start();
// Configuración básica para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Apoya-TEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Iniciar Sesión</h2>
                        
                        <!-- Mostrar errores si existen -->
                        <?php if (isset($_SESSION['error_login'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_login']; unset($_SESSION['error_login']); ?></div>
                        <?php endif; ?>
                        
                        <form id="loginForm" action="api/auth/login.php" method="POST">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
                            <p><a href="index.html">Volver al inicio</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
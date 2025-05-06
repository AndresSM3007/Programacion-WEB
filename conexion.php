<?php
// Usar las credenciales correctas proporcionadas por el usuario
$host = 'mysql.webcindario.com'; 
$dbname = 'apoya_tec'; // Nombre de la base de datos corregido
$username = 'apoya_tec'; // Usuario corregido
$password = 'AndresSM30$$$'; // Contraseña

// Variable para la conexión
$conn = null;

try {
    // Usar PDO para mantener la consistencia
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar PDO para que lance excepciones en caso de error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "¡Conexión exitosa!"; // Comentar o eliminar para producción
} catch(PDOException $e) {
    // En lugar de usar die(), lanzamos la excepción para que el script que incluye este archivo pueda manejarla.
    // Loguear el error es buena práctica.
    error_log("Error de conexión PDO: " . $e->getMessage()); 
    // Lanzar la excepción para que el script llamador la capture
    throw new PDOException("Error al conectar con la base de datos.", (int)$e->getCode()); 
}

// Si no hubo excepción, $conn estará disponible para el script que incluya este archivo.
?>

<?php
// Usar las credenciales correctas proporcionadas por el usuario
$host = 'mysql.webcindario.com'; 
$dbname = 'apoya_tec'; // Nombre de la base de datos corregido
$username = 'apoya_tec'; // Usuario corregido
$password = 'AndresSM30$$$'; // Contraseña

// Variable para la conexión
$conn = null;

try {
    // Usar PDO para mantener la consistencia
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar PDO para que lance excepciones en caso de error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "¡Conexión exitosa!"; // Comentar o eliminar para producción
} catch(PDOException $e) {
    // En lugar de usar die(), lanzamos la excepción para que el script que incluye este archivo pueda manejarla.
    // Loguear el error es buena práctica.
    error_log("Error de conexión PDO: " . $e->getMessage()); 
    // Lanzar la excepción para que el script llamador la capture
    throw new PDOException("Error al conectar con la base de datos.", (int)$e->getCode()); 
}

// Si no hubo excepción, $conn estará disponible para el script que incluya este archivo.
?>


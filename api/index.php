<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// --- CONTROL DE ACCESO ---
// La sesión de PHP es la misma que creó el login de public/index.php:
// la cookie de sesión vale para todo el sitio, así que api/ también la ve.
session_start();

// Sin email en la sesión no hay nadie autenticado: cortamos aquí, antes de
// procesar ninguna acción (y antes de abrir ninguna conexión a MySQL).
if (empty($_SESSION['usuario_email'])) {
    http_response_code(401); // 401 = no autenticado
    echo json_encode(["status" => "error", "message" => "No autorizado. Inicia sesión."]);
    exit;
}

// Solo permitimos POST por seguridad en el paso de credenciales
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$payload = $input['data'] ?? [];

try {
    switch ($action) {

        // 1. CONECTAR Y EXTRAER ESTRUCTURA DEL SERVIDOR
        case 'saveConnection':
            $host = $payload['host'] ?? 'localhost';
            $port = $payload['port'] ?? 3306;
            $user = $payload['user'] ?? 'root';
            $password = $payload['password'] ?? '';
            $alias = $payload['alias'] ?? 'Servidor';

            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Extraer Bases de Datos
            $stmt = $pdo->query("SHOW DATABASES");
            $databasesRaw = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $databases = [];

            // Filtrar esquemas del sistema y extraer tablas de las BD reales
            foreach ($databasesRaw as $dbName) {
                if (!in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                    $pdo->exec("USE `$dbName`");
                    $tablesStmt = $pdo->query("SHOW TABLES");
                    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
                    $databases[] = ["name" => $dbName, "tables" => $tables];
                }
            }

            echo json_encode([
                "status" => "success",
                "data" => [
                    "id" => time(),
                    "alias" => $alias,
                    "host" => $host,
                    "port" => $port,
                    "user" => $user,
                    "password" => $password, // Se envía temporalmente al cliente en RAM para futuras peticiones (En prod usaríamos tokens)
                    "databases" => $databases
                ]
            ]);
            break;

        // 2. OBTENER METADATOS DE COLUMNAS PARA GENERAR FORMULARIO
        case 'getTableColumns':
            $host = $payload['host'];
            $user = $payload['user'];
            $password = $payload['password'];
            $database = $payload['database'];
            $table = $payload['table'];

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new Exception("Nombre de tabla inválido.");

            $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["status" => "success", "data" => $columns]);
            break;

        // 3. INSERCIÓN DINÁMICA MEDIANTE PDO PREPARED STATEMENTS
        case 'insertRecord':
            $host = $payload['host'];
            $user = $payload['user'];
            $password = $payload['password'];
            $database = $payload['database'];
            $table = $payload['table'];
            $recordData = $payload['recordData'];

            $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $columns = array_keys($recordData);
            $columnList = implode(", ", array_map(function ($c) {
                return "`$c`";
            }, $columns)); // Escapar nombres de columnas
            $placeholderList = implode(", ", array_map(function ($c) {
                return ":$c";
            }, $columns));

            $sql = "INSERT INTO `$table` ($columnList) VALUES ($placeholderList)";
            $stmt = $pdo->prepare($sql);

            foreach ($recordData as $col => $val) {
                $stmt->bindValue(":$col", $val);
            }

            $stmt->execute();
            echo json_encode(["status" => "success", "inserted_id" => $pdo->lastInsertId()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Acción no válida."]);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de Base de Datos: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

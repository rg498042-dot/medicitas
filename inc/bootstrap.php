<?php
// Inicialización común: sesión, base de datos y funciones de apoyo.
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'America/Panama');
session_start();

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirigir(string $url): void { header("Location: $url"); exit; }

function flash(?string $msg = null, string $tipo = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $tipo]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function verificar_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Solicitud inválida. Recargue la página e intente de nuevo.');
    }
}

// Exige sesión iniciada y, opcionalmente, uno de los roles indicados.
function requiere_rol(array $roles = []): void {
    if (empty($_SESSION['u'])) redirigir('login.php');
    if ($roles && !in_array($_SESSION['u']['rol'], $roles, true)) {
        http_response_code(403);
        exit('Acceso denegado: su rol no tiene permiso para esta función.');
    }
    verificar_csrf();
}

function menu_por_rol(string $rol): array {
    $m = [
        'recepcionista' => [['pacientes.php', 'Pacientes'], ['cita_nueva.php', 'Agendar cita'], ['citas.php', 'Agenda']],
        'medico'        => [['citas.php', 'Mi agenda'], ['historial.php', 'Historial de pacientes']],
        'administrador' => [['pacientes.php', 'Pacientes'], ['cita_nueva.php', 'Agendar cita'], ['citas.php', 'Agenda'],
                            ['medicos.php', 'Médicos y horarios'], ['usuarios.php', 'Usuarios'], ['reporte.php', 'Reportes']],
    ];
    return $m[$rol] ?? [];
}

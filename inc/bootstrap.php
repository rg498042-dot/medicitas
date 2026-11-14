<?php
// Inicialización común: sesión, base de datos y funciones de apoyo.
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'America/Panama');
session_start();

// Cierre de sesión por inactividad (minutos configurables en config.php)
$limite = ($config['inactividad_min'] ?? 15) * 60;
if (!empty($_SESSION['u'])) {
    if (time() - ($_SESSION['ultima_actividad'] ?? time()) > $limite) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'] = ['Su sesión se cerró por inactividad. Inicie sesión de nuevo.', 'error'];
        header('Location: login.php');
        exit;
    }
    $_SESSION['ultima_actividad'] = time();
}

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

// Íconos SVG en línea (no necesitan internet ni archivos extra)
function icono(string $nombre, int $t = 28): string {
    static $p = [
        'pacientes' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'agendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/>',
        'agenda'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'medicos'   => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'historial' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'usuarios'  => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'reporte'   => '<path d="M12 20V10M18 20V4M6 20v-4"/>',
        'reloj'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
    ];
    return '<svg class="ico" width="' . $t . '" height="' . $t . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$nombre] ?? '') . '</svg>';
}

// Cada opción: [archivo, texto, ícono, descripción]
function menu_por_rol(string $rol): array {
    $m = [
        'recepcionista' => [
            ['pacientes.php',   'Pacientes',    'pacientes', 'Registrar y consultar pacientes'],
            ['cita_nueva.php',  'Agendar cita', 'agendar',   'Programar una nueva cita'],
            ['citas.php',       'Agenda',       'agenda',    'Ver, reprogramar o cancelar citas'],
        ],
        'medico' => [
            ['citas.php',       'Mi agenda',    'agenda',    'Citas programadas para atender'],
            ['historial.php',   'Historial de pacientes', 'historial', 'Consultar atenciones anteriores'],
        ],
        'administrador' => [
            ['pacientes.php',   'Pacientes',    'pacientes', 'Registrar y consultar pacientes'],
            ['cita_nueva.php',  'Agendar cita', 'agendar',   'Programar una nueva cita'],
            ['citas.php',       'Agenda',       'agenda',    'Ver, reprogramar o cancelar citas'],
            ['medicos.php',     'Médicos y horarios', 'medicos', 'Definir horarios de atención'],
            ['usuarios.php',    'Usuarios',     'usuarios',  'Crear y activar usuarios'],
            ['reporte.php',     'Reportes',     'reporte',   'Resumen de citas por fecha'],
        ],
    ];
    return $m[$rol] ?? [];
}

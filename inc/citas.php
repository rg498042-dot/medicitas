<?php
// Funciones de apoyo para citas (se asume que bootstrap.php ya fue cargado).
const DURACION_CITA = 1800; // 30 minutos

function fecha_valida(string $f): bool {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

function medicos_activos(): array {
    return db()->query("SELECT id, nombre, especialidad FROM usuarios WHERE rol='medico' AND activo=1 ORDER BY nombre")->fetchAll();
}

// Horas libres (HH:MM) de un médico en una fecha, según su horario semanal.
function slots_disponibles(int $medico_id, string $fecha): array {
    if (!fecha_valida($fecha)) return [];
    $dia = (int)date('N', strtotime($fecha));
    $st = db()->prepare('SELECT hora_inicio, hora_fin FROM horarios WHERE medico_id = ? AND dia_semana = ?');
    $st->execute([$medico_id, $dia]);
    $horarios = $st->fetchAll();

    $st = db()->prepare("SELECT TIME_FORMAT(hora, '%H:%i') AS h FROM citas WHERE medico_id = ? AND fecha = ? AND estado = 'programada'");
    $st->execute([$medico_id, $fecha]);
    $ocupadas = array_column($st->fetchAll(), 'h');

    $slots = [];
    foreach ($horarios as $hr) {
        $ini = strtotime("$fecha {$hr['hora_inicio']}");
        $fin = strtotime("$fecha {$hr['hora_fin']}");
        for ($t = $ini; $t + DURACION_CITA <= $fin; $t += DURACION_CITA) {
            if ($t <= time()) continue;               // no se agenda en el pasado
            $h = date('H:i', $t);
            if (!in_array($h, $ocupadas, true)) $slots[] = $h;
        }
    }
    $slots = array_values(array_unique($slots));
    sort($slots);
    return $slots;
}

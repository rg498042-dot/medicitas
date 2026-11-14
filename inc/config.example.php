<?php
// Copia este archivo como config/config.php y ajusta tus datos.
return [
    'host'     => 'localhost',
    'dbname'   => 'medicitas',
    'user'     => 'root',
    'pass'     => '',            // XAMPP: vacío por defecto
    'timezone' => 'America/Panama',
    'inactividad_min' => 15,
    // Datos que se muestran en el pie de página (cámbialos por los tuyos)
    'institucion'  => 'ISAE Universidad',
    'curso'        => 'Ingeniería de Software',
    'integrantes'  => 'Integrante 1 · Integrante 2',
    'docente'      => 'Nombre del docente',   // minutos sin actividad antes de cerrar la sesión
];

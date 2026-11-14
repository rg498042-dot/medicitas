# MediCitas

Sistema sencillo de gestión de citas e historial de pacientes para una clínica, desarrollado en **PHP 8 + MySQL** (sin frameworks).
Trabajo Grupal II de Ingeniería de Software · ISAE Universidad.

## Funciones (casos de uso)

| Código | Caso de uso | Rol | Archivo |
|---|---|---|---|
| CU-01 | Iniciar sesión (bloqueo tras 3 intentos fallidos) | Todos | `public/login.php` |
| CU-02 | Registrar paciente | Recepcionista / Admin | `public/paciente_form.php` |
| CU-03 | Consultar paciente | Recepcionista / Admin | `public/pacientes.php` |
| CU-04 | Agendar cita (sin duplicados) | Recepcionista / Admin | `public/cita_nueva.php` |
| CU-05 | Reprogramar o cancelar cita | Recepcionista / Admin | `public/cita_reprogramar.php`, `public/citas.php` |
| CU-06 | Consultar agenda | Recepcionista / Admin | `public/citas.php` |
| CU-07 | Ver mi agenda | Médico | `public/citas.php` |
| CU-08 | Registrar atención | Médico | `public/atencion.php` |
| CU-09 | Consultar historial del paciente | Médico | `public/historial.php` |
| CU-10 | Gestionar usuarios | Administrador | `public/usuarios.php` |
| CU-11 | Gestionar médicos y horarios | Administrador | `public/medicos.php` |
| CU-12 | Generar reporte de citas | Administrador | `public/reporte.php` |

## Requisitos

- PHP 8.0 o superior (extensión `pdo_mysql`)
- MySQL 5.7+ o MariaDB 10.2+
- XAMPP o Laragon (incluyen todo lo anterior)

## Instalación

1. Clona o descarga el proyecto.
2. Crea la base de datos importando `database/schema.sql` (phpMyAdmin → Importar, o `mysql -u root < database/schema.sql`).
3. Copia `config/config.example.php` como `config/config.php` y ajusta usuario/contraseña de MySQL.
4. Inicia el servidor:
   ```bash
   php -S localhost:8000 -t public
   ```
   (o coloca la carpeta en `htdocs` y abre `http://localhost/medicitas/public/`).
5. Ingresa con **admin / Admin123** y cambia la contraseña creando tus propios usuarios.

## Primeros pasos

1. Como administrador, crea usuarios: al menos un médico y una recepcionista (`Usuarios`).
2. Define los horarios de cada médico (`Médicos y horarios`). Las citas son de 30 minutos.
3. Ingresa como recepcionista, registra pacientes y agenda citas.
4. Ingresa como médico para atender las citas y consultar historiales.

## Reglas importantes

- Un médico no puede tener dos citas programadas a la misma fecha y hora (validación en código y en la base de datos).
- Las contraseñas se guardan con hash (`password_hash`) y los formularios usan token CSRF.
- La sesión se cierra tras 15 minutos de inactividad (`inactividad_min` en `config/config.php`).
- Las imágenes e íconos son SVG propios (`public/img/` y función `icono()`), sin dependencias externas.
- `config/config.php` está en `.gitignore`: no se sube a GitHub.

## Estructura

```
config/     configuración (config.example.php)
database/   schema.sql
inc/        funciones comunes y plantillas (header/footer)
public/     páginas del sistema, CSS e imágenes (img/)
```

## Subir a GitHub

```bash
git init
git add .
git commit -m "MediCitas: versión inicial"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/medicitas.git
git push -u origin main
```

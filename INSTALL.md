# Instalación

## Requisitos

- PHP 8.1 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Extensiones PHP: `PDO`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `gd` o `imagick`
- HTTPS recomendado y necesario para geolocalización en navegador

## Estructura

La aplicación incluye la carpeta pública en:

- `/public`

El instalador está disponible en:

- `/public/install/`

## Instalación paso a paso

1. Sube el contenido del proyecto a tu servidor.
2. Verifica que el dominio apunta correctamente a la instalación.
3. Accede desde el navegador a:
   - `https://tudominio.com/public/install/`
4. Sigue el asistente de instalación.
5. Completa los datos de base de datos, empresa, email, geolocalización y cuenta de administrador.
6. Finaliza la instalación y accede con la cuenta creada.

## Configuración recomendada

- Activar HTTPS
- Configurar SMTP para notificaciones y recuperación de contraseña
- Revisar radio máximo de fichaje y precisión GPS
- Revisar horario laboral y días de vacaciones por defecto

## Notas

- El instalador genera la configuración inicial automáticamente.
- Tras la instalación, revisa la configuración desde el panel de administración.
- Si se va a publicar en producción, se recomienda probar fichajes, incidencias, vacaciones e informes antes de abrir el acceso a usuarios finales.

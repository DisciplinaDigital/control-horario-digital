# Control Horario Digital v1.0

Sistema web open source de registro de jornada laboral, orientado a empresas y asesorías que necesitan una solución autoalojable, trazable y fácil de desplegar.

Desarrollado por **Javier Ortiz**  
[disciplinadigital.es](https://disciplinadigital.es)

## Descripción

Control Horario Digital permite registrar entrada y salida desde web móvil, tablet o escritorio, gestionar incidencias, vacaciones y festivos, y disponer de exportaciones útiles para revisión interna e inspección.

La aplicación está pensada para instalación en servidor propio, sin cuotas de suscripción y sin dependencia de servicios externos para su funcionamiento principal.

## Funcionalidades principales

- Registro diario de jornada con entrada y salida
- Panel de administración y área de empleado
- Gestión de usuarios, departamentos y roles
- Gestión de incidencias con correcciones administrativas trazables
- Gestión de vacaciones y festivos
- Geolocalización configurable con radio global y override por empleado
- Verificación de integridad de fichajes
- Exportación CSV y PDF
- Personalización de empresa, logo, colores y horarios

## Requisitos técnicos

- PHP 8.1 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Extensiones PHP: `PDO`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `gd` o `imagick`
- HTTPS recomendado y necesario para geolocalización en navegador

## Instalación rápida

1. Sube los archivos al servidor.
2. Accede a `/public/install/`.
3. Sigue el asistente de instalación.
4. Finaliza la configuración inicial y entra con la cuenta de administrador.

## Documentación

En la raíz del proyecto se incluyen:

- `Documentacion.pdf`
- `README.md`
- `LICENSE.txt`

## Licencia

Este proyecto se distribuye bajo licencia **GNU GPL v3**.  
Consulta el archivo `LICENSE.txt` para el texto completo.

## Aviso

La aplicación está orientada al registro diario de jornada y a la trazabilidad operativa de los fichajes. Cada empresa debe valorar su adecuación legal, laboral y de protección de datos según su caso concreto.

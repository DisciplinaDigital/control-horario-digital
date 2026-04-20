# Control Horario Digital v1.0

Sistema web open source de registro de jornada laboral, orientado a empresas y asesorias que necesitan una solucion autoalojable, trazable y facil de desplegar.

Desarrollado por **Javier Ortiz**  
[disciplinadigital.es](https://disciplinadigital.es)

## Descripcion

Control Horario Digital permite registrar entrada y salida desde web movil, tablet o escritorio, gestionar incidencias, vacaciones y festivos, y disponer de exportaciones utiles para revision interna e inspeccion.

La aplicacion esta pensada para instalacion en servidor propio, sin cuotas de suscripcion y sin dependencia de servicios externos para su funcionamiento principal.

## Funcionalidades principales

- Registro diario de jornada con entrada y salida
- Panel de administracion y area de empleado
- Gestion de usuarios, departamentos y roles
- Gestion de incidencias con correcciones administrativas trazables
- Gestion de vacaciones y festivos
- Geolocalizacion configurable con radio global y override por empleado
- Verificacion de integridad de fichajes
- Exportacion CSV y PDF
- Personalizacion de empresa, logo, colores y horarios

## Requisitos tecnicos

- PHP 8.1 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Extensiones PHP: `PDO`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `gd` o `imagick`
- HTTPS recomendado y necesario para geolocalizacion en navegador

## Instalacion rapida

1. Sube los archivos al servidor.
2. Accede a `/public/install/`.
3. Sigue el asistente de instalacion.
4. Finaliza la configuracion inicial y entra con la cuenta de administrador.

## Documentacion

En la raiz del proyecto se incluyen:

- `Documentacion.pdf`
- `README.md`
- `LICENSE.txt`
- `TRADEMARKS.md`

## Licencia

Este proyecto se distribuye bajo licencia **GNU AGPL v3 o posterior**.

Puedes usarlo, estudiarlo, modificarlo y redistribuirlo conforme a los terminos de `LICENSE.txt`. Si ejecutas una version modificada accesible por red, la licencia exige ofrecer el codigo fuente correspondiente de esa version modificada a sus usuarios.

## Marcas y signos distintivos

La licencia del codigo no concede derechos sobre nombres comerciales, marcas, logotipos, identidad visual ni otros signos distintivos del proyecto o de Disciplina Digital.

Consulta `TRADEMARKS.md` para el detalle.

## Aviso

La aplicacion esta orientada al registro diario de jornada y a la trazabilidad operativa de los fichajes. Cada empresa debe valorar su adecuacion legal, laboral y de proteccion de datos segun su caso concreto.

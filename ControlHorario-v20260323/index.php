<?php
/**
 * Control Horario Digital
 * Copyright (C) 2026 Javier Ortiz - disciplinadigital.es
 * * Este programa es software libre: usted puede redistribuirlo y/o modificarlo 
 * bajo los términos de la Licencia Pública General GNU publicada por 
 * la Free Software Foundation, ya sea la versión 3 de la Licencia, o 
 * (a su elección) cualquier versión posterior.
 *
 * Este programa se distribuye con la esperanza de que sea útil, 
 * pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de 
 * MERCANTILIDAD o APTITUD PARA UN PROPÓSITO PARTICULAR. 
 * Consulte la Licencia Pública General GNU para más detalles.
 *
 * Redirección raíz → /public/
 *
 * El cliente instala la app en la raíz del dominio.
 * APP_URL = https://tudominio.com/public  (donde vive la app)
 *
 * Cualquier petición a tudominio.com/* se redirige a tudominio.com/public/*
 * de forma transparente (301 permanente, SEO-friendly).
 *
 * Ejemplos:
 *   tudominio.com/          → tudominio.com/public/
 *   tudominio.com/login     → tudominio.com/public/login
 *   tudominio.com/admin     → tudominio.com/public/admin
 *
 * Las peticiones que ya llevan /public/ en la URL son servidas directamente
 * por nginx/Apache sin pasar por aquí (archivos físicos existentes).
 */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri   = $_SERVER['REQUEST_URI'] ?? '/';

// Construir destino: /public + URI original (conserva path y query string)
$target = '/public' . ($uri === '/' ? '/' : $uri);

header('Location: ' . $proto . '://' . $host . $target, true, 301);
exit;

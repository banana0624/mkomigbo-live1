<?php
declare(strict_types=1);

/**
 * /public/subjects/download.php  (ALIAS SHIM)
 * Back-compat endpoint. Forwards to /public/download.php.
 *
 * Accepts legacy query:
 *   ?subject=&page=&file=&scope=
 *   ?s=&p=&f=&in=
 *
 * Forces:
 *   dl=1 / in=0 semantics via central /download.php
 *   scope=private default unless explicitly provided.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* Normalize params to central format */
if (!isset($_GET['subject']) && isset($_GET['s'])) $_GET['subject'] = $_GET['s'];
if (!isset($_GET['page'])    && isset($_GET['p'])) $_GET['page']    = $_GET['p'];
if (!isset($_GET['file'])    && isset($_GET['f'])) $_GET['file']    = $_GET['f'];

/* Default scope */
if (!isset($_GET['scope']) || !is_scalar($_GET['scope']) || trim((string)$_GET['scope']) === '') {
  $_GET['scope'] = 'private';
}

/* Hand off to central download wrapper */
require __DIR__ . '/../download.php';

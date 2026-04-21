<?php

declare(strict_types=1);

/*
|------------------------------------------------------
| CORE BOOTSTRAP (SINGLE SOURCE OF TRUTH)
|------------------------------------------------------
*/

define('APP_ROOT', dirname(__DIR__, 2));

/* CORE FUNCTIONS (ORDER MATTERS) */
require_once APP_ROOT . '/private/functions/db.php';
require_once APP_ROOT . '/private/functions/bootstrap_init.php';

/* SAFE INIT */
mk_initialize();
<?php

spl_autoload_register(function ($class) {

    // base namespace
    $prefix = 'App\\';

    // base directory
    $baseDir = __DIR__ . '/';

    // does class use prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // relative class
    $relativeClass = substr($class, $len);

    // replace namespace with directory
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
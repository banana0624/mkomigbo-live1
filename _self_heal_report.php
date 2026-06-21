<?php
require_once __DIR__ . '/_init.php';

header('Content-Type: application/json');

echo json_encode(SelfHealingKernel::getReport(), JSON_PRETTY_PRINT);
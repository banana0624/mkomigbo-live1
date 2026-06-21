<?php
require_once __DIR__ . '/_init.php';

header('Content-Type: application/json');

echo ExecutionGraph::exportJson();
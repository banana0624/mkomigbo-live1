<?php
declare(strict_types=1);

/**
 * Boot Pipeline Compiler (BUILD-TIME ONLY)
 *
 * Responsibility:
 * - Scan codebase
 * - Build dependency graph
 * - Normalize execution order
 * - Generate bootmap.php
 *
 * This file MUST NOT be included in runtime.
 */

require_once __DIR__ . '/../core/DependencyCompiler.php';
require_once __DIR__ . '/../core/BootOrderNormalizer.php';

$root = dirname(__DIR__);

echo "=== BOOT PIPELINE COMPILER START ===\n";

$compiler = new DependencyCompiler($root);
$compiler->compile();

$graph = $compiler->getGraph();

echo "[1/3] Dependency graph compiled\n";

$normalizer = new BootOrderNormalizer($graph);

$bootOrder = $normalizer->normalize();

echo "[2/3] Boot order normalized\n";

$bootMapPath = $root . '/cache/bootmap.php';

$normalizer->exportBootMap($bootMapPath);

echo "[3/3] Bootmap generated at: {$bootMapPath}\n";

echo "=== BOOT PIPELINE COMPLETE ===\n";

echo "Total boot entries: " . count($bootOrder) . "\n";
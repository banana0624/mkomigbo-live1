<?php
declare(strict_types=1);

/**
 * DependencyCompiler
 *
 * Builds a deterministic dependency graph of PHP includes/requires
 * across the codebase and produces:
 *
 * - adjacency list graph
 * - topological execution order
 * - cycle detection report
 * - optional JSON export
 *
 * Designed to complement ExecutionGraph (runtime tracing).
 */
 
 public function getGraph(): array
{
    return $this->graph;
}

final class DependencyCompiler
{
    private string $root;

    /** @var array<string, array<int, string>> */
    private array $graph = [];

    /** @var array<int, array{file:string, line:int, target:string}> */
    private array $edges = [];

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    /**
     * Scan full codebase
     */
    public function compile(): void
    {
        $files = $this->getPhpFiles($this->root);

        foreach ($files as $file) {
            $this->parseFile($file);
        }
    }

    /**
     * Parse a single file and extract require/include statements
     */
    private function parseFile(string $file): void
    {
        $lines = @file($file);
        if (!$lines) {
            return;
        }

        foreach ($lines as $i => $line) {
            if (!str_contains($line, 'require') && !str_contains($line, 'include')) {
                continue;
            }

            $target = $this->extractPath($line);
            if ($target === null) {
                continue;
            }

            $resolved = $this->resolvePath($file, $target);

            if ($resolved === null) {
                continue;
            }

            $this->addEdge($file, $i + 1, $resolved);
        }
    }

    /**
     * Extract string path from require/include line
     */
    private function extractPath(string $line): ?string
    {
        if (!preg_match("/['\"](.+?)['\"]/", $line, $m)) {
            return null;
        }

        return $m[1];
    }

    /**
     * Resolve relative include paths
     */
    private function resolvePath(string $fromFile, string $target): ?string
    {
        if (str_starts_with($target, '/')) {
            return file_exists($target) ? realpath($target) : null;
        }

        $base = dirname($fromFile);
        $path = realpath($base . '/' . $target);

        return $path ?: null;
    }

    /**
     * Add graph edge
     */
    private function addEdge(string $from, int $line, string $to): void
    {
        $this->graph[$from][] = $to;

        $this->edges[] = [
            'file' => $from,
            'line' => $line,
            'target' => $to
        ];
    }

    /**
     * Build topological order (execution-safe order)
     */
    public function topoSort(): array
    {
        $visited = [];
        $stack = [];

        foreach (array_keys($this->graph) as $node) {
            $this->visit($node, $visited, $stack);
        }

        return array_reverse($stack);
    }

    private function visit(string $node, array &$visited, array &$stack): void
    {
        if (isset($visited[$node])) {
            return;
        }

        $visited[$node] = true;

        foreach ($this->graph[$node] ?? [] as $neighbor) {
            $this->visit($neighbor, $visited, $stack);
        }

        $stack[] = $node;
    }

    /**
     * Detect circular dependencies
     */
    public function detectCycles(): array
    {
        $visited = [];
        $stack = [];
        $cycles = [];

        foreach (array_keys($this->graph) as $node) {
            $this->dfsCycle($node, $visited, $stack, $cycles);
        }

        return $cycles;
    }

    private function dfsCycle(string $node, array &$visited, array &$stack, array &$cycles): void
    {
        if (isset($stack[$node])) {
            $cycles[] = array_keys($stack);
            return;
        }

        if (isset($visited[$node])) {
            return;
        }

        $visited[$node] = true;
        $stack[$node] = true;

        foreach ($this->graph[$node] ?? [] as $neighbor) {
            $this->dfsCycle($neighbor, $visited, $stack, $cycles);
        }

        unset($stack[$node]);
    }

    /**
     * Export graph as JSON (for debugging / visualization)
     */
    public function exportJson(string $path): void
    {
        $data = [
            'graph' => $this->graph,
            'edges' => $this->edges,
            'cycles' => $this->detectCycles()
        ];

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get all PHP files in system
     */
    private function getPhpFiles(string $dir): array
    {
        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        $files = [];

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (str_ends_with($file->getPathname(), '.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
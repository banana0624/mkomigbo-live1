<?php
declare(strict_types=1);

/**
 * BootOrderNormalizer
 *
 * Converts a dependency graph into a deterministic
 * linear boot sequence.
 */

final class BootOrderNormalizer
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $graph;

    /**
     * @var array<int, string>
     */
    private array $order = [];

    /**
     * @var array<string, bool>
     */
    private array $visited = [];

    /**
     * @var array<string, bool>
     */
    private array $stack = [];

    public function __construct(array $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Entry point
     */
    public function normalize(): array
    {
        foreach (array_keys($this->graph) as $node) {
            $this->dfs($node);
        }

        return array_values(array_unique(array_reverse($this->order)));
    }

    /**
     * Depth-first traversal for topological ordering
     */
    private function dfs(string $node): void
    {
        if (isset($this->visited[$node])) {
            return;
        }

        if (isset($this->stack[$node])) {
            throw new RuntimeException("Circular dependency detected at: {$node}");
        }

        $this->stack[$node] = true;

        foreach ($this->graph[$node] ?? [] as $dependency) {
            $this->dfs($dependency);
        }

        unset($this->stack[$node]);

        $this->visited[$node] = true;
        $this->order[] = $node;
    }

    /**
     * Export normalized boot order as PHP file
     */
    public function exportBootMap(string $path): void
    {
        $order = $this->normalize();

        $content = "<?php\n";
        $content .= "// AUTO-GENERATED BOOT MAP - DO NOT EDIT\n";
        $content .= "return " . var_export($order, true) . ";\n";

        file_put_contents($path, $content);
    }
}
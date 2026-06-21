<?php
declare(strict_types=1);

final class CompiledKernelLoader
{
    /**
     * @var string
     */
    private string $bootMapFile;

    /**
     * @var array<int, string>
     */
    private array $bootOrder = [];

    public function __construct(string $bootMapFile)
    {
        $this->bootMapFile = $bootMapFile;
    }

    /**
     * Load compiled boot map
     */
    public function load(): void
    {
        if (!is_file($this->bootMapFile)) {

            throw new RuntimeException(
                'Boot map missing: ' .
                $this->bootMapFile
            );
        }

        $data = require $this->bootMapFile;

        if (!is_array($data)) {

            throw new RuntimeException(
                'Invalid boot map format'
            );
        }

        $this->bootOrder = array_values(
            array_filter(
                $data,
                static fn ($item): bool => is_string($item)
            )
        );
    }

    /**
     * Execute compiled boot order
     */
    public function execute(): void
    {
        foreach ($this->bootOrder as $file) {

            if (!is_file($file)) {

                error_log(
                    '[BOOT LOADER] Missing file: ' .
                    $file
                );

                continue;
            }

            if (class_exists('BootstrapFirewall')) {

                BootstrapFirewall::check(
                    $file,
                    '__compiled_loader__'
                );
            }

            if (class_exists('ExecutionGraph')) {

                ExecutionGraph::record(
                    $file,
                    'compiled_boot'
                );
            }

            require_once $file;
        }
    }

    /**
     * Return resolved boot order
     *
     * @return array<int, string>
     */
    public function getBootOrder(): array
    {
        return $this->bootOrder;
    }
}
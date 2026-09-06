<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class LayerDependencyTest extends TestCase
{
    public function test_services_do_not_depend_on_http_or_livewire_layers(): void
    {
        $violations = $this->findViolations(
            app_path('Services'),
            [
                'App\\Http\\' => 'HTTP layer',
                'App\\Livewire\\' => 'Livewire layer',
                'Illuminate\\Http\\' => 'HTTP framework layer',
            ],
        );

        $this->assertSame([], $violations, $this->formatViolations('Services', $violations));
    }

    public function test_livewire_does_not_depend_on_controllers(): void
    {
        $violations = $this->findViolations(
            app_path('Livewire'),
            ['App\\Http\\Controllers\\' => 'Controller layer'],
        );

        $this->assertSame([], $violations, $this->formatViolations('Livewire', $violations));
    }

    public function test_controllers_do_not_depend_on_livewire(): void
    {
        $violations = $this->findViolations(
            app_path('Http/Controllers'),
            ['App\\Livewire\\' => 'Livewire layer'],
        );

        $this->assertSame([], $violations, $this->formatViolations('Controllers', $violations));
    }

    /**
     * @param array<string, string> $forbidden
     * @return array<int, string>
     */
    private function findViolations(string $directory, array $forbidden): array
    {
        $violations = [];

        if (! is_dir($directory)) {
            return $violations;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            foreach ($forbidden as $namespace => $layer) {
                if (str_contains($contents, $namespace)) {
                    $violations[] = sprintf('%s depends on %s', $file->getPathname(), $layer);
                }
            }
        }

        sort($violations);

        return $violations;
    }

    /**
     * @param array<int, string> $violations
     */
    private function formatViolations(string $layer, array $violations): string
    {
        return $layer . " layer dependency violations:\n" . implode("\n", $violations);
    }
}

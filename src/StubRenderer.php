<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Webkernel\XWebdev\Exceptions\XWebdevException;

final readonly class StubRenderer
{
    public function __construct(private string $stubsPath)
    {
    }

    /**
     * @param array<string, string> $vars
     */
    public function render(string $relativeStub, array $vars): string
    {
        $path = $this->stubsPath . DIRECTORY_SEPARATOR . $relativeStub;

        if (!is_file($path)) {
            throw new XWebdevException("Stub not found: {$relativeStub}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new XWebdevException("Failed to read stub: {$relativeStub}");
        }

        return str_replace(
            array_map(static fn (string $k): string => '{{' . $k . '}}', array_keys($vars)),
            array_values($vars),
            $content
        );
    }
}

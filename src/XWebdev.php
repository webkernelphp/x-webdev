<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Webkernel\XWebdev\Config\ConfigLoader;
use Webkernel\XWebdev\Exceptions\XWebdevException;

/**
 * Composition root for the unified x-webdev CLI.
 */
final readonly class XWebdev
{
    public function __construct(private ConfigLoader $config)
    {
    }

    public function getConfig(): ConfigLoader
    {
        return $this->config;
    }

    public function monorepoRoot(): string
    {
        $root = $this->config->getString('monorepo_root');

        $resolved = realpath($root);

        if ($resolved === false) {
            throw new XWebdevException("Monorepo root '{$root}' not found.");
        }

        return $resolved;
    }

    public function packagesPath(): string
    {
        $dir = $this->config->getString('packages_dir', 'packages');

        if (str_starts_with($dir, '/')) {
            return $dir;
        }

        return $this->monorepoRoot() . DIRECTORY_SEPARATOR . $dir;
    }

    /** @return list<string> */
    public function devPackages(): array
    {
        return $this->config->getStringArray('dev_packages');
    }
}

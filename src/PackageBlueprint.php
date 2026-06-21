<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Webkernel\StdLifecycle\Installer\SLCPackageType;
use Webkernel\XWebdev\Exceptions\XWebdevException;

/**
 * Resolved package scaffold identity.
 */
final readonly class PackageBlueprint
{
    public function __construct(
        public string $vendor,
        public string $slug,
        public string $directory,
        public string $composerName,
        public SLCPackageType $type,
        public string $namespace,
        public ?string $parentModule = null,
    ) {
    }

    public static function make(
        SLCPackageType $type,
        string $vendor,
        string $slug,
        string $namespace,
        ?string $parentModule = null,
    ): self {
        self::assertVendor($vendor);
        $normalized = self::normalizeSlug($slug);

        if ($normalized === '') {
            throw new XWebdevException('Package name cannot be empty.');
        }

        $directory = $type->requiresParentModule()
            ? self::featureDirectory($vendor, $normalized, $parentModule)
            : $normalized;

        return new self(
            vendor: $vendor,
            slug: $normalized,
            directory: $directory,
            composerName: "{$vendor}/{$normalized}",
            type: $type,
            namespace: rtrim($namespace, '\\') . '\\',
            parentModule: $parentModule,
        );
    }

    public static function defaultNamespace(string $root, string $slug): string
    {
        $parts = array_map(
            static fn (string $part): string => str_replace(' ', '', ucwords(str_replace('-', ' ', $part))),
            explode('-', self::normalizeSlug($slug))
        );

        return rtrim($root, '\\') . '\\' . implode('\\', $parts) . '\\';
    }

    public function providerShortName(): string
    {
        $parts = explode('\\', rtrim($this->namespace, '\\'));
        $base = $parts[count($parts) - 1] ?? $this->slug;

        return str_replace(' ', '', ucwords(str_replace('-', ' ', $base))) . 'ServiceProvider';
    }

    public function providerFqn(): string
    {
        return rtrim($this->namespace, '\\') . '\\Providers\\' . $this->providerShortName();
    }

    public function loadFileName(): string
    {
        return 'load.' . $this->slug . '.functions.php';
    }

    public function loadPackageComment(): string
    {
        return $this->composerName . '/' . $this->loadFileName();
    }

    public function viewNamespace(): string
    {
        return $this->slug;
    }

    public function configKey(): string
    {
        return str_replace('-', '_', $this->slug);
    }

    public function usesLaravel(): bool
    {
        return match ($this->type) {
            SLCPackageType::DevTool,
            SLCPackageType::Stdlib,
            SLCPackageType::Ffi,
            SLCPackageType::Assets => false,
            default => true,
        };
    }

    private static function featureDirectory(string $vendor, string $slug, ?string $parentModule): string
    {
        if ($parentModule === null || $parentModule === '') {
            throw new XWebdevException(
                "Package type requires --parent=vendor/module."
            );
        }

        if (!str_contains($parentModule, '/')) {
            throw new XWebdevException("Invalid parent module '{$parentModule}'. Expected vendor/package.");
        }

        [$parentVendor, $parentName] = explode('/', $parentModule, 2);
        $parentDir = self::guessParentDirectory($parentName);

        return "{$parentDir}/features/{$vendor}-{$slug}";
    }

    private static function guessParentDirectory(string $parentName): string
    {
        $packagesDir = project_root() . DIRECTORY_SEPARATOR . 'packages';
        $candidates = [
            self::normalizeSlug($parentName),
            'module-' . self::normalizeSlug($parentName),
            'platform-' . self::normalizeSlug($parentName),
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($packagesDir . DIRECTORY_SEPARATOR . $candidate)) {
                return $candidate;
            }
        }

        return 'module-' . self::normalizeSlug($parentName);
    }

    private static function assertVendor(string $vendor): void
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $vendor)) {
            throw new XWebdevException(
                "Invalid vendor '{$vendor}'. Use lowercase letters, numbers, and hyphens."
            );
        }
    }

    private static function normalizeSlug(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $name) ?? '', '-'));
    }
}

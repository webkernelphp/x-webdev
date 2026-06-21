<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Webkernel\StdLifecycle\Installer\SLCPackageType;
use Webkernel\XWebdev\Config\ConfigLoader;

final readonly class PackageScaffold
{
    private PackageWriter $writer;

    public function __construct(ConfigLoader $config)
    {
        $stubsPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'package';
        $this->writer = new PackageWriter($config, new StubRenderer($stubsPath));
    }

    /**
     * @return array{directory: string, composer_name: string, namespace: string}
     */
    public function create(
        SLCPackageType $type,
        string $vendor,
        string $slug,
        string $namespace,
        ?string $parentModule = null,
        bool $force = false,
    ): array {
        $blueprint = PackageBlueprint::make($type, $vendor, $slug, $namespace, $parentModule);
        $target = webkernel_package($blueprint->directory, null, false, true);

        $this->writer->write($blueprint, $target, $force);

        return [
            'directory' => $blueprint->directory,
            'composer_name' => $blueprint->composerName,
            'namespace' => $blueprint->namespace,
        ];
    }
}

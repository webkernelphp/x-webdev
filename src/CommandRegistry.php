<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Symfony\Component\Console\Application;
use Webkernel\XWebdev\Exceptions\XWebdevException;

/**
 * Registers commands from configured x-* dev packages.
 */
final readonly class CommandRegistry
{
    public function __construct(
        private XWebdev $webdev,
        private DevCommandFactory $factory,
    ) {
    }

    public function register(Application $app): void
    {
        foreach ($this->webdev->devPackages() as $package) {
            $this->registerPackage($app, $package);
        }
    }

    private function registerPackage(Application $app, string $package): void
    {
        $root = $this->resolvePackageRoot($package);
        $commandsDir = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Commands';

        if (!is_dir($commandsDir)) {
            return;
        }

        $prefix = $this->commandPrefix($package);
        $namespace = $this->commandNamespace($package);

        foreach (glob($commandsDir . DIRECTORY_SEPARATOR . '*Command.php') ?: [] as $file) {
            $class = $namespace . basename($file, '.php');
            if (!class_exists($class)) {
                continue;
            }
            if ((new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $command = $this->factory->create($class);
            $name = $command->getName();

            if ($name !== null && !str_contains($name, ':')) {
                $command->setName("{$prefix}:{$name}");
            }

            $app->add($command);
        }
    }

    private function resolvePackageRoot(string $package): string
    {
        $packagesPath = $this->webdev->packagesPath() . DIRECTORY_SEPARATOR . $package;

        if (is_dir($packagesPath)) {
            return realpath($packagesPath) ?: $packagesPath;
        }

        $vendorPath = vendor_dir("webkernel/{$package}");

        if (is_dir($vendorPath)) {
            return realpath($vendorPath) ?: $vendorPath;
        }

        throw new XWebdevException("Dev package '{$package}' not found in packages/ or vendor.");
    }

    private function commandPrefix(string $package): string
    {
        return str_starts_with($package, 'x-')
            ? substr($package, 2)
            : $package;
    }

    private function commandNamespace(string $package): string
    {
        $studly = str_replace(' ', '', ucwords(str_replace('-', ' ', $package)));

        return "Webkernel\\{$studly}\\Commands\\";
    }
}

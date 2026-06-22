<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Webkernel\XWebdev\Config\ConfigLoader;
use Webkernel\XWebdev\Exceptions\XWebdevException;

final readonly class PackageWriter
{
    public function __construct(
        private ConfigLoader $config,
        private StubRenderer $stubs,
    ) {
    }

    public function write(PackageBlueprint $blueprint, string $target, bool $force): void
    {
        $composerFile = $target . DIRECTORY_SEPARATOR . 'composer.json';

        if (is_dir($target) && file_exists($composerFile) && !$force) {
            throw new XWebdevException(
                "Package directory already exists: {$target}. Use --force to overwrite scaffold files."
            );
        }

        $vars = $this->templateVars($blueprint);

        $this->ensureDir($target);
        $this->writeFile($target, 'composer.json', $this->buildComposerJson($blueprint), $force);
        $this->writeFile($target, 'package.json', $this->stubs->render('package.json.stub', $vars), $force);
        $this->writeFile($target, 'README.md', $this->stubs->render('README.md.stub', $vars), $force);
        $this->writeFile($target, '.gitignore', $this->stubs->render('gitignore.stub', $vars), $force);
        $this->writeFile($target, $blueprint->loadFileName(), $this->stubs->render('load.functions.php.stub', $vars), $force);

        $this->ensureDir($target . '/src/Providers');
        $this->ensureDir($target . '/config');
        $this->ensureDir($target . '/routes');
        $this->ensureDir($target . '/resources/views/components');
        $this->ensureDir($target . '/resources/css');
        $this->ensureDir($target . '/resources/js');
        $this->ensureDir($target . '/database/migrations');
        $this->ensureDir($target . '/database/factories');
        $this->ensureDir($target . '/database/seeders');
        $this->ensureDir($target . '/tests');

        $this->writeFile(
            $target,
            'src/Providers/' . $blueprint->providerShortName() . '.php',
            $this->stubs->render(
                $blueprint->usesLaravel() ? 'ServiceProvider.php.stub' : 'ServiceProvider.minimal.php.stub',
                $vars
            ),
            $force
        );

        $this->writeFile($target, 'config/' . $blueprint->slug . '.php', $this->stubs->render('config.php.stub', $vars), $force);
        $this->writeFile($target, 'routes/web.php', $this->stubs->render('routes.web.php.stub', $vars), $force);
        $this->touchKeep($target . '/resources/views/components/.gitkeep');
        $this->touchKeep($target . '/resources/css/.gitkeep');
        $this->touchKeep($target . '/resources/js/.gitkeep');
        $this->touchKeep($target . '/database/migrations/.gitkeep');
        $this->touchKeep($target . '/database/factories/.gitkeep');
        $this->touchKeep($target . '/database/seeders/.gitkeep');
        $this->touchKeep($target . '/tests/.gitkeep');
    }

    private function buildComposerJson(PackageBlueprint $blueprint): string
    {
        $githubOrg = $this->config->getString('github_org', 'webkernelphp');
        $namespace = rtrim($blueprint->namespace, '\\') . '\\';

        $require = [
            'php' => $this->config->getString('default_php_constraint', '>=8.4'),
        ];
        if ($blueprint->usesLaravel()) {
            $require['laravel/framework'] = '*';
            $require['webkernel/standard-lifecycle'] = '>=0.1.0';
        }

        $webkernelExtra = [
            'package_repo' => "git@github.com:{$githubOrg}/{$blueprint->slug}.git",
            'prefix' => $blueprint->slug,
        ];

        if ($blueprint->parentModule !== null && $blueprint->parentModule !== '') {
            $webkernelExtra['module'] = $blueprint->parentModule;
        }

        $payload = [
            '$schema' => 'https://getcomposer.org/schema.json',
            'name' => $blueprint->composerName,
            'description' => $blueprint->type->description(),
            'type' => $blueprint->type->value,
            'version' => '0.1.0',
            'license' => $this->config->getString('default_license', 'EPL-2.0'),
            'require' => $require,
            'autoload' => [
                'files' => [$blueprint->loadFileName()],
                'psr-4' => [
                    $namespace => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $namespace . 'Tests\\' => 'tests/',
                ],
            ],
            'minimum-stability' => 'stable',
            'extra' => [
                'laravel' => [
                    'providers' => [$blueprint->providerFqn()],
                ],
                'webkernel' => $webkernelExtra,
            ],
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($encoded)) {
            throw new XWebdevException('Failed to encode composer.json.');
        }

        return $encoded . "\n";
    }

    /** @return array<string, string> */
    private function templateVars(PackageBlueprint $blueprint): array
    {
        $githubOrg = $this->config->getString('github_org', 'webkernelphp');

        return [
            'vendor' => $blueprint->vendor,
            'slug' => $blueprint->slug,
            'directory' => $blueprint->directory,
            'composer_name' => $blueprint->composerName,
            'type' => $blueprint->type->value,
            'description' => $blueprint->type->description(),
            'license' => $this->config->getString('default_license', 'EPL-2.0'),
            'github_org' => $githubOrg,
            'package_repo' => "git@github.com:{$githubOrg}/{$blueprint->slug}.git",
            'namespace' => $blueprint->namespace,
            'provider_class' => $blueprint->providerShortName(),
            'provider_fqn' => $blueprint->providerFqn(),
            'load_filename' => $blueprint->loadFileName(),
            'load_package_comment' => $blueprint->loadPackageComment(),
            'view_namespace' => $blueprint->viewNamespace(),
            'config_key' => $blueprint->configKey(),
            'parent_module' => $blueprint->parentModule ?? '',
            'year' => '2026',
        ];
    }

    private function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function writeFile(string $root, string $relative, string $content, bool $force): void
    {
        $path = $root . DIRECTORY_SEPARATOR . $relative;

        if (file_exists($path) && !$force) {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function touchKeep(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($path)) {
            touch($path);
        }
    }
}

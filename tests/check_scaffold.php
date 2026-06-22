<?php declare(strict_types=1);

require dirname(__DIR__, 3) . '/third_party/autoload.php';

use Webkernel\StdLifecycle\Installer\SLCPackageType;
use Webkernel\XWebdev\Config\ConfigLoader;
use Webkernel\XWebdev\PackageBlueprint;
use Webkernel\XWebdev\PackageScaffold;

$config = new ConfigLoader([
    'default_vendor' => 'webkernel',
    'default_namespace' => 'Webkernel',
    'default_license' => 'EPL-2.0',
    'github_org' => 'webkernelphp',
]);

$component = PackageBlueprint::make(
    SLCPackageType::Component,
    'webkernelphp',
    'component-http',
    'Webkernelphp\\Component\\Http\\'
);

assert($component->directory === 'component-http');
assert($component->composerName === 'webkernelphp/component-http');
assert($component->loadFileName() === 'load.component-http.functions.php');

$weird = PackageBlueprint::make(
    SLCPackageType::Component,
    'webkernel',
    'fuck-suck-api',
    'Webkernel\\Fuck\\Suck\\Api\\'
);

assert($weird->directory === 'fuck-suck-api');
assert($weird->composerName === 'webkernel/fuck-suck-api');

assert(PackageBlueprint::defaultNamespace('Webkernel', 'component-http') === 'Webkernel\\Component\\Http\\');

$scaffold = new PackageScaffold($config);
$built = $scaffold->create(
    SLCPackageType::Component,
    'webkernel',
    'json-escape-test',
    'Webkernel\\Json\\Escape\\Test\\',
    null,
    true
);

$composerPath = dirname(__DIR__, 3) . '/packages/json-escape-test/composer.json';
$composer = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
assert($composer['extra']['laravel']['providers'][0] === 'Webkernel\\Json\\Escape\\Test\\Providers\\TestServiceProvider');
assert($composer['require']['php'] === '>=8.4');

$platformPhp = $composer['config']['platform']['php'] ?? null;
assert($platformPhp === null || ! preg_match('/[<>=]/', (string) $platformPhp));

shell_exec('rm -rf ' . escapeshellarg(dirname(__DIR__, 3) . '/packages/json-escape-test'));

echo "check_scaffold: ok\n";

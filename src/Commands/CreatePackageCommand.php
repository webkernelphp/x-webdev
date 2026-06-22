<?php declare(strict_types=1);

namespace Webkernel\XWebdev\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Webkernel\StdLifecycle\Installer\SLCPackageType;
use Webkernel\XWebdev\Exceptions\XWebdevException;
use Webkernel\XWebdev\PackageBlueprint;
use Webkernel\XWebdev\PackageScaffold;
use Webkernel\XWebdev\XCommand;
use Webkernel\XWebdev\XWebdev;

final class CreatePackageCommand extends XCommand
{
    public function __construct(XWebdev $webdev)
    {
        parent::__construct($webdev, 'create:package');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new Webkernel package scaffold under packages/.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Package slug (not vendor/package).')
            ->addOption('vendor', null, InputOption::VALUE_REQUIRED, 'Composer vendor.')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'SLCPackageType value.')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'PSR-4 root namespace.')
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent module (vendor/package) for feature types.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing scaffold files.');
    }

    #[\Override]
    protected function needsInput(InputInterface $input): bool
    {
        if ($this->resolvedVendor($input) === null) {
            return true;
        }
        if (!$this->resolvedType($input) instanceof \Webkernel\StdLifecycle\Installer\SLCPackageType) {
            return true;
        }
        if ($this->resolvedSlug($input) === null) {
            return true;
        }
        return $this->resolvedNamespace($input, false) === null;
    }

    protected function promptMissing(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');
        $defaultVendor = $this->config()->getString('default_vendor', 'webkernel');

        if ($this->resolvedVendor($input) === null) {
            $answer = $helper->ask(
                $input,
                $output,
                new Question(" <fg=cyan>Vendor</> [{$defaultVendor}]: ", $defaultVendor)
            );

            if (is_string($answer) && $answer !== '') {
                $input->setOption('vendor', $answer);
            }
        }

        if (!$this->resolvedType($input) instanceof \Webkernel\StdLifecycle\Installer\SLCPackageType) {
            $choices = array_map(static fn (SLCPackageType $t): string => $t->value, SLCPackageType::cases());
            $answer = $helper->ask(
                $input,
                $output,
                new ChoiceQuestion(' <fg=cyan>Package type</>: ', $choices)
            );

            if (is_string($answer) && $answer !== '') {
                $input->setOption('type', $answer);
            }
        }

        if ($this->resolvedSlug($input) === null) {
            $answer = $helper->ask(
                $input,
                $output,
                new Question(' <fg=cyan>Package name</> (slug, any prefix you want): ')
            );

            if (is_string($answer) && $answer !== '') {
                $input->setArgument('name', $this->extractSlug($answer));
            }
        }

        $slug = $this->resolvedSlug($input);
        if ($slug !== null && $this->resolvedNamespace($input, false) === null) {
            $defaultNs = PackageBlueprint::defaultNamespace(
                $this->config()->getString('default_namespace', 'Webkernel'),
                $slug
            );

            $answer = $helper->ask(
                $input,
                $output,
                new Question(" <fg=cyan>PSR-4 namespace</> [{$defaultNs}]: ", $defaultNs)
            );

            if (is_string($answer) && $answer !== '') {
                $input->setOption('namespace', rtrim($answer, '\\') . '\\');
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vendor = $this->resolvedVendor($input);
        $type = $this->resolvedType($input);
        $slug = $this->resolvedSlug($input);
        $namespace = $this->resolvedNamespace($input);

        if ($vendor === null || !$type instanceof \Webkernel\StdLifecycle\Installer\SLCPackageType || $slug === null || $namespace === null) {
            return $this->gentleStart($output, $this->hintLines());
        }

        $parent = $input->getOption('parent');
        $parentModule = is_string($parent) && $parent !== '' ? $parent : null;

        try {
            $built = (new PackageScaffold($this->config()))->create(
                $type,
                $vendor,
                $slug,
                $namespace,
                $parentModule,
                (bool) $input->getOption('force')
            );
        } catch (XWebdevException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }

        $output->writeln("<info>Created {$built['composer_name']} at packages/{$built['directory']}</info>");
        $output->writeln('<info>Namespace: ' . rtrim($built['namespace'], '\\') . '</info>');

        return self::SUCCESS;
    }

    private function resolvedVendor(InputInterface $input): ?string
    {
        $vendor = $input->getOption('vendor');

        if (is_string($vendor) && $vendor !== '') {
            return strtolower($vendor);
        }

        $name = $input->getArgument('name');
        if (is_string($name) && str_contains($name, '/')) {
            [$parsedVendor] = explode('/', $name, 2);

            return strtolower($parsedVendor);
        }

        return null;
    }

    private function resolvedType(InputInterface $input): ?SLCPackageType
    {
        $typeValue = $input->getOption('type');

        if (!is_string($typeValue) || $typeValue === '') {
            return null;
        }

        return SLCPackageType::tryFrom($typeValue);
    }

    private function resolvedSlug(InputInterface $input): ?string
    {
        $name = $input->getArgument('name');

        if (!is_string($name) || $name === '') {
            return null;
        }

        return $this->extractSlug($name);
    }

    private function resolvedNamespace(InputInterface $input, bool $allowDefault = true): ?string
    {
        $namespace = $input->getOption('namespace');

        if (is_string($namespace) && $namespace !== '') {
            return rtrim($namespace, '\\') . '\\';
        }

        if (!$allowDefault) {
            return null;
        }

        $slug = $this->resolvedSlug($input);

        return $slug === null ? null : PackageBlueprint::defaultNamespace(
            $this->config()->getString('default_namespace', 'Webkernel'),
            $slug
        );
    }

    private function extractSlug(string $name): string
    {
        if (str_contains($name, '/')) {
            [, $slug] = explode('/', $name, 2);

            return $slug;
        }

        return $name;
    }

    /** @return list<string> */
    private function hintLines(): array
    {
        $defaultVendor = $this->config()->getString('default_vendor', 'webkernel');
        $defaultNs = $this->config()->getString('default_namespace', 'Webkernel');

        return [
            '<comment>Interactive order:</comment> vendor → type → name → namespace',
            '',
            '<comment>Need:</comment>',
            "  • <fg=cyan>--vendor</>  default: {$defaultVendor}",
            '  • <fg=cyan>--type</>    SLCPackageType value',
            '  • <fg=cyan>name</>     slug as-is (component-http, fuck-suck-api, …)',
            "  • <fg=cyan>--namespace</>  default from slug + {$defaultNs}",
            '',
            '<comment>Examples:</comment>',
            "  third_party/bin/x-webdev create:package component-http --vendor=webkernelphp --type=webkernel-component",
            '  third_party/bin/x-webdev create:package fuck-suck-api --type=webkernel-component',
            '',
            '<comment>Types:</comment>',
            ...array_map(
                static fn (SLCPackageType $t): string => "  • <fg=gray>{$t->value}</> — {$t->description()}",
                SLCPackageType::cases()
            ),
        ];
    }
}

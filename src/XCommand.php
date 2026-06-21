<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for x-webdev native commands.
 */
abstract class XCommand extends Command
{
    public function __construct(
        protected readonly XWebdev $webdev,
        string $name,
    ) {
        parent::__construct($name);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isInteractive() || !$this->needsInput($input)) {
            return;
        }

        $this->showIntro($output);
        $this->promptMissing($input, $output);
    }

    protected function needsInput(InputInterface $input): bool
    {
        return false;
    }

    protected function showIntro(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(sprintf(
            ' <info>%s</info> — %s',
            $this->getName(),
            $this->getDescription() ?? ''
        ));
        $output->writeln('');
    }

    /**
     * Ask for missing values when running interactively. Override in subclasses.
     */
    protected function promptMissing(InputInterface $input, OutputInterface $output): void
    {
    }

    /**
     * Friendly guidance when required input is still missing (non-interactive or skipped prompts).
     *
     * @param list<string> $lines Hint lines without leading space (added automatically).
     */
    protected function gentleStart(OutputInterface $output, array $lines = []): int
    {
        $this->showIntro($output);

        foreach ($lines as $line) {
            $output->writeln(' ' . $line);
        }

        if ($lines !== []) {
            $output->writeln('');
        }

        $output->writeln(' <comment>' . $this->getSynopsis(true) . '</comment>');
        $output->writeln('');

        return Command::SUCCESS;
    }

    protected function packagesPath(): string
    {
        return $this->webdev->packagesPath();
    }

    protected function config(): Config\ConfigLoader
    {
        return $this->webdev->getConfig();
    }
}

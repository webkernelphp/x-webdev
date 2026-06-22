<?php declare(strict_types=1);

namespace Webkernel\XWebdev\I18n;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait ConsoleBridge
{
    private SymfonyStyle $io;

    private InputInterface $input;

    public function bindIo(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function line(string $message): void
    {
        $this->io->writeln($message);
    }

    protected function info(string $message): void
    {
        $this->io->writeln("<info>{$message}</info>");
    }

    protected function error(string $message): void
    {
        $this->io->writeln("<error>{$message}</error>");
    }

    protected function warn(string $message): void
    {
        $this->io->writeln("<comment>{$message}</comment>");
    }

    protected function newLine(int $count = 1): void
    {
        $this->io->newLine($count);
    }

    protected function ask(string $question, mixed $default = null): mixed
    {
        return $this->io->ask($question, $default);
    }

    protected function confirm(string $question, bool $default = true): bool
    {
        return $this->io->confirm($question, $default);
    }

    protected function option(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    protected function argument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }
}

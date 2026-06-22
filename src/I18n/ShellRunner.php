<?php declare(strict_types=1);

namespace Webkernel\XWebdev\I18n;

use Symfony\Component\Process\Process;

final class ShellRunner
{
    public function runTranslate(
        string $engine,
        string $sourceLang,
        string $targetLang,
        string $text,
        int $timeoutSeconds = 120,
    ): string {
        $process = new Process(
            ['trans', '-e', $engine, '-brief', "{$sourceLang}:{$targetLang}", $text],
            timeout: $timeoutSeconds,
        );
        $process->run();

        return trim($process->getOutput());
    }

    public function lintPhp(string $file, int $timeoutSeconds = 30): bool
    {
        $process = new Process(['php', '-l', $file], timeout: $timeoutSeconds);
        $process->run();

        return $process->isSuccessful();
    }

    public function runShellWithProgress(
        string $command,
        callable $onSlowFeedback,
        int $slowThresholdMs = 3000,
        ?int $timeoutSeconds = null,
    ): string {
        $process = Process::fromShellCommandline($command, timeout: $timeoutSeconds);
        $process->start();

        $start = microtime(true);
        $feedbackGiven = false;

        while ($process->isRunning()) {
            if (
                ! $feedbackGiven
                && ((microtime(true) - $start) * 1000) >= $slowThresholdMs
            ) {
                $onSlowFeedback();
                $feedbackGiven = true;
            }

            usleep(100_000);
        }

        return trim($process->getOutput());
    }
}

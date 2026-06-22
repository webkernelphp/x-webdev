<?php declare(strict_types=1);

namespace Webkernel\XWebdev\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webkernel\XWebdev\I18n\TranslationHub;
use Webkernel\XWebdev\XCommand;
use Webkernel\XWebdev\XWebdev;

final class LangCommand extends XCommand
{
    public function __construct(XWebdev $webdev)
    {
        parent::__construct($webdev, 'i18n:lang');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('TranslationHub: multilingual translation management for Webkernel packages')
            ->setAliases(['i18n:translate', 'i18n:translation-hub'])
            ->addArgument('text', InputArgument::OPTIONAL, 'English text to translate')
            ->addArgument('key', InputArgument::OPTIONAL, 'Translation key (optional)')
            ->addOption('change-key', null, InputOption::VALUE_NONE, 'Change existing key mode')
            ->addOption('old-key', null, InputOption::VALUE_REQUIRED, 'Old key to change (use with --change-key)')
            ->addOption('new-key', null, InputOption::VALUE_REQUIRED, 'New key to change to (use with --change-key)')
            ->addOption('restore', null, InputOption::VALUE_NONE, 'Restore from backup')
            ->addOption('validate-only', null, InputOption::VALUE_NONE, 'Only validate existing files')
            ->addOption('repair', null, InputOption::VALUE_NONE, 'Repair syntax errors in existing files')
            ->addOption('retranslate', null, InputOption::VALUE_NONE, 'Retranslate all existing entries from English')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED, 'Target specific language for retranslation')
            ->addOption('protect', null, InputOption::VALUE_NONE, 'Mark keys as protected')
            ->addOption('unprotect', null, InputOption::VALUE_NONE, 'Remove protection from specific keys')
            ->addOption('before', null, InputOption::VALUE_REQUIRED, 'Unprotect keys protected before this time')
            ->addOption('after', null, InputOption::VALUE_REQUIRED, 'Unprotect keys protected after this time')
            ->addOption('keys', null, InputOption::VALUE_REQUIRED, 'Comma-separated keys for --protect/--unprotect')
            ->addOption('all-langs', null, InputOption::VALUE_NONE, 'Apply protection to all languages')
            ->addOption('migrate-timestamps', null, InputOption::VALUE_NONE, 'Add missing protected_at timestamps');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hub = new TranslationHub();
        $hub->bindIo($input, $output);

        return $hub->runHub();
    }
}

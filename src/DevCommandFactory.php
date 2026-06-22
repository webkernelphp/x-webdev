<?php declare(strict_types=1);

namespace Webkernel\XWebdev;

use Symfony\Component\Console\Command\Command;
use Webkernel\XWebdev\Exceptions\XWebdevException;

final readonly class DevCommandFactory
{
    public function __construct(private XWebdev $webdev)
    {
    }

    public function create(string $class): \Webkernel\XWebdev\XCommand
    {
        if (!class_exists($class) || !is_subclass_of($class, XCommand::class)) {
            throw new XWebdevException("Command '{$class}' must extend XCommand.");
        }

        return new $class($this->webdev);
    }
}

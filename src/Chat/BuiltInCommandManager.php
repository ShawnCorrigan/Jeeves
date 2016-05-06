<?php

namespace Room11\Jeeves\Chat;

use Room11\Jeeves\Chat\Message\Command;
use Room11\Jeeves\Log\Level;
use Room11\Jeeves\Log\Logger;
use Room11\Jeeves\Storage\Ban as BanStorage;

class BuiltInCommandManager
{
    private $banStorage;
    private $logger;

    /**
     * @var BuiltInCommand[]
     */
    private $commands = [];

    public function __construct(BanStorage $banStorage, Logger $logger)
    {
        $this->banStorage = $banStorage;
        $this->logger = $logger;
    }

    public function register(BuiltInCommand $command): BuiltInCommandManager
    {
        foreach ($command->getCommandNames() as $commandName) {
            $this->commands[$commandName] = $command;
        }

        return $this;
    }

    public function handle(Command $command): \Generator
    {
        $eventId = $command->getEvent()->getEventId();
        $userId = $command->getUserId();

        $this->logger->log(Level::DEBUG, "Processing event #{$eventId} for built in commands");

        if (yield from $this->banStorage->isBanned($userId)) {
            $this->logger->log(Level::DEBUG, "User #{$userId} is banned, ignoring event #{$eventId} for built in commands");
            return;
        }

        $commandName = $command->getCommandName();
        if (isset($this->commands[$commandName])) {
            $this->logger->log(Level::DEBUG, "Passing event #{$eventId} to built in command handler " . get_class($this->commands[$commandName]));
            yield from $this->commands[$commandName]->handleCommand($command);
        }

        $this->logger->log(Level::DEBUG, "Event #{$eventId} processed for built in commands");
    }
}

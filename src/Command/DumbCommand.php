<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumbCommand extends Command
{
    protected static $defaultName = 'dumb';
    protected static $defaultDescription = 'Just a dumb command';

    /**
     * @var bool
     */
    private $requireArg;

    public function __construct(bool $requireArg = false)
    {
        $this->requireArg = $requireArg;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This is just a dumb command that does nothing useful...')
             ->addArgument('arg', $this->requireArg ? InputArgument::REQUIRED : InputArgument::OPTIONAL, 'User argument');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
           'Welcome to the dumb command that does nothing useful',
            '----------------------------------------------------',
        ]);

        return Command::SUCCESS;
    }
}
<?php

namespace Droid\Plugin\Cron\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Cron\Model\JobFactory;

class JobDeleteCommand extends Command
{
    use CheckableTrait;

    protected $jobFactory;

    public function __construct(JobFactory $jobFactory, $name = null)
    {
        $this->jobFactory = $jobFactory;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this
            ->setName('cron:deljob')
            ->setDescription('Delete a cron job.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the job.'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $job = $this->jobFactory->create($input->getArgument('name'));
        $filename = $job->getFilePath();

        if (!file_exists($filename)) {
            $output->writeln(
                sprintf(
                    'The job file "%s" does not exist. Nothing to do.',
                    $filename
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $this->markChange();

        if ($this->checkMode()) {
            $output->writeln(
                sprintf('I would delete the job "%s".', $job->name)
            );
            $this->reportChange($output);
            return 0;
        }

        if (!unlink($filename)) {
            throw new RuntimeException(
                sprintf('I cannot delete the job file "%s".', $filename)
            );
        }

        $output->writeln(
            sprintf('I have successfully deleted the job "%s".', $job->name)
        );

        $this->reportChange($output);
        return 0;
    }
}

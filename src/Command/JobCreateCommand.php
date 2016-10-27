<?php

namespace Droid\Plugin\Cron\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Cron\Model\JobFactory;

class JobCreateCommand extends Command
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
            ->setName('cron:addjob')
            ->setDescription('Create a cron job.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the job.'
            )
            ->addArgument(
                'schedule',
                InputArgument::REQUIRED,
                'The job schedule.'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'The name of the user account under which to run the job command.'
            )
            ->addArgument(
                'job-command',
                InputArgument::REQUIRED,
                'The job command.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Overwrite an existing job of the same name.'
            )
            ->addOption(
                'mail',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma separated list of user names or email addresses to which to email the job output.'
            )
            ->addOption(
                'no-mail',
                null,
                InputOption::VALUE_NONE,
                'Do not email the job output.'
            )
            ->addOption(
                'home',
                null,
                InputOption::VALUE_REQUIRED,
                'A value for the HOME environment setting.'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'A value for the PATH environment setting.'
            )
            ->addOption(
                'shell',
                null,
                InputOption::VALUE_REQUIRED,
                'A value for the SHELL environment setting.'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $job = $this->jobFactory->create($input->getArgument('name'));

        if (!$input->getOption('force') && file_exists($job->getFilePath())) {
            throw new RuntimeException(
                sprintf(
                    'The job named "%s" already exists. Use the --force.',
                    $job->name
                )
            );
        }

        $job
            ->setSchedule($input->getArgument('schedule'))
            ->setUsername($input->getArgument('username'))
            ->setCommand($input->getArgument('job-command'))
        ;

        $this->markChange();

        if ($this->checkMode()) {
            $output->writeln(
                sprintf('I would create the job "%s".', $job->name)
            );
            $this->reportChange($output);
            return 0;
        }

        if ($input->getOption('no-mail')) {
            $job->setEnv('MAILTO', '');
        } elseif ($input->getOption('mail')) {
            $job->setEnv('MAILTO', $input->getOption('mail'));
        }

        if ($input->getOption('home')) {
            $job->setEnv('HOME', $input->getOption('home'));
        }
        if ($input->getOption('path')) {
            $job->setEnv('PATH', $input->getOption('path'));
        }
        if ($input->getOption('shell')) {
            $job->setEnv('SHELL', $input->getOption('shell'));
        }

        $fh = @fopen($job->getFilePath(), 'wb');
        if ($fh === false) {
            throw new RuntimeException(
                sprintf(
                    'I cannot open the file "%s" for writing.',
                    $job->getFilePath()
                )
            );
        }
        $content = (string) $job;
        fwrite($fh, $content, strlen($content));
        fclose($fh);
        if (!chmod($job->getFilePath(), 0640)) {
            throw new RuntimeException(
                sprintf(
                    'I have created the job file "%s" but cannot set the file mode to 0640.',
                    $job->getFilePath()
                )
            );
        }

        $output->writeln(
            sprintf('I have successfully created the job "%s".', $job->name)
        );

        $this->reportChange($output);
        return 0;
    }
}

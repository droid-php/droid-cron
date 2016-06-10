<?php

namespace Droid\Plugin\Cron;

use Droid\Plugin\Cron\Command\JobCreateCommand;
use Droid\Plugin\Cron\Command\JobDeleteCommand;
use Droid\Plugin\Cron\Model\JobFactory;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        return array(
            new JobCreateCommand(new JobFactory($this->droid, '/etc/cron.d')),
            new JobDeleteCommand(new JobFactory($this->droid, '/etc/cron.d')),
        );
    }
}

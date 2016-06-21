<?php

namespace Droid\Plugin\Cron\Model;

use Symfony\Component\Console\Application;

class JobFactory
{
    private $namePrefix;
    private $jobFilePath;

    public function __construct(Application $app, $jobFilePath)
    {
        $this->namePrefix = sprintf('%s_', strtolower($app->getName()));
        $this->jobFilePath = $jobFilePath;
    }

    public function create($jobName)
    {
        $job = new Job;
        return $job
            ->setPath($this->jobFilePath)
            ->setName($this->namePrefix . $jobName)
        ;
    }
}

<?php

namespace Droid\Test\Plugin\Cron\Command;

use RuntimeException;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Cron\Command\JobDeleteCommand;
use Droid\Plugin\Cron\Model\Job;
use Droid\Plugin\Cron\Model\JobFactory;

class JobDeleteCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $fac;
    protected $job;
    protected $tester;
    protected $vfs;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('etc', 0750, array('cron.d' => array()));

        $this->app = new Application;

        $this->job = $this
            ->getMockBuilder(Job::class)
            ->getMock()
        ;
        $this
            ->job
            ->method('getFilePath')
            ->willReturn(vfsStream::url('etc/cron.d/my-app_the-job'))
        ;

        $this->fac = $this
            ->getMockBuilder(JobFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(array('create'))
            ->getMock()
        ;
        $this
            ->fac
            ->method('create')
            ->willReturn($this->job)
        ;

        $command = new JobDeleteCommand($this->fac);
        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    public function testCommandDoesNothingWhenJobFileDoesNotExist()
    {
        $this->assertFalse(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));

        $this->tester->execute(array(
            'command' => $this->app->find('cron:deljob')->getName(),
            'name' => 'the-job',
        ));

        $this->assertFalse(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));
        $this->assertRegExp(
            '/^The job file "[^"]*" does not exist\./',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot delete the job file
     */
    public function testCommandThrowsExceptionWhenItFailsToDeleteJobFile()
    {
        vfsStream::newFile('my-app_the-job')->at($this->vfs->getChild('cron.d'));

        $this->vfs->getChild('cron.d')->chmod(0500); # make cron.d unwritable

        $this->tester->execute(array(
            'command' => $this->app->find('cron:deljob')->getName(),
            'name' => 'the-job'
        ));
    }

    public function testCommandDoesNotDeleteJobFileInCheckMode()
    {
        vfsStream::newFile('my-app_the-job')->at($this->vfs->getChild('cron.d'));

        $this->job->name = 'the-job';

        $this->tester->execute(array(
            'command' => $this->app->find('cron:deljob')->getName(),
            'name' => 'the-job',
            '--check' => true,
        ));

        $this->assertTrue(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));
        $this->assertRegExp(
            '/I would delete the job "the-job"/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandDeletesJobFile()
    {
        vfsStream::newFile('my-app_the-job')->at($this->vfs->getChild('cron.d'));
        $this->assertTrue(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));

        $this->job->name = 'the-job';

        $this->tester->execute(array(
            'command' => $this->app->find('cron:deljob')->getName(),
            'name' => 'the-job',
        ));

        $this->assertFalse(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));
        $this->assertRegExp(
            '/I have successfully deleted the job "the-job"/',
            $this->tester->getDisplay()
        );
    }
}

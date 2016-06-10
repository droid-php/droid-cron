<?php

namespace Droid\Test\Plugin\Cron\Command;

use RuntimeException;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Cron\Command\JobCreateCommand;
use Droid\Plugin\Cron\Model\Job;
use Droid\Plugin\Cron\Model\JobFactory;

class JobCreateCommandTest extends \PHPUnit_Framework_TestCase
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

        $command = new JobCreateCommand($this->fac);
        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The job named "the-job" already exists
     */
    public function testCommandThrowsExceptionWhenJobFileExistsAndNotForced()
    {
        vfsStream::newFile('my-app_the-job')->at($this->vfs->getChild('cron.d'));
        $this->assertTrue(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));

        $this->job->name = 'the-job';

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
        ));
    }

    public function testCommandDoesNotCreateJobFileInCheckMode()
    {
        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;

        $this->assertFalse(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
            '--check' => true,
        ));

        $this->assertFalse(file_exists(vfsStream::url('etc/cron.d/my-app_the-job')));
        $this->assertRegExp(
            '/I would create the job "the-job"/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandSetsEmptyMailtoEnvSettingWhenNomailArgIsGiven()
    {
        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('__toString')
            ->willReturn('job-content')
        ;

        $this
            ->job
            ->expects($this->once())
            ->method('setEnv')
            ->with('MAILTO', '')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
            '--no-mail' => true,
        ));
    }

    public function testCommandSetsEnvSettingsWithEnvArgs()
    {
        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('__toString')
            ->willReturn('job-content')
        ;

        $this
            ->job
            ->expects($this->exactly(4))
            ->method('setEnv')
            ->withConsecutive(
                array('MAILTO', 'root'),
                array('HOME', '/root'),
                array('PATH', '/usr/local/bin'),
                array('SHELL', '/bin/bash')
            )
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
            '--mail' => 'root',
            '--home' => '/root',
            '--path' => '/usr/local/bin',
            '--shell' => '/bin/bash',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot open the file "vfs://etc/cron.d/my-app_the-job" for writing
     */
    public function testCommandThrowsExceptionWhenJobFileCannotBeWrittenTo()
    {
        $this->vfs->getChild('cron.d')->chmod(0400);
        $this->assertFalse(is_writable(vfsStream::url('etc/cron.d')));

        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('__toString')
            ->willReturn('job-content')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
        ));
    }

    public function testCommandCreatesJobFile()
    {
        $this->assertFalse(
            file_exists(vfsStream::url('etc/cron.d/my-app_the-job'))
        );

        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('__toString')
            ->willReturn('job-content')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
        ));

        $this->assertTrue(
            file_exists(vfsStream::url('etc/cron.d/my-app_the-job'))
        );
        $this->assertSame(
            0640,
            $this->vfs->getChild('cron.d')->getChild('my-app_the-job')->getPermissions()
        );
        $this->assertSame(
            'job-content',
            $this->vfs->getChild('cron.d')->getChild('my-app_the-job')->getContent()
        );
        $this->assertRegExp(
            '/I have successfully created the job "the-job"/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandOverwritesExistingJobFileOfTheSameName()
    {
        vfsStream::newFile('my-app_the-job')
            ->at($this->vfs->getChild('cron.d'))
            ->setContent('old-job-content')
        ;
        $this->assertTrue(
            file_exists(vfsStream::url('etc/cron.d/my-app_the-job'))
        );

        $this->job->name = 'the-job';

        $this
            ->job
            ->method('setSchedule')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setUsername')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('setCommand')
            ->willReturnSelf()
        ;
        $this
            ->job
            ->method('__toString')
            ->willReturn('job-content')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('cron:addjob')->getName(),
            'name' => 'the-job',
            'schedule' => '* * * * *',
            'username' => 'some-user',
            'job-command' => 'do_the_thing',
            '--force' => true,
        ));

        $this->assertTrue(
            file_exists(vfsStream::url('etc/cron.d/my-app_the-job'))
        );
        $this->assertSame(
            0640,
            $this->vfs->getChild('cron.d')->getChild('my-app_the-job')->getPermissions()
        );
        $this->assertSame(
            'job-content',
            $this->vfs->getChild('cron.d')->getChild('my-app_the-job')->getContent()
        );
        $this->assertRegExp(
            '/I have successfully created the job "the-job"/',
            $this->tester->getDisplay()
        );
    }
}

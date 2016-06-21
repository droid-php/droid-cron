<?php

namespace Droid\Test\Plugin\Cron\Model;

use Symfony\Component\Console\Application;

use Droid\Plugin\Cron\Model\Job;
use Droid\Plugin\Cron\Model\JobFactory;

class JobFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    const TEST_APP_NAME = 'Droidtastic';

    protected function setUp()
    {
        $this->app = $this
            ->getMockBuilder(Application::class)
            ->setMethods(array('getName'))
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->app->method('getName')->willReturn(self::TEST_APP_NAME);
    }

    public function testCreateReturnsJob()
    {
        $fac = new JobFactory($this->app, '');
        $this->assertInstanceOf(Job::class, $fac->create('some-job-name'));
    }

    public function testCreateSetsTheJobPath()
    {
        $path = 'path/to/somewhere';

        $fac = new JobFactory($this->app, $path);

        $this->assertSame($path, $fac->create('some-job-name')->path);
    }

    public function testCreateSetsAJobNameWithPrefix()
    {
        $givenName = 'some-job-name';

        $this
            ->app
            ->expects($this->once())
            ->method('getName')
        ;

        $fac = new JobFactory($this->app, '');

        $this->assertSame(
            strtolower(self::TEST_APP_NAME) . '_' . $givenName,
            $fac->create('some-job-name')->name,
            'The job name is the lower case app name + underscore + the name given to create()'
        );
    }
}

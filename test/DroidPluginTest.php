<?php

namespace Droid\Test\Plugin\Cron;

use Symfony\Component\Console\Application;

use Droid\Plugin\Cron\DroidPlugin;

class DroidPluginTest extends \PHPUnit_Framework_TestCase
{
    protected $plugin;

    protected function setUp()
    {
        $app = $this
            ->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
        ;
        $this->plugin = new DroidPlugin($app);
    }

    public function testGetCommandsReturnsExpectedCommands()
    {
        $this->assertSame(
            array(
                'Droid\Plugin\Cron\Command\JobCreateCommand',
                'Droid\Plugin\Cron\Command\JobDeleteCommand',
            ),
            array_map(
                function ($x) {
                    return get_class($x);
                },
                $this->plugin->getCommands()
            )
        );
    }
}

<?php

namespace Droid\Test\Plugin\Cron\Model;

use InvalidArgumentException;

use Droid\Plugin\Cron\Model\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFilePath()
    {
        $job = new Job;
        $this->assertSame(
            'path/to/some-job',
            $job
                ->setPath('path/to')
                ->setName('some-job')
                ->getFilePath()
        );
    }

    /**
     * @dataProvider getJobNames
     */
    public function testSetNameEnsuresALegalNameForACronFilename(
        $argument,
        $expectedResult,
        $exception = null
    ) {
        $job = new Job;

        if ($exception) {
            $this->setExpectedException($exception);
            $job->setName($argument);
            return;
        }

        $this->assertSame($expectedResult, $job->setName($argument)->name);
    }

    public function getJobNames()
    {
        return array(
            'All spaces are replaced with underscores, except leading and trailing' => array(
                '  this is a name ', 'this_is_a_name',
            ),
            'Case is retained' => array(
                'ThisIsAName', 'ThisIsAName',
            ),
            'Non-legal characters are stripped' => array(
                '0_A-a!"£$%^&*()+={}[]~#@\':;?/>.<,|\\¬`|✓', '0_A-a',
            ),
            'Exception if an empty string is given' => array(
                '', null, InvalidArgumentException::class
            ),
            'Exception if an empty string results' => array(
                '!', null, InvalidArgumentException::class
            ),
        );
    }

    /**
     * @dataProvider getSchedules
     */
    public function testSetScheduleEnsuresALegalScheduleForACronFile(
        $argument,
        $exception = null
    ) {
        $job = new Job;

        if ($exception) {
            $this->setExpectedException($exception);
            $job->setSchedule($argument);
            return;
        }

        $job->setSchedule($argument);
    }

    public function getSchedules()
    {
        return array(
            'Random Nonesense' => array(
                'random nonesense', InvalidArgumentException::class
            ),
            'Too few fields' => array(
                '* * * *', InvalidArgumentException::class
            ),
            'Too many fields' => array(
                '* * * * * *', InvalidArgumentException::class
            ),
            'Just the right number of fields' => array(
                '* * * * *'
            ),
            'Min too low ' => array(
                '-1 * * * *', InvalidArgumentException::class
            ),
            'Min too high' => array(
                '60 * * * *', InvalidArgumentException::class
            ),
            'Min just right' => array(
                '59 * * * *'
            ),
            'Hr too low' => array(
                '* -1 * * *', InvalidArgumentException::class
            ),
            'Hr too high' => array(
                '* 24 * * *', InvalidArgumentException::class
            ),
            'Hr just right' => array(
                '* 23 * * *'
            ),
            'Day too low' => array(
                '* * -1 * *', InvalidArgumentException::class
            ),
            'Day too high' => array(
                '* * 32 * *', InvalidArgumentException::class
            ),
            'Day just right' => array(
                '* * 31 * *'
            ),
            'Month too low' => array(
                '* * * -1 *', InvalidArgumentException::class
            ),
            'Month too high' => array(
                '* * * 13 *', InvalidArgumentException::class
            ),
            'Month just right' => array(
                '* * * 12 *'
            ),
            'Dow too low' => array(
                '* * * * -1', InvalidArgumentException::class
            ),
            'Dow too high' => array(
                '* * * * 8', InvalidArgumentException::class
            ),
            'Dow just right' => array(
                '* * * * 7'
            ),
            'Ranges' => array(
                '*/2 1-5/2,6-23/3 1-2,5-10 1,2-4,7,9-11 3,4'
            ),
            'Bad ranges are bad' => array(
                '*/-1 1-5/2,6-26/3 1-2,5-32 1,2-4,7,9-13 3,8',
                InvalidArgumentException::class
            ),
            'Month names not allowed in min field' => array(
                'jan * * * *', InvalidArgumentException::class
            ),
            'Month names not allowed in hr field' => array(
                '* jan * * *', InvalidArgumentException::class
            ),
            'Month names not allowed in day field' => array(
                '* * jan * *', InvalidArgumentException::class
            ),
            'Month names are allowed in month field' => array(
                '* * * jan *'
            ),
            'Month names not allowed in dow field' => array(
                '* * * * jan', InvalidArgumentException::class
            ),
            'Month names match first three letters, but more are OK' => array(
                '* * * january *'
            ),
            'Month names are case insensitive' => array(
                '* * * JaNuary *'
            ),
            'Range of months not allowed' => array(
                '* * * jan-mar *', InvalidArgumentException::class
            ),
            'DoW names not allowed in min field' => array(
                'mon * * * *', InvalidArgumentException::class
            ),
            'DoW names not allowed in hr field' => array(
                '* mon * * *', InvalidArgumentException::class
            ),
            'DoW names not allowed in day field' => array(
                '* * mon * *', InvalidArgumentException::class
            ),
            'DoW names not allowed in month field' => array(
                '* * * mon *', InvalidArgumentException::class
            ),
            'DoW names are allowed in dow field' => array(
                '* * * * mon'
            ),
            'DoW names match first three letters, but more are OK' => array(
                '* * * * tuesday'
            ),
            'DoW names are case insensitive' => array(
                '* * * * WeDnesday'
            ),
            'Range of DoW not allowed' => array(
                '* * * * mon-wed', InvalidArgumentException::class
            ),
            'Bogus special is not allowed' => array(
                '@fortnighly', InvalidArgumentException::class
            ),
            '@reboot special is allowed' => array('@reboot'),
            '@yearly special is allowed' => array('@yearly'),
            '@annually special is allowed' => array('@annually'),
            '@monthly special is allowed' => array('@monthly'),
            '@weekly special is allowed' => array('@weekly'),
            '@daily special is allowed' => array('@daily'),
            '@midnight special is allowed' => array('@midnight'),
            '@hourly special is allowed' => array('@hourly'),
        );
    }

    public function testToString()
    {
        $job = new Job;
        $this->assertSame(
            implode(
                "\n",
                array(
                    '# some-job-name',
                    'PATH="/usr/local/bin"',
                    'SHELL="/bin/bash"',
                    'MAILTO=""',
                    '* * * * * some-user some-command',
                    '',
                )
            ),
            (string) $job
                ->setName('some-job-name')
                ->setSchedule('* * * * *')
                ->setUsername('some-user')
                ->setCommand('some-command')
                ->setEnv('PATH', '/usr/local/bin')
                ->setEnv('SHELL', '/bin/bash')
                ->setEnv('MAILTO', '')
        );
    }
}

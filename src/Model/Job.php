<?php

namespace Droid\Plugin\Cron\Model;

use InvalidArgumentException;

class Job
{
    public $path;
    public $name;
    public $schedule;
    public $username;
    public $command;
    public $env = array();

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getFilePath()
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }

    public function setName($name)
    {
        $candidate = preg_replace(
            '/[^0-9A-Z_a-z-]/',
            '',
            preg_replace('/\s/', '_', trim($name))
        );
        if ($candidate === '') {
            throw new InvalidArgumentException('The name given is not legal');
        }
        $this->name = $candidate;

        return $this;
    }

    public function setSchedule($schedule)
    {
        $match = preg_match($this->buildScheduleRegex(), $schedule);

        if ($match === false) {
            throw new UnexpectedValueException(
                'Failed to match the schedule to a regular expression.'
            );
        } elseif (!$match) {
            throw new InvalidArgumentException(
                'The schedule given is not legal.'
            );
        }

        $this->schedule = $schedule;

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public function setEnv($name, $value)
    {
        $this->env[] = array($name, $value);

        return $this;
    }

    public function __toString()
    {
        $lines = array('# ' . $this->name);

        foreach ($this->env as list($name, $value)) {
            $lines[] = $name . '="' . $value . '"';
        }

        $lines[] = sprintf(
            '%s %s %s',
            $this->schedule,
            $this->username,
            $this->command
        );

        $lines[] = '';

        return implode("\n", $lines);
    }

    /*
     * Adapted from https://stackoverflow.com/a/2610562 to match just the
     * schedule part of the line and to permit case insensitive matching of
     * day and month names.
     *
     * @author Jordi Salvat i Alabart - with thanks to www.salir.com
     */
    private function buildScheduleRegex()
    {
        $numbers = array(
            'min'   => '[0-5]?\d',
            'hour'  => '[01]?\d|2[0-3]',
            'day'   => '0?[1-9]|[12]\d|3[01]',
            'month' => '[1-9]|1[012]',
            'dow'   => '[0-7]'
        );
        foreach ($numbers as $field => $number) {
            $range = "($number)(-($number)(\/\d+)?)?";
            $field_re[$field] = "\*(\/\d+)?|$range(,$range)*";
        }

        $months = array(
            '[Jj][Aa][Nn]', '[Ff][Ee][Bb]', '[Mm][Aa][Rr]', '[Aa][Pp][Rr]',
            '[Mm][Aa][Yy]', '[Jj][Uu][Nn]', '[Jj][Uu][Ll]', '[Aa][Uu][Gg]',
            '[Ss][Ee][Pp]', '[Oo][Cc][Tt]', '[Nn][Oo][Vv]', '[Dd][Ee][Cc]',
        );
        foreach ($months as $m) {
            $field_re['month'] .= '|' . $m . '\w*';
        }

        $days = array(
            '[Mm][Oo][Nn]', '[Tt][Uu][Ee]', '[Ww][Ee][Dd]', '[Tt][Hh][Uu]',
            '[Ff][Rr][Ii]', '[Ss][Aa][Tt]', '[Ss][Uu][Nn]',
        );
        foreach ($days as $d) {
            $field_re['dow'] .= '|' . $d . '\w*';
        }

        $fields_re = '(' . join(')\s+(', $field_re) . ')';

        $specials = '@reboot|@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly';

        return "/^($fields_re|($specials))$/";
    }
}

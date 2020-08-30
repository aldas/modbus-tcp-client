<?php

use Psr\Log\LoggerInterface;

/**
 * Logged that echos log lines. Useful for examples
 */
class EchoLogger implements LoggerInterface
{

    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $now = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $extra = '';
        if (!empty($context)) {
            $extra = ', data: ' . var_export($context, true);
        }
        echo $now->format("Y-m-d H:i:s.u") . ': [' . $level . '] msg: "' . $message . '"' . $extra . PHP_EOL;
    }
}

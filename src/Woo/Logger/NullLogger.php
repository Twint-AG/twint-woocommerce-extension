<?php

namespace Twint\Woo\Logger;

use WC_Log_Levels;
use WC_Logger_Interface;

class NullLogger implements WC_Logger_Interface{

    public function add($handle, $message, $level = WC_Log_Levels::NOTICE)
    {
        // TODO: Implement add() method.
    }

    public function log($level, $message, $context = array())
    {
        // TODO: Implement log() method.
    }

    public function emergency($message, $context = array())
    {
        // TODO: Implement emergency() method.
    }

    public function alert($message, $context = array())
    {
        // TODO: Implement alert() method.
    }

    public function critical($message, $context = array())
    {
        // TODO: Implement critical() method.
    }

    public function error($message, $context = array())
    {
        // TODO: Implement error() method.
    }

    public function warning($message, $context = array())
    {
        // TODO: Implement warning() method.
    }

    public function notice($message, $context = array())
    {
        // TODO: Implement notice() method.
    }

    public function info($message, $context = array())
    {
        // TODO: Implement info() method.
    }

    public function debug($message, $context = array())
    {
        // TODO: Implement debug() method.
    }
}

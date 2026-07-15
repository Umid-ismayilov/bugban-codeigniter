<?php

namespace Bugban\CodeIgniter;

use Bugban\Sdk\Bugban;

/**
 * Small helper for wiring Bugban into CodeIgniter 3 and 4.
 */
class BugbanCI
{
    /**
     * Initialize the SDK and register global error/exception/shutdown handlers.
     *
     * @return void
     */
    public static function boot(array $config)
    {
        Bugban::init($config);
        Bugban::registerHandlers();
    }

    /**
     * Manually capture a throwable/exception.
     *
     * @param \Throwable|\Exception $e
     * @return void
     */
    public static function capture($e, array $extra = array())
    {
        Bugban::capture($e, $extra);
    }
}

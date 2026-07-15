<?php

namespace Bugban\CodeIgniter\Hooks;

use Bugban\CodeIgniter\BugbanCI;

/**
 * CodeIgniter 3 hook class. Suitable for use from application/hooks/
 * on the `pre_system` hook point.
 *
 * Reads configuration from environment variables:
 *   BUGBAN_API_KEY, BUGBAN_HOST
 */
class BugbanHook
{
    public function __construct()
    {
        $host = getenv('BUGBAN_HOST');

        BugbanCI::boot(array(
            'api_key' => (string) getenv('BUGBAN_API_KEY'),
            'host' => $host ? $host : 'https://bugban.online',
        ));
    }
}

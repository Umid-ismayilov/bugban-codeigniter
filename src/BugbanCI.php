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
        // Metadata for the one-time install ping (SDK handshake).
        if (!isset($config['framework'])) {
            $config['framework'] = 'codeigniter';
        }
        if (!isset($config['framework_version'])) {
            $version = self::frameworkVersion();
            if ($version !== null) {
                $config['framework_version'] = $version;
            }
        }
        if (!isset($config['sdk'])) {
            $config['sdk'] = 'bugban/codeigniter';
        }

        Bugban::init($config);
        Bugban::registerHandlers();
    }

    /**
     * CI3 defines the CI_VERSION constant; CI4 exposes it on the CodeIgniter class.
     *
     * @return string|null
     */
    private static function frameworkVersion()
    {
        try {
            if (defined('CI_VERSION')) {
                return (string) constant('CI_VERSION');
            }
            if (defined('CodeIgniter\CodeIgniter::CI_VERSION')) {
                return (string) constant('CodeIgniter\CodeIgniter::CI_VERSION');
            }
        } catch (\Exception $e) {
            // ignore
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
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

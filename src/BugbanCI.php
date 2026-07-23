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
        self::registerCi4QueryListener();
        self::registerQueryRunner();
    }

    /**
     * Let the Bugban panel re-run one of this app's own captured SELECTs and
     * report the timing. Works on both CI4 (\Config\Database::connect()) and
     * CI3 (the loaded $CI->db). Always inside a rolled-back transaction, and
     * only the row COUNT is returned — never row data.
     *
     * @return void
     */
    private static function registerQueryRunner()
    {
        try {
            // The core SDK may be older than this adapter (stale lock file or a
            // manual libs/ copy loaded first). Never call into it blindly.
            if (!method_exists('\\Bugban\\Sdk\\Bugban', 'setQueryRunner')) {
                return;
            }

            Bugban::setQueryRunner(function ($sql, array $bindings, $returnRows = false) {
                $db = null;

                if (class_exists('\\Config\\Database')) {                 // CI4
                    $db = \Config\Database::connect();
                } elseif (function_exists('get_instance')) {              // CI3
                    $ci = get_instance();
                    $db = (is_object($ci) && isset($ci->db)) ? $ci->db : null;
                }
                if (!is_object($db)) {
                    throw new \Exception('No CodeIgniter database connection is available.');
                }

                $db->transBegin();
                try {
                    $query = $db->query($sql, $bindings);
                    $rows = ($query && method_exists($query, 'getResultArray'))
                        ? $query->getResultArray()          // CI4
                        : (($query && method_exists($query, 'result_array')) ? $query->result_array() : array());

                    if (!is_array($rows)) {
                        return $returnRows ? array() : 0;
                    }

                    return $returnRows ? $rows : count($rows);
                } catch (\Exception $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    throw $e;
                } finally {
                    try {
                        $db->transRollback();
                    } catch (\Exception $e) {
                        // Nothing was written; a failed rollback is not fatal.
                    }
                }
            });
        } catch (\Exception $e) {
            // Monitoring must never break the app.
        } catch (\Throwable $e) {
            // Same for engine errors.
        }
    }

    /**
     * CI4 slow-query capture: listen to the framework's DBQuery event.
     * (CI3 has no query event — see flushCi3Queries() below.) Fully guarded,
     * no-op when capture_queries is disabled or Events are unavailable.
     *
     * @return void
     */
    private static function registerCi4QueryListener()
    {
        try {
            $client = Bugban::client();
            if (!$client || !$client->config()->captureQueries) {
                return;
            }
            if (!class_exists('\CodeIgniter\Events\Events')) {
                return;
            }
            \CodeIgniter\Events\Events::on('DBQuery', function ($query) {
                try {
                    // \CodeIgniter\Database\Query: getQuery() = final SQL,
                    // getDuration() = seconds -> convert to milliseconds.
                    if (is_object($query) && method_exists($query, 'getQuery') && method_exists($query, 'getDuration')) {
                        Bugban::recordQuery(
                            (string) $query->getQuery(),
                            ((float) $query->getDuration(6)) * 1000
                        );
                    }
                } catch (\Exception $e) {
                    // never break the host app
                } catch (\Throwable $e) {
                    // non-fatal
                }
            });
        } catch (\Exception $e) {
            // never break the host app
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    /**
     * CI3 slow-query capture (best effort, manual): CodeIgniter 3 keeps every
     * executed query + its duration on the DB driver when save_queries is
     * enabled (the default). Call this once late in the request, e.g. in a
     * post_system hook or at the end of your controller:
     *
     *     \Bugban\CodeIgniter\BugbanCI::flushCi3Queries();
     *
     * Durations are handed to the SDK, which drops anything faster than the
     * configured slow_query_ms. Fully guarded — never throws.
     *
     * @return void
     */
    public static function flushCi3Queries()
    {
        try {
            if (!function_exists('get_instance')) {
                return;
            }
            $ci = get_instance();
            if (!is_object($ci) || !isset($ci->db) || !is_object($ci->db)) {
                return;
            }
            $db = $ci->db;
            if (empty($db->queries) || !is_array($db->queries)) {
                return;
            }
            $times = (isset($db->query_times) && is_array($db->query_times)) ? $db->query_times : array();
            $driver = (isset($db->dbdriver) && is_string($db->dbdriver)) ? $db->dbdriver : null;
            foreach ($db->queries as $i => $sql) {
                $seconds = isset($times[$i]) ? (float) $times[$i] : 0.0;
                $meta = array();
                if ($driver !== null) {
                    $meta['connection'] = $driver;
                }
                Bugban::recordQuery((string) $sql, $seconds * 1000, $meta);
            }
        } catch (\Exception $e) {
            // never break the host app
        } catch (\Throwable $e) {
            // non-fatal
        }
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

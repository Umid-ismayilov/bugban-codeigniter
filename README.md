# bugban/codeigniter

CodeIgniter adapter for the [Bugban](https://bugban.online) error & monitoring
SDK. Works with **CodeIgniter 3 and CodeIgniter 4**, and even in
composer-free legacy projects. PHP **7.0+**.

## Install (Composer)

```bash
composer require bugban/codeigniter
```

Set your credentials as environment variables (in `.env` or the server env):

```dotenv
BUGBAN_API_KEY=bb_xxxxxxxxxxxxxxxx
BUGBAN_HOST=https://bugban.online
```

---

## CodeIgniter 3

### Option A — hooks (recommended)

1. Enable hooks in `application/config/config.php`:

   ```php
   $config['enable_hooks'] = TRUE;
   ```

2. Register the hook in `application/config/hooks.php` on `pre_system`:

   ```php
   $hook['pre_system'] = array(
       'class'    => 'Bugban\\CodeIgniter\\Hooks\\BugbanHook',
       'function' => '__construct',
       'filename' => '', // loaded via Composer autoloader
       'filepath' => '',
   );
   ```

   The hook reads `BUGBAN_API_KEY` and `BUGBAN_HOST` from the environment and
   boots the SDK (init + register global handlers) automatically.

### Option B — composer-free (legacy)

If you cannot run Composer, require the core SDK's manual autoloader directly
in `index.php` (before CodeIgniter bootstraps):

```php
require __DIR__ . '/path/to/bugban-php-sdk/autoload.php';

\Bugban\Sdk\Bugban::init([
    'api_key' => getenv('BUGBAN_API_KEY') ?: 'bb_xxxxxxxxxxxxxxxx',
    'host'    => 'https://bugban.online',
]);
\Bugban\Sdk\Bugban::registerHandlers();
```

---

## CodeIgniter 4

Register a `pre_system` event in `app/Config/Events.php`:

```php
Events::on('pre_system', static function () {
    \Bugban\CodeIgniter\BugbanCI::boot([
        'api_key' => getenv('BUGBAN_API_KEY'),
        'host'    => getenv('BUGBAN_HOST'),
    ]);
});
```

`BugbanCI::boot()` initializes the SDK and registers global error, exception
and shutdown handlers.

---

## Manual capture

Anywhere in your app:

```php
use Bugban\CodeIgniter\BugbanCI;

try {
    // ...
} catch (\Throwable $e) {
    BugbanCI::capture($e);
}
```

Or via the core facade:

```php
\Bugban\Sdk\Bugban::captureMessage('Payment gateway timeout', 'error');
```

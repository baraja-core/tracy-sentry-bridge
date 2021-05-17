Tracy and Sentry bridge
=======================

The package provides an easy connection between Tracy and Sentry.

How to use
----------

In your project Bootstrap:

```php
public static function boot(): Configurator
{
    $configurator = new Configurator;

    if (\function_exists('\Sentry\init')) {
        \Sentry\init(
            [
                'dsn' => 'https://....',
                'attach_stacktrace' => true,
            ]
        );
    }

    // register here:
    SentryLogger::register();
}
```

The tool automatically loads the original Tracy Logger and registers logging to Sentry. Logging takes place to the Sentry and at the same time to the original log, which does not interrupt the integrity of the data.

If the log write to the Sentry fails, the exception is written to the original Logger.

Tracy and Sentry bridge
=======================

The package provides an easy connection between Tracy and Sentry.

📦 Installation
---------------

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/tracy-sentry-bridge) and
[GitHub](https://github.com/baraja-core/tracy-sentry-bridge).

To install, simply use the command:

```shell
$ composer require baraja-core/tracy-sentry-bridge
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

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

📄 License
-----------

`baraja-core/tracy-sentry-bridge` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/template/blob/master/LICENSE) file for more details.

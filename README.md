# Tracy and Sentry Bridge

The package provides an easy connection between [Tracy](https://tracy.nette.org/) debugger and [Sentry](https://sentry.io/) error tracking service. It allows you to automatically forward all Tracy log entries to Sentry while preserving the original Tracy logging functionality.

## :bulb: Key Principles

- **Seamless Integration**: Works as a drop-in replacement for Tracy's default logger
- **Dual Logging**: All logs are sent to both the original Tracy logger and Sentry simultaneously
- **No Data Loss**: Original Tracy logs remain intact regardless of Sentry status
- **Automatic Severity Mapping**: Tracy log levels are automatically converted to appropriate Sentry severity levels
- **Fault Tolerant**: If Sentry logging fails, errors are captured by the fallback logger
- **Zero Configuration**: Simple one-line registration in your bootstrap

## :building_construction: Architecture

The package implements Tracy's `ILogger` interface and uses the decorator pattern to wrap the original Tracy logger. This ensures that all existing logging functionality continues to work while adding Sentry integration on top.

```
┌─────────────────────────────────────────────────────────────────┐
│                     Your Application                            │
│                           │                                     │
│                     log($value, $level)                         │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   SentryLogger                           │   │
│  │                  (ILogger impl.)                         │   │
│  │                                                          │   │
│  │   ┌──────────────────────────────────────────────────┐  │   │
│  │   │                    log()                          │  │   │
│  │   │                      │                            │  │   │
│  │   │          ┌───────────┴───────────┐                │  │   │
│  │   │          ▼                       ▼                │  │   │
│  │   │   ┌─────────────┐       ┌───────────────┐         │  │   │
│  │   │   │   Fallback  │       │  logToSentry  │         │  │   │
│  │   │   │   Logger    │       │               │         │  │   │
│  │   │   │  (original) │       │ ┌───────────┐ │         │  │   │
│  │   │   └──────┬──────┘       │ │  Severity │ │         │  │   │
│  │   │          │              │ │  Mapping  │ │         │  │   │
│  │   │          ▼              │ └─────┬─────┘ │         │  │   │
│  │   │   ┌─────────────┐       │       ▼       │         │  │   │
│  │   │   │ Tracy Logs  │       │ ┌───────────┐ │         │  │   │
│  │   │   │  (files)    │       │ │  Sentry   │ │         │  │   │
│  │   │   └─────────────┘       │ │   API     │ │         │  │   │
│  │   │                         │ └───────────┘ │         │  │   │
│  │   └──────────────────────────────────────────────────┘  │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

## :gear: Main Components

### SentryLogger

The core class that implements Tracy's `ILogger` interface. It acts as a bridge between Tracy and Sentry.

**Key Features:**

| Feature | Description |
|---------|-------------|
| `register()` | Static method to automatically register the logger with Tracy |
| `log()` | Main logging method that forwards to both Sentry and fallback logger |
| Severity Mapping | Automatic conversion of Tracy levels to Sentry severity |
| Exception Handling | Proper handling of both `Throwable` objects and string messages |

### Severity Level Mapping

The package automatically maps Tracy log levels to Sentry severity:

| Tracy Level | Sentry Severity |
|-------------|-----------------|
| `DEBUG` | `debug` |
| `INFO` | `info` |
| `WARNING` | `warning` |
| `ERROR` | `error` |
| `EXCEPTION` | `error` |
| `CRITICAL` | `fatal` |

Any unrecognized level defaults to `fatal` severity.

## :package: Installation

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/tracy-sentry-bridge) and
[GitHub](https://github.com/baraja-core/tracy-sentry-bridge).

To install, simply use the command:

```shell
$ composer require baraja-core/tracy-sentry-bridge
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

### Requirements

- PHP 8.0 or higher
- Tracy ^2.8 or 3.0-dev
- Sentry SDK ^3.1

## :rocket: Basic Usage

### Quick Start with Nette Framework

In your project's `Bootstrap.php` file, initialize Sentry first and then register the bridge:

```php
use Baraja\TracySentryBridge\SentryLogger;

public static function boot(): Configurator
{
    $configurator = new Configurator;

    // Initialize Sentry first
    if (\function_exists('\Sentry\init')) {
        \Sentry\init([
            'dsn' => 'https://your-sentry-dsn@sentry.io/project-id',
            'attach_stacktrace' => true,
        ]);
    }

    // Register the Tracy-Sentry bridge
    SentryLogger::register();

    return $configurator;
}
```

### Standalone Usage

If you're not using Nette Framework, you can still use the package with standalone Tracy:

```php
use Tracy\Debugger;
use Baraja\TracySentryBridge\SentryLogger;

// Enable Tracy
Debugger::enable();

// Initialize Sentry
\Sentry\init([
    'dsn' => 'https://your-sentry-dsn@sentry.io/project-id',
]);

// Register the bridge
SentryLogger::register();

// Now all Tracy logs will be sent to Sentry
Debugger::log('Something happened', Debugger::WARNING);
```

### Manual Instantiation

For more control, you can manually create the logger instance:

```php
use Tracy\Debugger;
use Baraja\TracySentryBridge\SentryLogger;

$originalLogger = Debugger::getLogger();
$sentryLogger = new SentryLogger($originalLogger);
Debugger::setLogger($sentryLogger);
```

## :shield: Error Handling

The package is designed to be fault-tolerant:

1. **Primary logging always works**: The fallback (original Tracy) logger is called first, ensuring your local logs are always written.

2. **Sentry failures are captured**: If the Sentry logging fails for any reason, the exception is logged to the fallback logger with `CRITICAL` level.

3. **Graceful degradation**: If Sentry SDK is not properly loaded, the package will output a warning message but won't crash your application.

```php
// Example of how errors are handled internally
public function log(mixed $value, mixed $level = self::INFO): void
{
    // Always log to fallback first (guaranteed to work)
    $this->fallback->log($value, $level);

    try {
        // Then attempt Sentry logging
        $this->logToSentry($value, $level);
    } catch (\Throwable $e) {
        // If Sentry fails, log the error locally
        $this->fallback->log($e, ILogger::CRITICAL);
    }
}
```

## :zap: Advanced Configuration

### Sentry Configuration Options

When initializing Sentry, you can customize various options:

```php
\Sentry\init([
    'dsn' => 'https://your-sentry-dsn@sentry.io/project-id',
    'attach_stacktrace' => true,
    'environment' => 'production',
    'release' => 'my-app@1.0.0',
    'sample_rate' => 1.0,
    'traces_sample_rate' => 0.2,
    'send_default_pii' => false,
]);
```

### Conditional Registration

You might want to only enable Sentry logging in production:

```php
if (getenv('APP_ENV') === 'production' && \function_exists('\Sentry\init')) {
    \Sentry\init(['dsn' => getenv('SENTRY_DSN')]);
    SentryLogger::register();
}
```

### Logging Different Types

The bridge handles different value types appropriately:

```php
// Exceptions are captured with full stack trace
try {
    throw new \RuntimeException('Something went wrong');
} catch (\Throwable $e) {
    Debugger::log($e, Debugger::EXCEPTION);
}

// String messages are captured as messages
Debugger::log('User login failed', Debugger::WARNING);

// Arrays and objects are serialized
Debugger::log(['user_id' => 123, 'action' => 'failed_login'], Debugger::INFO);
```

## :test_tube: Development & Testing

The package uses PHPStan for static analysis:

```shell
$ composer phpstan
```

This runs PHPStan at level 8 with strict rules and Nette-specific extensions.

## :busts_in_silhouette: Author

**Jan Barasek** - [https://baraja.cz](https://baraja.cz)

## :handshake: Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## :page_facing_up: License

`baraja-core/tracy-sentry-bridge` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/tracy-sentry-bridge/blob/master/LICENSE) file for more details.

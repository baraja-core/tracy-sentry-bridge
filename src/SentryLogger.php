<?php

declare(strict_types=1);

namespace Baraja\TracySentryBridge;


use function Sentry\captureException;
use function Sentry\captureMessage;
use Sentry\Severity;

use Tracy\Debugger;
use Tracy\ILogger;

final class SentryLogger implements ILogger
{
	public function __construct(
		private ILogger $fallback,
	) {
	}


	public static function register(): void
	{
		$logger = Debugger::getLogger();
		if (!$logger instanceof self) { // init this logger
			Debugger::setLogger(new self($logger));
		}
	}


	public function log(mixed $value, mixed $level = self::INFO): void
	{
		$this->fallback->log($value, $level);
		if (is_string($level) === false) {
			return;
		}
		try {
			$this->logToSentry($value, $level);
		} catch (\Throwable $e) {
			$this->fallback->log($e, ILogger::CRITICAL);
		}
	}


	private function logToSentry(mixed $value, string $level = self::INFO): void
	{
		$severity = $this->getSeverityFromLevel($level);

		if (function_exists('Sentry\captureException') === false) {
			echo 'Sentry already has not loaded.';
			if ($value instanceof \Throwable) {
				echo "\n\n" . $value->getMessage() . "\n";
				echo $value->getTraceAsString();
			}
		} elseif ($value instanceof \Throwable) {
			captureException($value);
		} else {
			captureMessage($value, $severity);
		}
	}


	private function getSeverityFromLevel(string $level): Severity
	{
		$map = [
			self::DEBUG => Severity::DEBUG,
			self::INFO => Severity::INFO,
			self::WARNING => Severity::WARNING,
			self::ERROR => Severity::ERROR,
			self::EXCEPTION => Severity::ERROR,
			self::CRITICAL => Severity::FATAL,
		];

		return new Severity($map[$level] ?? Severity::FATAL);
	}
}

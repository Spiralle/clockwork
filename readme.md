<p align="center">
	<img width="300px" src="https://github.com/itsgoingd/clockwork/raw/master/.github/assets/title.png">
	<img width="100%" src="https://github.com/itsgoingd/clockwork/raw/master/.github/assets/screenshot.png">
</p>

**spiralle/clockwork** is a seamless integration library that connects the **Spiral Framework** with **Clockwork**,
bringing enhanced runtime insights to your Spiral-based PHP applications. This library extends Clockwork’s
powerful monitoring and debugging tools, making it easy to track request data, performance metrics, database queries,
and more, specifically tailored for the Spiral ecosystem. With this bridge, you can efficiently monitor
your HTTP requests, console commands, queue jobs, and tests within the familiar Clockwork interface, empowering
you to optimize and debug your Spiral applications with ease.

# Installation

```bash
composer require spiralle/clockwork
```

# Configuration

## Interceptions

To enable Clockwork interception, you need to add the `ClockworkInterceptor` to your application's bootloaders.
This interceptor will automatically capture and log all HTTP requests, console commands, and queue jobs.
It collects data such as the controller duration and controller/action name, and sends it to Clockwork.

```php

final class AppBootloader extends DomainBootloader
{

	protected const INTERCEPTORS = [ClockworkInterceptor::class];

}
```

## Bootloaders

```php
public function defineBootloaders(): array
{
	return [
		// ...
		// Clockwork Scope - Required
        ClockworkBootloader::class,
		// Displays middlewares in the "middleware" section
		ClockworkMiddlewareBootloader::class => new BootloadConfig(allowEnv: ['APP_ENV' => ['local']]),
		// Displays database queries in the "database" tab
		ClockworkCycleBootloader::class => new BootloadConfig(allowEnv: ['APP_ENV' => ['local']]),
		// Displays cache queries in the "cache" tab
		ClockworkCacheBootloader::class => new BootloadConfig(allowEnv: ['APP_ENV' => ['local']]),
		// Adds the Clockwork Monolog Handler
		ClockworkLoggingBootloader::class => new BootloadConfig(allowEnv: ['APP_ENV' => ['local']]),

		// ...
	];
}
```

## Middlewares

To collect DataSources and common data, you need to add the `ClockworkMiddlewareBootloader` to your application's bootloaders.

```

final class RoutesBootloader extends BaseRoutesBootloader
{

	protected function globalMiddleware(): array
	{
		return [
			// Should be the first middleware - Required
			ClockworkMiddleware::class,
		];
	}

}

```

## Environment

To enable Clockwork, you need to add the following environment variable to your `.env.local` file:
```dotenv
CLOCKWORK_ENABLED=true
MONOLOG_DEFAULT_CHANNEL=clockwork # To log to Clockwork
```

## Chrome Extension

To view the collected data, you need to install the Clockwork Chrome extension. You can find it here:
https://chromewebstore.google.com/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp

## Official Documentation

For more information, please refer to the official Clockwork documentation: https://underground.works/clockwork/

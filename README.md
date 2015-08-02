Laravel Loggly
===============

Loggly logging and error integration for Laravel 5. Based on [laravel-rollbar](https://github.com/jenssegers/laravel-rollbar) by jenssegers.

## This package is still IN DEVELOPMENT.

Installation
------------

Install using composer:

    composer require utkarshx/loggly-laravel

Add the service provider to the `'providers'` array in `config/app.php`:

    'Utkarshx\Loggly\LogglyServiceProvider',

Configuration
-------------

This package supports configuration through the services configuration file located in `app/config/services.php`. All configuration variables will be directly passed to Rollbar:

    'loggly' => [
        'key' => 'your-loggly-token',
        'level' => 'debug',
        'tags' => ['your-tokens]
    ],

The level variable defines the minimum log level at which log messages are sent to Loggly.

Usage
-----

To automatically monitor exceptions, simply use the `Log` facade in your error handler in `app/Exceptions/Handler.php`:

    public function report(Exception $e)
    {
        \Log::error($e);

        return parent::report($e);
    }

For Laravel 4 installations, this is located in `app/start/global.php`:

    App::error(function(Exception $exception, $code)
    {
        Log::error($exception);
    });

Your other log messages will also be sent to Loggly:

    \Log::debug('Here is some debug information');
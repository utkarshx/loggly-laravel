<?php namespace Utkarshx\Loggly;

use InvalidArgumentException;
use Monolog\Handler\LogglyHandler;
use Monolog\Logger as Monolog;
use Illuminate\Support\ServiceProvider;

class LogglyServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $app = $this->app;
        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $logger = \Log::getMonolog();
            $logger->pushHandler($app['loggly.handler']);
        });
    } 

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;

        $this->app['loggly.handler'] = $this->app->share(function ($app) {
            $config = $app['config']->get('services.loggly');

            if (empty($config['key'])) {
                throw new InvalidArgumentException('Loggly key not configured');
            }

            $tags  = $config['tags'];
            $level = $this->parseLevel($app['config']->get('services.loggly.level', 'debug'));

            $handler = new LogglyHandler($config['key'], $level);
            $handler->setTag($tags);

            return $handler;
        });

        // Register the fatal error handler.
        register_shutdown_function(function () use ($app) {
            if (isset($app['loggly.handler'])) {

                $app->make('loggly.handler');

                $last_error = error_get_last();
                if (!is_null($last_error)) {
                    switch ($last_error['type']) {
                        case E_PARSE:
                        case E_ERROR:
                            $app['loggly.handler']->write($last_error);
                            break;
                    }
                }
            }
        });
    }

    public function parseLevel($level)
    {
        switch ($level) {
            case 'debug':
                return Monolog::DEBUG;

            case 'info':
                return Monolog::INFO;

            case 'notice':
                return Monolog::NOTICE;

            case 'warning':
                return Monolog::WARNING;

            case 'error':
                return Monolog::ERROR;

            case 'critical':
                return Monolog::CRITICAL;

            case 'alert':
                return Monolog::ALERT;

            case 'emergency':
                return Monolog::EMERGENCY;

            case 'none':
                return 1000;

            default:
                throw new \InvalidArgumentException("Invalid log level.");
        }
    }

}

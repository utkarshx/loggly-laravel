<?php
namespace Utkarshx/Loggly;

use InvalidArgumentException;
use Monolog\Handler\LogglyHandler;
use Monolog\Logger;
use Monolog\Formatter\LogglyFormatter;
use Illuminate\Support\ServiceProvider;

class LogglyServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    const HOST = 'logs-01.loggly.com';
    const ENDPOINT_SINGLE = 'inputs';
    const ENDPOINT_BATCH = 'bulk';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function ($level, $message, $context) use ($app) {

//            $configlevel = $this->parseLevel($app['config']->get('services.loggly.level', 'debug'));
//                return ($this->parseLevel($level) >= $configlevel);

            $logglyformatter = new LogglyFormatter();
            $formattedrec = $logglyformatter->format(array('data'=> $message,'level'=>$level));
            $this->sendmsg($formattedrec);

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

    public function sendmsg($msgtosend)
    {
        $app = $this->app;

        //Added Config
        $config = $app['config']->get('services.loggly');
        $url = sprintf("https://%s/%s/%s/", self::HOST, self::ENDPOINT_SINGLE, $config['key']);

        //Added Headers
        $headers = array('Content-Type: application/json');

        //tag management
        $tags  = $config['tags'];
        $tag_list = !empty($tags) ? $tags : array();
        $tag_arr = is_array($tag_list) ? $tag_list : array($tag_list);
        if (!empty($tag_arr)) {
            $headers[] = 'X-LOGGLY-TAG: '.implode(',', $tag_arr);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msgtosend);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        \Monolog\Handler\Curl\Util::execute($ch);
    }

    public function parseLevel($level)
    {
        switch ($level) {
            case 'debug':
                return \Monolog\Logger::DEBUG;

            case 'info':
                return \Monolog\Logger::INFO;

            case 'notice':
                return \Monolog\Logger::NOTICE;

            case 'warning':
                return \Monolog\Logger::WARNING;

            case 'error':
                return \Monolog\Logger::ERROR;

            case 'critical':
                return \Monolog\Logger::CRITICAL;

            case 'alert':
                return \Monolog\Logger::ALERT;

            case 'emergency':
                return \Monolog\Logger::EMERGENCY;

            case 'none':
                return 1000;

            default:
                throw new \InvalidArgumentException("Invalid log level.");
        }
    }

}

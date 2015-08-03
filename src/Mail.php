<?php

namespace Hazzard\Mail;

use Swift_Mailer;
use Illuminate\Mail\Mailer;
use Illuminate\Container\Container;
use Illuminate\Mail\TransportManager;

class Mail
{
    /**
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The current globally used instance.
     *
     * @var object
     */
    protected static $instance;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Create a new instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->app = $container;

        $this->register();
    }

    /**
     * Register the mailer.
     *
     * @return void
     */
    protected function register()
    {
        if (!$this->app->bound('view')) {
            $this->app['view'] = new ViewFactory;
        }

        $this->registerSwiftMailer();

        $this->mailer = new Mailer(
            $this->app['view'], $this->app['swift.mailer'], $this->app['events']
        );

        $this->setMailerDependencies();

        $from = $this->app['config']['mail.from'];

        if (is_array($from) && isset($from['address'])) {
            $this->mailer->alwaysFrom($from['address'], $from['name']);
        }

        $to = $this->app['config']['mail.to'];

        if (is_array($to) && isset($to['address'])) {
            $this->mailer->alwaysTo($to['address'], $to['name']);
        }
    }

    /**
     * Set a few dependencies on the mailer instance.
     *
     * @return void
     */
    protected function setMailerDependencies()
    {
        $this->mailer->setContainer($this->app);

        if ($this->app->bound('Psr\Log\LoggerInterface')) {
            $this->mailer->setLogger($this->app->make('Psr\Log\LoggerInterface'));
        }

        if ($this->app->bound('queue')) {
            $this->mailer->setQueue($this->app['queue.connection']);
        }
    }

    /**
     * Register the Swift Mailer instance.
     *
     * @return void
     */
    protected function registerSwiftMailer()
    {
        $this->registerSwiftTransport();

        $this->app['swift.mailer'] = $this->app->share(function ($app) {
            return new Swift_Mailer($app['swift.transport']->driver());
        });
    }

    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        $this->app['swift.transport'] = $this->app->share(function ($app) {
            return new TransportManager($app);
        });
    }

    /**
     * Set view storage path.
     *
     * @param  string $path
     * @return $this
     */
    public function setViewStoragePath($path)
    {
        $this->app['view']->setStoragePath($path);

        return $this;
    }

    /**
     * Make this instance available globally.
     *
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Create a class alias.
     *
     * @param  string $alias
     * @return void
     */
    public function classAlias($alias = 'Mail')
    {
        class_alias(get_class($this), $alias);
    }

    /**
     * Get the validation factory instance.
     *
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * Call mailer methods dynamically.
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->mailer, $method], $arguments);
    }

    /**
     * Call static mailer methods dynamically.
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $mailer = static::$instance->getMailer();

        return call_user_func_array([$mailer, $method], $arguments);
    }
}

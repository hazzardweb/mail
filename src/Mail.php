<?php

namespace Hazzard\Mail;

use ArrayAccess;
use Swift_Mailer;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Hazzard\Mail\Transport\LogTransport;
use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Hazzard\Mail\Transport\MailgunTransport;
use Hazzard\Mail\Transport\MandrillTransport;
use Swift_SendmailTransport as SendmailTransport;

class Mail
{
    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var \Hazzard\Mail\Mailer
     */
    protected $mailer;

    /**
     * @var array
     */
    protected $drivers = array();

    /**
     * Create a new mail instance.
     *
     * @param mixed $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->register();
    }

    /**
     * Get the mailer instance.
     *
     * @return \Hazzard\Mail\Mailer
     */
    public function mailer()
    {
        return $this->mailer;
    }

    /**
     * Get a driver instance.
     *
     * @param  string $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->config('driver');

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createNewDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Register the Mailer instance.
     *
     * @return void
     */
    protected function register()
    {
        $swift = new Swift_Mailer($this->driver());

        $this->mailer = new Mailer($swift);

        $from = $this->config('from');
        if (is_array($from) && isset($from['address'])) {
            $this->mailer->alwaysFrom($from['address'], $from['name']);
        }

        $to = $this->config('to');
        if (is_array($to) && isset($to['address'])) {
            $this->mailer->alwaysTo($to['address'], $to['name']);
        }
    }

    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpDriver()
    {
        $config = $this->config();

        $transport = SmtpTransport::newInstance($config['host'], $config['port']);

        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        if (isset($config['username'])) {
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
        }

        return $transport;
    }

    /**
     * Create an instance of the Sendmail Swift Transport driver.
     *
     * @return \Swift_SendmailTransport
     */
    protected function createSendmailDriver()
    {
        $command = $this->config('sendmail');

        return SendmailTransport::newInstance($command);
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
     *
     * @return \Swift_MailTransport
     */
    protected function createMailDriver()
    {
        return MailTransport::newInstance();
    }

    /**
     * Create an instance of the Mailgun Swift Transport driver.
     *
     * @return \Illuminate\Mail\Transport\MailgunTransport
     */
    protected function createMailgunDriver()
    {
        $config = $this->config('mailgun', array());

        return new MailgunTransport(new Client, $config['secret'], $config['domain']);
    }

    /**
     * Create an instance of the Mandrill Swift Transport driver.
     *
     * @return \Illuminate\Mail\Transport\MandrillTransport
     */
    protected function createMandrillDriver()
    {
        $config = $this->config('mandrill', array());

        return new MandrillTransport(new Client, $config['secret']);
    }

    /**
     * Create an instance of the Log Swift Transport driver.
     *
     * @return \Hazzard\Mail\Transport\LogTransport
     */
    protected function createLogDriver()
    {
        return new LogTransport($this->config('log'));
    }

    /**
     * Create a new driver instance.
     *
     * @param  string $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createNewDriver($driver)
    {
        $method = 'create'.ucfirst($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function config($key = null, $default = null)
    {
        if ($this->config instanceof ArrayAccess) {
            if (is_null($key)) {
                return $this->config['mail'];
            }

            return isset($this->config["mail.$key"]) ? $this->config["mail.$key"] : $default;
        }

        if (is_null($key)) {
            return $this->config;
        }

        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}

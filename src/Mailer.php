<?php

namespace Hazzard\Mail;

use Closure;
use Swift_Mailer;
use Swift_Message;
use InvalidArgumentException;

class Mailer
{
    /**
     * The Swift Mailer instance.
     *
     * @var \Swift_Mailer
     */
    protected $swift;

    /**
     * The global from address and name.
     *
     * @var array
     */
    protected $from;

    /**
     * Array of failed recipients.
     *
     * @var array
     */
    protected $failedRecipients = array();

    /**
     * Array of parsed views containing html and text view name.
     *
     * @var array
     */
    protected $parsedViews = array();

    /**
     * Create a new Mailer instance.
     *
     * @param  \Swift_Mailer $swift
     * @return void
     */
    public function __construct(Swift_Mailer $swift)
    {
        $this->swift = $swift;
    }

    /**
     * Set the global from address and name.
     *
     * @param  string $address
     * @param  string|null $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->from = compact('address', 'name');
    }

    /**
     * Set the global to address and name.
     *
     * @param  string $address
     * @param  string|null $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->to = compact('address', 'name');
    }

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string $text
     * @param  mixed  $callback
     * @return int
     */
    public function raw($text, $callback)
    {
        return $this->send(array('raw' => $text), array(), $callback);
    }

    /**
     * Send a new message when only a plain part.
     *
     * @param  string $view
     * @param  array  $data
     * @param  mixed  $callback
     * @return int
     */
    public function plain($view, array $data, $callback)
    {
        return $this->send(array('text' => $view), $data, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array $view
     * @param  array $data
     * @param  mixed $callback
     * @return mixed
     */
    public function send($view, array $data, $callback)
    {
        $this->forceReconnection();

        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        $this->addContent($message, $view, $plain, $raw, $data);

        call_user_func($callback, $message);

        if (isset($this->to['address'])) {
            $message->to($this->to['address'], $this->to['name'], true);
        }

        $message = $message->getSwiftMessage();

        return $this->sendSwiftMessage($message);
    }

    /**
     * Force the transport to re-connect.
     *
     * This will prevent errors in daemon queue situations.
     *
     * @return void
     */
    protected function forceReconnection()
    {
        $this->getSwiftMailer()->getTransport()->stop();
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Hazzard\Mail\Message $message
     * @param  string $view
     * @param  string $plain
     * @param  string $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            $message->setBody($this->getView($view, $data), 'text/html');
        }

        if (isset($plain)) {
            $message->addPart($this->getView($plain, $data), 'text/plain');
        }

        if (isset($raw)) {
            $message->addPart($raw, 'text/plain');
        }
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return array($view, null, null);
        }

        if (is_array($view) && isset($view[0])) {
            return array($view[0], $view[1], null);
        }

        if (is_array($view)) {
            return array(
                isset($view['html']) ? $view['html'] : null,
                isset($view['text']) ? $view['text'] : null,
                isset($view['raw']) ? $view['raw'] : null,
            );
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Send a Swift Message instance.
     *
     * @param  \Swift_Message $message
     * @return void
     */
    protected function sendSwiftMessage($message)
    {
        return $this->swift->send($message, $this->failedRecipients);
    }

    /**
     * Create a new message instance.
     *
     * @return \Hazzard\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message(new Swift_Message);

        if (!empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        return $message;
    }

    /**
     * Render the given view.
     *
     * @param  string $view
     * @param  array  $data
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getView($view, $data)
    {
        if (!file_exists($view)) {
            throw new InvalidArgumentException("View [$view] not found.");
        }

        ob_start();

        extract($data);

        include $view;

        $contents = ob_get_contents();

        if (ob_get_contents()) {
            ob_end_clean();
        }

        return $contents;
    }

    /**
     * Get the Swift Mailer instance.
     *
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }

    /**
     * Get the array of failed recipients.
     *
     * @return array
     */
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Set the Swift Mailer instance.
     *
     * @param  \Swift_Mailer $swift
     * @return void
     */
    public function setSwiftMailer($swift)
    {
        $this->swift = $swift;
    }
}

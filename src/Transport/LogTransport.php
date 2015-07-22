<?php

namespace Hazzard\Mail\Transport;

use Swift_Transport;
use Swift_Mime_Message;
use Swift_Mime_MimeEntity;
use Swift_Events_SendEvent;
use Swift_Events_EventListener;

class LogTransport extends Transport implements Swift_Transport
{
    /**
     * The log file.
     *
     * @var string
     */
    protected $log;

    /**
     * Create a new log transport instance.
     *
     * @param  string $log
     * @return void
     */
    public function __construct($log)
    {
        $this->log = $log;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        file_put_contents($this->log, $this->getMimeEntityString($message).PHP_EOL.PHP_EOL, FILE_APPEND);
    }

    /**
     * Get a loggable string out of a Swiftmailer entity.
     *
     * @param  \Swift_Mime_MimeEntity $entity
     * @return string
     */
    protected function getMimeEntityString(Swift_Mime_MimeEntity $entity)
    {
        $string = (string) $entity->getHeaders().PHP_EOL.$entity->getBody();

        foreach ($entity->getChildren() as $children) {
            $string .= PHP_EOL.PHP_EOL.$this->getMimeEntityString($children);
        }

        return $string;
    }
}

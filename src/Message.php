<?php

namespace SimplePMS;

/**
 * Class Message
 * 
 * @package SimplePMS
 */
class Message
{
    const DEFAULT_TIMEOUT = 30;

    /**
     * @var int Queue ID.
     */
    protected $id;

    /**
     * @var Queue instance for this message.
     */
    protected $queue;

    /**
     * @var mixed Message content.
     */
    protected $content;

    /**
     * @var string Content checksum.
     */
    protected $checksum;

    /**
     * @var int Micro-timestamp indicating when the message has been handled.
     */
    protected $handled;

    /**
     * @var int Number of microseconds before a message is considered stuck after being handled.
     */
    protected $timeout;

    /**
     * @var int Micro-timestamp indicating message creation.
     */
    protected $created;

    /**
     * @var string Message log.
     */
    protected $log;

    /**
     * Message constructor.
     *
     * @param Queue $queue       Queue object rattached to the message.
     * @param array $messageData Array containing all message data.
     */
    public function __construct(Queue $queue, $messageData)
    {
        $this->queue = $queue;
        $this->id = isset($messageData['id_message']) ? (int)$messageData['id_message'] : null;
        $this->checksum = isset($messageData['checksum']) ? $messageData['checksum'] : null;
        $this->handled = isset($messageData['handled']) ? $messageData['handled'] : null;
        $this->timeout = isset($messageData['timeout']) ? (int) $messageData['timeout'] : null;
        $this->created = isset($messageData['created']) ? $messageData['created'] : null;
        $this->log = isset($messageData['log']) ? $messageData['log'] : null;

        if(isset($messageData['content'])) {
            $this->content = unserialize(base64_decode($messageData['content']));
            $this->validate($messageData['content']);
        }
    }

    /**
     * Make sure the message content has been successfully saved.
     *
     * @param string $base64Content Base64 encoded content.
     *
     * @throws \Exception Checksum doesn't match the content.
     */
    public function validate($base64Content)
    {
        if ($this->checksum !== SimplePMS::generateChecksum($base64Content)) {
            throw new \Exception('Message #'. $this->id .': could not validate checksum against content');
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * @return int
     */
    public function getHandled()
    {
        return $this->handled;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }
}
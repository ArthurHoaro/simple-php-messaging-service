<?php

namespace SimplePMS;

/**
 * Class Queue
 * 
 * @package SimplePMS
 */
class Queue
{
    /**
     * @var string Default queue name.
     */
    const DEFAULT_QUEUE = '_main_';

    /**
     * @var int Queue ID.
     */
    protected $id;

    /**
     * @var string Queue name.
     */
    protected $name;

    /**
     * @var int Micro-timestamp indicating queue creation date.
     */
    protected $created;
    
    /**
     * Queue constructor.
     *
     * @param int    $id      Queue ID.
     * @param string $name    Queue name.
     * @param int    $created Queue creation date (microtime as integer).
     */
    public function __construct($id, $name = self::DEFAULT_QUEUE, $created = null)
    {
        $this->id = $id;
        $this->name = $name;
        if ($created) {
            $this->created = $created;
        }
    }

    /**
     * Return queue id.
     *
     * @return int Queue ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return queue name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Prints class
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf('Queue #%s: %s '. PHP_EOL, $this->getId(), $this->getName());
    }
}
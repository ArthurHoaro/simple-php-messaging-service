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
     * Queue constructor.
     *
     * @param int    $id   Queue ID.
     * @param string $name Queue name.
     */
    public function __construct($id, $name = self::DEFAULT_QUEUE)
    {
        $this->id = $id;
        $this->name = $name;
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
     * Prints class
     * 
     * @return string
     */
    public function __toString()
    {
        return sprintf('Queue #%s: %s '. PHP_EOL, $this->getId(), $this->getName());
    }
}
<?php

namespace SimplePMS;

/**
 * Class SimplePMS
 *
 * Simple PHP Message Service. Look at the README for more information.
 *
 * @package SimplePMS
 */
class SimplePMS
{
    /**
     * @var \PDO instance.
     */
    protected $pdo;

    /**
     * Queue table name.
     */
    const TABLE_QUEUE   = 'spms_queue';

    /**
     * Message table name.
     */
    const TABLE_MESSAGE = 'spms_message';

    /**
     * Add new message to the given queue.
     *
     * @param mixed  $message   Message object to store, will be serialized.
     * @param string $queueName Queue name.
     * @param int    $timeout   Timeout before the message is considered lost after being received (in seconds).
     *
     * @return bool true if the message has been successfully added to the queue.
     */
    public function send($message, $queueName = Queue::DEFAULT_QUEUE, $timeout = Message::DEFAULT_TIMEOUT)
    {
        SimplePMSLogger::log($this->getDb(), SimplePMSAction::NEW_MESSAGE, [$queueName, $timeout]);
        $queue = $this->getQueue($queueName);
        $content = base64_encode(serialize($message));
        $checksum = self::generateChecksum($content);

        $sql = 'INSERT INTO ' . self::TABLE_MESSAGE . '
            (id_queue, content, created, timeout, checksum)
            VALUES
            (:id_queue, :content, :created, :timeout, :checksum)
            ';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':id_queue', $queue->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':checksum', $checksum);
        $stmt->bindValue(':created', self::microtime(), \PDO::PARAM_INT);
        $stmt->bindValue(':timeout', $timeout * 10000, \PDO::PARAM_INT);

        if (($insert = $stmt->execute())) {
            SimplePMSLogger::log($this->getDb(), SimplePMSAction::NEW_MESSAGE_SUCCESS, [$queueName, $this->getDb()->lastInsertId()]);
        } else {
            SimplePMSLogger::log($this->getDb(), SimplePMSAction::NEW_MESSAGE_ERROR, [$queueName]);
        }
        return $insert;
    }

    /**
     * Receive the first message pending in the queue, by creation date (FIFO).
     *
     * @param string $queueName Queue name.
     * @param bool   $stucked   Also receive message if its timeout is expired (default false).
     *
     * @return Message|bool The first message found, or false if no message pending.
     *
     * @throws \Exception An error occurred, the database hasn't been updated.
     */
    public function receive($queueName = Queue::DEFAULT_QUEUE, $stucked = false) {
        $message = $this->receiveMessages($queueName, 1, $stucked);
        if(empty($message) || ! isset($message[0])) {
            return false;
        }
        return $message[0];
    }

    /**
     * Receive the $nb first messages pending in the queue, by creation date (FIFO).
     *
     * @param string   $queueName Queue name.
     * @param int|bool $nb        Number of message to retrieve (default false, all of them are returned).
     * @param bool     $stucked   Also receive messages if their timeout is expired (default false).
     *
     * @return Message[] List of pending message, or an empty array if none is found.
     *
     * @throws \Exception An error occurred, the database hasn't been updated.
     */
    public function receiveMessages($queueName = Queue::DEFAULT_QUEUE, $nb = false, $stucked = false) {
        if (empty($queueName) || ! is_string($queueName)) {
            throw new \InvalidArgumentException('$queueName value should be a string if defined');
        }

        if ($nb !== false && (! is_int($nb) || $nb < 1)) {
            throw new \InvalidArgumentException('$nb value should be an integer if defined');
        }

        $messages = [];
        $microtime = self::microtime();
        $db = $this->getDb();
        $queue = $this->getQueue($queueName);

        // start transaction handling
        try {
            $db->beginTransaction();

            $sql = 'SELECT *
                    FROM '. self::TABLE_MESSAGE . '
                    WHERE id_queue = :id_queue
                    AND (handled IS NULL';
            $sql .= $stucked ? ' OR timeout + handled < '. $microtime : '';
            $sql .= ') ORDER BY created ASC';
            $sql .= $nb !== false ? ' LIMIT ' . $nb : '';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id_queue', $queue->getId(), \PDO::PARAM_INT);
            $stmt->execute();

            $sql = 'UPDATE ' . self::TABLE_MESSAGE . '
                        SET
                            handled = :handled
                        WHERE
                            id_message = :id';
            $stu = $db->prepare($sql);

            foreach ($stmt->fetchAll() as $data) {
                $stu->bindParam(':handled', $microtime);
                $stu->bindParam(':id', $data['id_message'], \PDO::PARAM_INT);
                if ($stu->execute()) {
                    SimplePMSLogger::log(
                        $db,
                        SimplePMSAction::MESSAGE_HANDLED,
                        [$data['id_message'], $queue->getName(), $data['timeout']]
                    );
                    $data['handled'] = $microtime;
                    $messages[] = new Message($queue, $data);
                }
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return $messages;
    }

    /**
     * Retrieve stuck messages.
     * Stuck messages are message which have been handled, but their timeout since then is expired.
     *
     * @param String   $queueName Queue name.
     * @param int|bool $nb        Number of message to retrieve, or false to retrieve all of them.
     *
     * @return Message[] List of stuck messages.
     *
     * @throws \Exception
     */
    public function getStuckMessages($queueName = Queue::DEFAULT_QUEUE, $nb = false)
    {
        $db = $this->getDb();
        $microtime = self::microtime();
        $queue = $this->getQueue($queueName);

        $sql = 'SELECT *
                    FROM '. self::TABLE_MESSAGE . '
                    WHERE id_queue = :id_queue
                    AND timeout + handled < '. $microtime .'
                    ORDER BY created ASC';
        $sql .= $nb !== false ? ' LIMIT ' . $nb : '';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id_queue', $queue->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $messages = [];
        foreach ($stmt->fetchAll() as $data) {
            $messages[] = new Message($queue, $data);
        }
        return $messages;
    }

    /**
     * Return new queue instance
     *
     * @param string $queueName Queue name.
     *
     * @return Queue
     *
     * @throws \Exception
     */
    public function getQueue($queueName = Queue::DEFAULT_QUEUE)
    {
        if (! is_string($queueName)) {
            throw new \InvalidArgumentException('$queueName is not a string');
        }

        $sql = 'SELECT * FROM ' . self::TABLE_QUEUE . ' WHERE name = :name';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':name', $queueName);
        $stmt->execute();
        if (!empty($queue = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            return new Queue($queue['id_queue'], $queue['name'], $queue['created']);
        }
        return $this->createQueue($queueName);
    }

    /**
     * Returns queue id or bool false
     *
     * @param string $queueName Queue name. Optional, main queue as default.
     *
     * @return int queue ID or false it does not exist.
     */
    public function getQueueId($queueName = Queue::DEFAULT_QUEUE)
    {
        $sql = 'SELECT id_queue FROM ' . self::TABLE_QUEUE . ' WHERE name = :name';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':name', $queueName);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Creates new Queue.
     *
     * @param string $queueName The name of the queue to create.
     *
     * @return Queue The Queue created.
     */
    protected function createQueue($queueName)
    {
        $microtime = self::microtime();
        $sql = 'INSERT INTO ' . self::TABLE_QUEUE . '
            (name, created)
            VALUES
            (:queue_name, :created)
            ';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':queue_name', $queueName);
        $stmt->bindValue(':created', $microtime);
        if ($stmt->execute()) {
            $id = $this->getDb()->lastInsertId(self::TABLE_QUEUE . '_id_queue_seq');
            SimplePMSLogger::log($this->getDb(), SimplePMSAction::NEW_QUEUE, [$queueName]);
            return new Queue($id, $queueName, $microtime);
        }
        return false;
    }

    /**
     * Delete all pending messages in queue.
     *
     * @param string $queueName Queue name.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function clearQueue($queueName = Queue::DEFAULT_QUEUE)
    {
        $qid = $this->getQueueId($queueName);
        $sth = $this->getDb()->prepare('DELETE FROM ' . self::TABLE_MESSAGE . ' WHERE id_queue = :id');
        $sth->bindValue(':id', $qid, \PDO::PARAM_INT);
        $sth->execute();
        $sth = $this->getDb()->prepare('DELETE FROM ' . self::TABLE_QUEUE .' WHERE id_queue = :id');
        $sth->bindValue(':id', $qid, \PDO::PARAM_INT);
        if (($delete = $sth->execute())) {
            SimplePMSLogger::log($this->getDb(), SimplePMSAction::CLEAR_QUEUE, [$queueName]);
        }
        return $delete;
    }

    /**
     * Deletes a message.
     * Should be called everytime a message has been handled by a worker.
     *
     * @param Message $message Message to delete.
     *
     * @return bool True if the message has been deleted, false otherwise.
     */
    public function deleteMessage(Message $message)
    {
        $mid = $message->getId();
        if (empty($mid) || ! is_int($mid)) {
            throw new \InvalidArgumentException('Could not delete this message because its ID is empty or invalid');
        }

        return $this->deleteMessageById($message->getId());
    }

    /**
     * Deletes a message by its ID.
     *
     * @param integer $mid message ID.
     *
     * @return bool True if the message has been deleted, false otherwise.
     */
    protected function deleteMessageById($mid)
    {
        $sth = $this->getDb()->prepare('DELETE FROM ' . self::TABLE_MESSAGE . ' WHERE id_message = :id');
        $sth->bindValue(':id', $mid, \PDO::PARAM_INT);
        if (($delete = $sth->execute())) {
            SimplePMSLogger::log($this->getDb(), SimplePMSAction::MESSAGE_DELETE, [$mid]);
        }
        return $delete;
    }

    /**
     * Log information related to a message in the database.
     *
     * @param Message $message Message containing the log.
     * @param string  $log     The log to store.
     *
     * @return bool PDO execution status.
     */
    public function log(Message $message, $log)
    {
        $mid = $message->getId();
        if (empty($mid) || ! is_int($mid)) {
            throw new \InvalidArgumentException('Could log into this message because its ID is empty or invalid');
        }
        $sql = 'UPDATE '. self::TABLE_MESSAGE .' SET log = :log WHERE id_message = :id';
        $sth = $this->getDb()->prepare($sql);
        $sth->bindValue(':id', $mid, \PDO::PARAM_INT);
        $sth->bindValue(':log', $log);
        return $sth->execute();
    }

    /**
     * Retrieve a single message by its ID.
     * Note: you shouldn't normally access message directly, use queue receiving functions instead.
     *
     * @param int $messageId Message ID.
     *
     * @return bool|Message Found Message, or false if it doesn't exists.
     *
     * @throws \Exception
     */
    public function getMessage($messageId)
    {
        $sql = 'SELECT m.*, q.id_queue as qid, q.name as qname, q.created as qcreated
                FROM '. self::TABLE_MESSAGE .' m, '. self::TABLE_QUEUE.' q  
                WHERE id_message = :id AND q.id_queue = m.id_queue';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindParam(':id', $messageId, \PDO::PARAM_INT);
        $stmt->execute();
        $message = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!empty($message)) {
            $queue = new Queue($message['qid'], $message['qname'], $message['qcreated']);
            return new Message($queue, $message);
        }
        return false;
    }

    /**
     * Retrieve all existing queues.
     *
     * @return Queue[] Array of existing queue.
     *
     * @throws \Exception
     */
    public function getQueues()
    {
        $sql = 'SELECT * FROM '. self::TABLE_QUEUE .' ORDER BY created ASC';
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        $queues = [];
        $i = 0;
        foreach ($stmt->fetchAll() as $queue) {
            $queues[] = new Queue($queue['id_queue'], $queue['name'], $queue['created']);
        }
        return $queues;
    }

    /**
     * Configure database
     *
     * @throws \Exception
     * @return \PDO
     */
    public function getDb()
    {
        if(!is_object($this->pdo)) {
            throw new \Exception('Database connection was not set');
        }
        return $this->pdo;
    }

    /**
     * Assign $pdo.
     *
     * @param \PDO $pdo PDO instance, or null to close it.
     */
    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }


    /**
     * Generate a checksum based on given encoded content.s
     *
     * @param string $content Serialized base64 content.
     *
     * @return string Checksum hash.
     */
    public static function generateChecksum($content)
    {
        return md5($content);
    }

    /**
     * Returns the microtime() as an integer.
     * This avoid issues with approximation in floats.
     *
     * @return int microtime.
     */
    public static function microtime()
    {
        return (int) (microtime(true) * 10000);
    }
}
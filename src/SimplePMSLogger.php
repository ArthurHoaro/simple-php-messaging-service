<?php

namespace SimplePMS;

/**
 * Class SimplePMSLogger
 * Helper class which logs actions in the database.
 *
 * @package SimplePMS
 */
class SimplePMSLogger
{
    const TABLE_LOG = 'spms_log';

    /**
     * Log an action in the database.
     *
     * @param \PDO  $pdo    PDO instance.
     * @param int   $action Action ID in SimplePMSAction enum class.
     * @param array $param  Array of parameters for the log message.
     *
     * @return bool PDO execute response to the insert.
     */
    public static function log(\PDO $pdo, $action, $param)
    {
        $message = self::getMessage($action, $param);
        $sql = 'INSERT INTO '. self::TABLE_LOG .' (created, message) 
                VALUES (:created, :message)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':created', SimplePMS::microtime());
        $stmt->bindValue(':message', $message);
        return $stmt->execute();
    }

    /**
     * Generate the log message according to the given action.
     *
     * @param int   $action Action ID in SimplePMSAction enum class.
     * @param array $param  Array of parameters to build the message.
     *
     * @return string Log message.
     */
    protected static function getMessage($action, $param)
    {
        switch ($action) {
            case SimplePMSAction::NEW_MESSAGE:
                return sprintf('A new message has been received in the queue "%s" (timeout %ss)', $param[0], $param[1]);
            case SimplePMSAction::NEW_MESSAGE_SUCCESS:
                return sprintf(
                    'The new message has been successfully added to the queue "%s": ID #%s',
                    $param[0],
                    $param[1]
                );
            case SimplePMSAction::NEW_MESSAGE_ERROR:
                return sprintf('The new message could not be saved in the queue "$s". It is lost.', $param[0]);
            case SimplePMSAction::MESSAGE_HANDLED:
                return sprintf(
                    'The message #%s from queue "%s" has been handled. Its timeout is %s seconds',
                    $param[0],
                    $param[1],
                    $param[2]
                );
            case SimplePMSAction::MESSAGE_DELETE:
                return sprintf('The message #%s has been deleted.', $param[0]);
            case SimplePMSAction::NEW_QUEUE:
                return sprintf('The queue "%s" has been created', $param[0]);
            case SimplePMSAction::CLEAR_QUEUE:
                return sprintf('The queue "%s" and its messages have been deleted.', $param[0]);
            default:
                return sprintf('Unknown action: %s', $param[0]);
        }
    }
}
<?php

namespace SimplePMS;

/**
 * Class SimplePMSAction
 * List of loggable actions.
 *
 * @package SimplePMS
 */
class SimplePMSAction
{
    /** A new message has been received in a queue. */
    const NEW_MESSAGE = 1;
    /** The new message has been successfully added in the queue. */
    const NEW_MESSAGE_SUCCESS = 2;
    /** The new message couldn't be saved in the queue. */
    const NEW_MESSAGE_ERROR = 3;
    /** A worker has received a pending message. */
    const MESSAGE_HANDLED = 4;
    /** A message is being deleted. */
    const MESSAGE_DELETE = 5;

    /** A new queue has been created. */
    const NEW_QUEUE = 6;
    /** A queue and its messages has been deleted. */
    const CLEAR_QUEUE = 7;
}
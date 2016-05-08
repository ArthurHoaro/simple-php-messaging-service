<?php

namespace SimplePMS;

/**
 * Class SimplePMSTest
 * Test SimplePMS class.
 *
 * @package SimplePMS
 */
class SimplePMSTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimplePMS instance.
     */
    private $spms;

    /**
     * Run before every test.
     */
    public function setUp()
    {
        $dbh = getConnection();
        $this->spms = new SimplePMS();
        $this->spms->setPdo($dbh);
    }

    /**
     * Run after every test.
     */
    public function tearDown()
    {
        // Supposed to close the PDO connection.
        $this->spms->setPdo(null);
    }

    /**
     * No message found, so empty array.
     */
    public function testReceiveEmptyQueue()
    {
        $queue = $this->spms->getQueue('test');
        $this->assertEmpty($this->spms->receive($queue->getName()));
        $this->assertEmpty($this->spms->receive('nope'));
    }

    /**
     * Receive invalid queueName parameter.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp #.*\$queueName.*#
     */
    public function testReceiveInvalidQueueName()
    {
        $this->spms->receive([]);
    }

    /**
     * Receive invalid queueName parameter.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp #.*\$nb.*#
     */
    public function testReceiveInvalidNb()
    {
        $this->spms->receiveMessages('test', []);
    }

    /**
     * Simple send and receive workflow with a string message.
     */
    public function testSimpleSendReceiveString()
    {
        $message = 'Hi!';
        $this->spms->send($message);
        $receiveMsg = $this->spms->receive();
        $this->assertEquals($message, $receiveMsg->getContent());
    }

    /**
     * Simple send and receive workflow with an integer message.
     */
    public function testSimpleSendReceiveInteger()
    {
        $message = 12;
        $this->spms->send($message);
        $receiveMsg = $this->spms->receive();
        $this->assertEquals($message, $receiveMsg->getContent());
    }

    /**
     * Simple send and receive workflow with a boolean message.
     */
    public function testSimpleSendReceiveBool()
    {
        $message = false;
        $this->spms->send($message);
        $receiveMsg = $this->spms->receive();
        $this->assertFalse($receiveMsg->getContent());
    }

    /**
     * Simple send and receive workflow with a nested array message.
     */
    public function testSimpleSendReceiveArray()
    {
        // Nested array
        $message = ['stuff' => ['foo' => ['bar']]];
        $this->spms->send($message);
        $receiveMsg = $this->spms->receive();
        $this->assertEquals($message, $receiveMsg->getContent());
    }

    /**
     * Simple send and receive workflow with an object message.
     */
    public function testSimpleSendReceiveObject()
    {
        // Object
        $message = new Foo();
        $this->spms->send($message);
        $receiveMsg = $this->spms->receive();
        /** @var Foo $receiveMsg */
        $messageObject = $receiveMsg->getContent();
        $this->assertEquals($message, $messageObject);
        $this->assertTrue($messageObject->helloWorld());
    }

    /**
     * Using queue names.
     */
    public function testSendReceiveQueueName()
    {
        $q1 = 'q1';
        $q2 = 'q2';
        $message1 = 'Hi!';
        $this->spms->send($message1, $q2);
        $message2 = 'msg2';
        $this->spms->send($message2);
        $message3 = 'msg3';
        $this->spms->send($message3, $q1);

        $receiveMsg = $this->spms->receive();
        $this->assertEquals($message2, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($q2);
        $this->assertEquals($message1, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($q1);
        $this->assertEquals($message3, $receiveMsg->getContent());
    }

    /**
     * Multiple messages in the same queue.
     */
    public function testMultipleMessages()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $this->spms->send($message1, $queue);
        $message2 = 'msg2';
        $this->spms->send($message2, $queue);
        $message3 = 'msg3';
        $this->spms->send($message3, $queue);

        $receiveMsg = $this->spms->receive($queue);
        $this->assertEquals($message1, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($queue);
        $this->assertEquals($message2, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($queue);
        $this->assertEquals($message3, $receiveMsg->getContent());
    }

    /**
     * Multiple messages in the same queue, received all at once.
     */
    public function testMultipleMessagesWithMultipleReceive()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $this->spms->send($message1, $queue);
        $message2 = 'msg2';
        $this->spms->send($message2, $queue);
        $message3 = 'msg3';
        $this->spms->send($message3, $queue);

        $receiveMsgs = $this->spms->receiveMessages($queue);
        $this->assertEquals(3, count($receiveMsgs));
        $this->assertEquals($message1, $receiveMsgs[0]->getContent());
        $this->assertEquals($message2, $receiveMsgs[1]->getContent());
        $this->assertEquals($message3, $receiveMsgs[2]->getContent());
    }

    /**
     * Retrieve a stuck message (based on timeout).
     */
    public function testTimeout()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $this->spms->send($message1, $queue, 10);
        $message2 = 'msg2';
        $this->spms->send($message2, $queue, 0);
        $receiveMsg = $this->spms->receive($queue);
        $this->assertEquals($message1, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($queue);
        $this->assertEquals($message2, $receiveMsg->getContent());
        $receiveMsg = $this->spms->receive($queue, true);
        $this->assertEquals($message2, $receiveMsg->getContent());
    }

    public function testDeleteMessage()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $this->spms->send($message1, $queue, 0);
        $msg = $this->spms->receive($queue);
        $this->spms->deleteMessage($msg);
        $this->assertFalse($this->spms->receive($queue));
    }

    /**
     * Test clear queue.
     */
    public function testClearQueue()
    {
        $message1 = 'Hi!';
        $this->spms->send($message1);
        $this->spms->clearQueue();
        $this->assertEmpty($this->spms->receive());
    }

    /**
     * Call the manager without setting PDO instance first.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Database connection was not set
     */
    public function testUnsetManager()
    {
        $spms = new SimplePMS();
        $spms->receive();
    }

    /**
     * Log something into a message.
     */
    public function testLog()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $log = 'Error 545 occured.';
        $this->spms->send($message1, $queue, 0);
        $msg = $this->spms->receive($queue);
        $this->spms->log($msg, $log);
        $msg = $this->spms->receive($queue, true);
        $this->assertEquals($log, $msg->getLog());
    }

    /**
     * Retrieve a list of existing queues.
     */
    public function testGetQueues()
    {
        $queue1 = 'queue1';
        $queue2 = 'queue2';
        $qObj1 = $this->spms->getQueue($queue1);
        $qObj2 = $this->spms->getQueue($queue2);
        $queues = $this->spms->getQueues();
        $this->assertEquals($qObj1, $queues[0]);
        $this->assertEquals($qObj2, $queues[1]);
    }

    /**
     * Retrieve a message in the database by its ID.
     */
    public function testGetMessage()
    {
        $message = 'Hi!';
        $this->spms->send($message);
        $receivedMsg = $this->spms->receive();
        $retrievedMsg = $this->spms->getMessage($receivedMsg->getId());
        $this->assertEquals($receivedMsg, $retrievedMsg);
    }

    /**
     * Retrieve a non existent message in the database by its ID.
     */
    public function testGetNonExistentMessage()
    {
        $this->assertFalse($this->spms->getMessage(42));
    }

    /**
     * Retrieve a list of stuck messages.
     */
    public function testGetStuckMessages()
    {
        $queue = 'queue';
        $message1 = 'Hi!';
        $message2 = 'Hi2!';
        $this->spms->send($message1, $queue, 0);
        $this->spms->send($message2, $queue, 0);
        $rcvMsg1 = $this->spms->receive($queue);
        $rcvMsg2 = $this->spms->receive($queue);
        $stuckMsg = $this->spms->getStuckMessages($queue);
        $this->assertEquals(2, count($stuckMsg));
        $this->assertEquals($rcvMsg1, $stuckMsg[0]);
        $this->assertEquals($rcvMsg2, $stuckMsg[1]);
    }
}

class Foo {
    public function helloWorld() {
        return true;
    }
}

<?php

namespace SimplePMS;

/**
 * Class MessageTest
 * Test Message class.
 *
 * @package SimplePMS
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /could not validate checksum against content/
     */
    public function testInvalidChecksum()
    {
        $queue = new Queue(1);
        $msgData = ['content' => base64_encode(serialize(123)), 'checksum' => 'nope'];
        new Message($queue, $msgData);
    }
}
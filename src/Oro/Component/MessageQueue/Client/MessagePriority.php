<?php
namespace Oro\Component\MessageQueue\Client;

class MessagePriority
{
    const VERY_LOW = 'oro.message_queue.client.very_low_message_priority';
    const LOW = 'oro.message_queue.client.low_message_priority';
    const NORMAL = 'oro.message_queue.client.normal_message_priority';
    const HIGH = 'oro.message_queue.client.high_message_priority';
    const VERY_HIGH = 'oro.message_queue.client.very_high_message_priority';
}

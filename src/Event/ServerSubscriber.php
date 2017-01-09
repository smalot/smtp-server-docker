<?php

namespace App\Event;

use PhpMimeMailParser\Parser;
use Smalot\Smtp\Server\Event\MessageReceivedEvent;
use Smalot\Smtp\Server\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ServerSubscriber
 * @package App\Event
 */
class ServerSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
          Events::MESSAGE_RECEIVED => 'onMessageReceived',
        ];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        $parser = new Parser();
        $parser->setText($event->getMessage());

        var_dump($parser->getHeaders());
        var_dump($parser->getMessageBody());
        var_dump($parser->getMessageBody('html'));

        var_dump('attachments:');
        foreach ($parser->getAttachments() as $attachment) {
            var_dump($attachment->getContentID());
            var_dump($attachment->getFilename());
            $file = tempnam(sys_get_temp_dir(), 'attachment_');
            $dest = fopen($file, 'w');
            stream_copy_to_stream($attachment->getStream(), $dest);
            fclose($dest);
        }
    }
}

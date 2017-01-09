<?php

namespace App\Event;

use PhpMimeMailParser\Parser;
use Psr\Log\LoggerInterface;
use Smalot\Smtp\Server\Event\MessageReceivedEvent;
use Smalot\Smtp\Server\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ServerSubscriber
 * @package App\Event
 */
class ServerSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $folder;

    /**
     * ServerSubscriber constructor.
     * @param LoggerInterface $logger
     * @param string $folder
     */
    public function __construct(LoggerInterface $logger, $folder)
    {
        $this->logger = $logger;
        $this->folder = $folder;
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

        $details = [
          'from' => $parser->getHeader('from'),
          'to' => $parser->getHeader('to'),
          'subject' => $parser->getHeader('subject'),
          'attachments' => count($parser->getAttachments()),
        ];

        if ($messageId = md5(trim($parser->getHeader('message-id'), '<>'))) {
            $details['id'] = $messageId;
        } else {
            $messageId = $this->generateUniqId();
        }

        $this->logger->info('Message received: '.strlen($event->getMessage()).' bytes', $details);

        file_put_contents($this->folder.DIRECTORY_SEPARATOR.$messageId.'.eml', $event->getMessage());

        mkdir($this->folder.DIRECTORY_SEPARATOR.$messageId);

        foreach ($parser->getAttachments() as $attachment) {
            $file = $this->folder.DIRECTORY_SEPARATOR.$messageId.DIRECTORY_SEPARATOR.$attachment->getFilename();
            $dest = fopen($file, 'w');
            stream_copy_to_stream($attachment->getStream(), $dest);
            fclose($dest);
        }
    }

    /**
     * @return string
     */
    protected function generateUniqId()
    {
        $strong = true;
        $random = openssl_random_pseudo_bytes(32, $strong);

        return bin2hex($random);
    }
}

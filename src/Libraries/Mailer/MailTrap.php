<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.9.8
 */

namespace Quantum\Libraries\Mailer;

use Quantum\Libraries\Storage\Exceptions\FileSystemException;
use Quantum\Libraries\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Phemail\Message\MessagePartInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Libraries\Storage\FileSystem;
use Quantum\Di\Exceptions\DiException;
use Phemail\Message\MessagePart;
use Phemail\MessageParser;
use ReflectionException;

/**
 * class MailTrap
 * @package Quantum\Libraries\Mailer
 */
class MailTrap
{

    /**
     * @var FileSystem
     */
    private $fs;

    /**
     * @var MessageParser
     */
    private $parser;

    /**
     * @var MessagePart|MessagePartInterface
     */
    private $message;

    /**
     * @var MailTrap|null
     */
    private static $instance = null;

    /**
     * @var string
     */
    private $emailsDirectory;

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    private function __construct()
    {
        $this->fs = FileSystemFactory::get();
        $this->parser = new MessageParser();
        $this->emailsDirectory = base_dir() . DS . 'shared' . DS . 'emails';
    }

    /**
     * Get Instance
     * @return MailTrap
     */
    public static function getInstance(): MailTrap
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Saves the message on the local file
     * @param string $filename
     * @param string $content
     * @return bool
     */
    public function saveMessage(string $filename, string $content): bool
    {
        if (!$this->fs->isDirectory($this->emailsDirectory)) {
            return false;
        }

        return (bool) $this->fs->put($this->emailsDirectory . DS . $filename . '.eml', $content);
    }

    /**
     * Gets the parsed email
     * @param string $filename
     * @return $this
     * @throws BaseException
     */
    public function parseMessage(string $filename): MailTrap
    {
        $filePath = $this->emailsDirectory . DS . $filename . '.eml';

        if (!$this->fs->exists($filePath)) {
            throw FileSystemException::fileNotFound($filename);
        }

        $this->message = $this->parser->parse($filePath);
        return $this;
    }

    /**
     * Gets the parsed message ID
     * @return string|null
     */
    public function getParsedMessageId(): ?string
    {
        return $this->message->getHeaderValue('message-id');
    }

    /**
     * Gets the parsed X Mailer
     * @return string|null
     */
    public function getParsedXMailer(): ?string
    {
        return $this->message->getHeaderValue('x-mailer');
    }

    /**
     * Gets the parsed mime version
     * @return string|null
     */
    public function getParsedMimeVersion(): ?string
    {
        return $this->message->getHeaderValue('mime-version');
    }

    /**
     * Gets the parsed content type of the email
     * @return string|null
     */
    public function getParsedContentType(): ?string
    {
        return $this->message->getHeaderValue('content-type');
    }

    /**
     * Gets the parsed date
     * @return string|null
     */
    public function getParsedDate(): ?string
    {
        return $this->message->getHeaderValue('date');
    }

    /**
     * Gets the parsed 'To' addresses
     * @return array
     */
    public function getParsedToAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('to'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed 'From' address
     * @return string|null
     */
    public function getParsedFromAddress(): ?string
    {
        return $this->message->getHeaderValue('from');
    }

    /**
     * Gets the parsed 'CC' addresses
     * @return array
     */
    public function getParsedCcAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('cc'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed 'BCC' addresses
     * @return array
     */
    public function getParsedBccAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('bcc'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the 'Reply To' addresses
     * @return array
     */
    public function getParsedReplyToAddresses(): array
    {
        $addresses = explode(',', $this->message->getHeaderValue('reply-to'));
        return array_map('trim', $addresses);
    }

    /**
     * Gets the parsed subject
     * @return string|null
     */
    public function getParsedSubject(): ?string
    {
        return $this->message->getHeaderValue('subject');
    }

    /**
     * Gets the parsed body
     * @return string
     */
    public function getParsedBody(): string
    {
        $body = '';

        if ($this->message->isMultiPart()) {
            $parts = $this->message->getParts();

            if (isset($parts[0]) && $parts[0]->isText()) {
                $body = $parts[0]->getContents();
            }
        } else {
            $body = $this->message->getContents();
        }

        return $body;
    }

    /**
     * Gets the parsed attachments
     * @return array|null
     */
    public function getParsedAttachments(): ?array
    {
        $parsedAttachments = $this->message->getAttachments();

        if (empty($parsedAttachments)) {
            return null;
        }

        $attachments = [];

        foreach ($parsedAttachments as $parsedAttachment) {
            $attachments[] = [
                'filename' => $parsedAttachment->getHeaderAttribute('content-disposition', 'filename'),
                'content-type' => $parsedAttachment->getHeaderValue('content-type'),
                'content' => $parsedAttachment->getContents()
            ];
        }

        return $attachments;
    }
}
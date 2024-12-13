<?php

namespace App\Services;

use Webklex\IMAP\Client;
use Webklex\IMAP\Message;
use Illuminate\Support\Collection;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapService
{
    /**
     * The IMAP client instance
     *
     * @var Client
     */
    protected $client;

    /**
     * ImapService constructor.
     *
     * @param ClientManager $clientManager
     */
    public function __construct(ClientManager $clientManager)
    {
        $this->client = $clientManager->account('ars');
    }

    /**
     * Retrieve all messages from a specific mailbox folder.
     *
     * @param string $folderName
     * @return Collection
     *
     * @throws ConnectionFailedException
     */
    public function getMessagesFromFolder(string $folderName = 'INBOX')
    {
        // Connect to the IMAP server
        $this->client->connect();

        // Get a folder instance
        $folder = $this->client->getFolder($folderName);

        // Retrieve all messages
        $messages = $folder->query()->all()->get();

        return $messages;
    }

    /**
     * Delete a given message from the mailbox.
     *
     * @param Message $message
     * @return bool
     */
    public function deleteMessage($message)
    {
        // Delete the message
        $result = $message->delete();

        // If needed, explicitly expunge the mailbox:
        // $this->client->expunge();

        return $result;
    }
}

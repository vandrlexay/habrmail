<?php

namespace ItIsAllMail;

class Mailbox {
    protected $path;
    protected $localMessages;
    protected $mailSubdirs;
        
    public function __construct(string $path) {
        $this->path = $path;
        $this->localMessages = [];

        $this->mailSubdirs = [
            "new" => $this->path . DIRECTORY_SEPARATOR . "new",
            "cur" => $this->path . DIRECTORY_SEPARATOR . "cur"
        ];

        $this->loadMailbox();
    }

    /**
     * Checks if image with such id already exists
     */
    protected function msgExists(string $id) : bool {
        return (isset($this->localMessages[$id]) ? true : false);
    }

    /**
     * Read mailbox for a list of already known IDs
     */
    protected function loadMailbox() :void {
        $localFiles = [];

        foreach ($this->mailSubdirs as $msd) {
            $localFiles = array_merge($localFiles, scandir($msd));
        }

        foreach ($localFiles as $file) {
            $msgId = $file;
            $modified = strpos($file, ":");
            if ($modified !== false) {
                $msgId = substr($file, 0, $modified);
            }

            $this->localMessages[$msgId] = 1;
        }
    }

    public function mergeMessages(array $messages) : void {
        foreach ($messages as $msg) {
            $messageFilepath = $this->mailSubdirs["new"] . DIRECTORY_SEPARATOR . $msg->getId();


            if (! $this->msgExists($msg->getId())) {
                print "Adding " . $msg->getId()  . "\n";
                $this->localMessages[$msg->getId()] = 1;
                file_put_contents($messageFilepath, $msg->toMIMEString());
            }
        }
    }
}

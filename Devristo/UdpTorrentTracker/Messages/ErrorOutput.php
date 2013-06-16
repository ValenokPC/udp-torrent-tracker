<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 22:07
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


class ErrorOutput {
    protected $action = 3;
    protected $transactionId;
    protected $message;

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function toBytes(){
        return pack("NN", $this->action, $this->transactionId).$this->getMessage();
    }
}
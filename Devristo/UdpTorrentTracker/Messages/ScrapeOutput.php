<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 21:21
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


use Devristo\UdpTorrentTracker\SwarmPeer;

class ScrapeOutput {
    protected $action = 2;
    protected $transactionId;
    protected $connectionId;

    protected $leechers = array();
    protected $seeders = array();
    protected $completed = array();

    /**
     * @param array $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return array
     */
    public function getCompleted()
    {
        return $this->completed;
    }


    /**
     * @param int $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $connectionId
     */
    public function setConnectionId($connectionId)
    {
        $this->connectionId = $connectionId;
    }

    /**
     * @return mixed
     */
    public function getConnectionId()
    {
        return $this->connectionId;
    }

    /**
     * @param mixed $leechers
     */
    public function setLeechers(array $leechers)
    {
        $this->leechers = $leechers;
    }

    /**
     * @return mixed
     */
    public function getLeechers()
    {
        return $this->leechers;
    }

    /**
     * @param mixed $seeders
     */
    public function setSeeders(array $seeders)
    {
        $this->seeders = $seeders;
    }

    /**
     * @return mixed
     */
    public function getSeeders()
    {
        return $this->seeders;
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
        $header =
            Pack::pack_int32be($this->getAction())
            .Pack::pack_int32be($this->transactionId);

        foreach($this->seeders as $seeders){
            $header .= pack("N", $seeders);
        }

        foreach($this->completed as $completed){
            $header .= pack("N", $completed);
        }

        foreach($this->leechers as $leechers){
            $header .= pack("N", $leechers);
        }

        return $header;
    }
}
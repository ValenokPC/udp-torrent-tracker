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

class AnnounceOutput {
    protected $action = 1;
    protected $transactionId;
    protected $connectionId;

    protected $interval = 15;
    protected $leechers = 0;
    protected $seeders = 0;

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
     * @param mixed $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return mixed
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param mixed $leechers
     */
    public function setLeechers($leechers)
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
    public function setSeeders($seeders)
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



    /**
     * @var SwarmPeer[]
     */
    protected $_peers = array();

    public function toBytes(){
        $header =
            Pack::pack_int32be($this->getAction())
            .hex2bin($this->transactionId)
            .Pack::pack_int32be($this->interval)
            .Pack::pack_int32be($this->leechers)
            .Pack::pack_int32be($this->seeders);

        $peerData = '';
        foreach($this->_peers as $peer){
            $peerData .= ip2long($peer->getIp()).pack("n", $peer->getPort());
        }

        return $header.$peerData;
    }

    public function addPeer(SwarmPeer $peer){
        $this->_peers[] = $peer;
    }
}
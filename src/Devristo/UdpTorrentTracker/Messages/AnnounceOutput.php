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

    public static function fromBytes($data){
        $o = new AnnounceOutput();

        $header = unpack("Naction/Ntransaction/Ninterval/Nseeders/Nleechers", $data);
        $o->setAction($header['action']);
        $o->setTransactionId($header['transaction']);
        $o->setInterval($header['interval']);
        $o->setLeechers($header['leechers']);
        $o->setSeeders($header['seeders']);

        $offset = 20;

        while($offset < strlen($data)){
            $peerdata = unpack("Niplong/nport", substr($data, $offset));
            $peer = new SwarmPeer(long2ip($peerdata['iplong']), $peerdata['port']);
            $o->addPeer($peer);
            $offset += 6;
        }

        return $o;

    }

    public function toBytes(){
        $header = pack("NNNNN", $this->getAction(), $this->transactionId, $this->interval, $this->leechers, $this->seeders);

        $peerData = '';
        foreach($this->_peers as $peer){
            $peerData .= pack("N", ip2long($peer->getIp())).pack("n", $peer->getPort());
        }

        return $header.$peerData;
    }

    public function addPeer(SwarmPeer $peer){
        $this->_peers[] = $peer;
    }

    public function getPeers()
    {
        return $this->_peers;
    }
}
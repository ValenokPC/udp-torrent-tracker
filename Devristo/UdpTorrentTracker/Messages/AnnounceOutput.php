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

    protected $interval;
    protected $leechers;
    protected $seeders;

    /**
     * @var SwarmPeer[]
     */
    protected $_peers = array();

    public function toBytes(){
        $header = pack("NNNNN", $this->action, $this->transactionId, $this->interval, $this->leechers, $this->seeders);

        $peerData = array();
        foreach($this->_peers as $peer){
            list($ip, $port) = explode(":", $peer);

            $peerData .= pack("NN",$peer->getIp(), $peer->getPort());
        }

        return $header.$peerData;
    }

    public function addPeer(SwarmPeer $peer){
        $this->_peers[] = $peer;
    }
}
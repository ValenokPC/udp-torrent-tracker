<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:36
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


use Devristo\UdpTorrentTracker\Exceptions\ProtocolViolationException;
use Devristo\UdpTorrentTracker\Messages\Input;

class ScrapeInput extends Input {
    protected $infoHashes = array();

    public static function fromUdpPacket($peerIp, $peerPort, $data){
        if(strlen($data) < 20)
            throw new ProtocolViolationException("Data packet should be at least 20 bytes long");


        $offset = 0;
        $connectionId = substr($data, $offset, 8);
        $offset += 8;

        list(,$action) = (unpack("N", substr($data, $offset, 4)));
        $offset += 4;
        $transactionId = substr($data, $offset, 4);
        $offset +=4;

        $o = new self();

        $o->peerIp = $peerIp;
        $o->peerPort = $peerPort;

        $o->setConnectionId(bin2hex($connectionId));
        $o->setAction($action);
        $o->setTransactionId(bin2hex($transactionId));

        while($offset + 20 <= strlen($data)){
            $o->infoHashes[] = (substr($data, $offset, 20));
            $offset += 20;
        }



        if($action !== 2)
            throw new ProtocolViolationException("Action should be 2 for a SCRAPE INPUT");

        return $o;
    }
}
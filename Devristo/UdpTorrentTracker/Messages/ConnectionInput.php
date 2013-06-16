<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:24
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


use Devristo\UdpTorrentTracker\Messages\Input;
use Devristo\UdpTorrentTracker\Exceptions\ProtocolViolationException;

class ConnectionInput extends Input{
    public static function fromUdpPacket($peer, $data){
        if(strlen($data) < 16)
            throw new ProtocolViolationException("Data packet should be at least 16 bytes long");

        $o = new self();

        $offset = 0;
        $connectionId = substr($data, $offset, 8);
        $offset += 8;

        list($action, $transactionId) = unpack("NN", substring($data, $offset, 8));

        $o->setConnectionId($connectionId);
        $o->setAction($action);
        $o->setTransactionId($transactionId);


        if($action !== 0)
            throw new ProtocolViolationException("Action should be 0 for a CONNECT INPUT");

        if($connectionId !== hex2bin("41727101980"))
            throw new ProtocolViolationException("ConnectionId shoulde be 0x41727101980 for CONNECT INPUT");

        return $o;
    }
}
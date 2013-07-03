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
    public static function fromUdpPacket($peerIp, $peerPort, $data){
        if(strlen($data) < 16)
            throw new ProtocolViolationException("Data packet should be at least 16 bytes long");

        $o = new self();

        $offset = 0;
        $connectionId = substr($data, $offset, 8);
        $offset += 8;

        $action = Pack::unpack_int32be(substr($data, $offset, 4));
        $offset += 4;

        $transactionId = Pack::unpack_int32be(substr($data, $offset, 4));
        $offset += 4;

        $o->setConnectionId(bin2hex($connectionId));
        $o->setAction($action);
        $o->setTransactionId($transactionId);

        $o->peerIp = $peerIp;
        $o->peerPort = $peerPort;


        if($action !== 0)
            throw new ProtocolViolationException("Action should be 0 for a CONNECT INPUT");

        if($connectionId !== hex2bin("0000041727101980"))
            throw new ProtocolViolationException("ConnectionId shoulde be 0x41727101980 for CONNECT INPUT");

        return $o;
    }
}
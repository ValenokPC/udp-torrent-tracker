<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:38
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


abstract class Input
{
    const PACKET_TYPE_CONNECT = 0;
    const PACKET_TYPE_ANNOUNCE = 1;
    const PACKET_TYPE_SCRAPE = 2;

    protected static $_typeNames = array(
        self::PACKET_TYPE_CONNECT => "connect",
        self::PACKET_TYPE_ANNOUNCE => "announce",
        self::PACKET_TYPE_SCRAPE => "scrape"
    );

    protected $connectionId;
    protected $action;
    protected $transactionId;

    protected $peer;

    /**
     * @param mixed $peer
     */
    public function setPeer($peer)
    {
        $this->peer = $peer;
    }

    /**
     * @return mixed
     */
    public function getPeer()
    {
        return $this->peer;
    }

    public function getType(){
        return self::$_typeNames[$this->getAction()];
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return mixed
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

    static public function fromUdpPacket($peer, $data)
    {
        if (strlen($data) < 16)
            throw new ProtocolViolationException("Data packet should be at least 16 bytes long");


        $offset = 0;
        $connectionId = substr($data, $offset, 8);
        $offset += 8;

        list($action, $transactionId) = unpack("NN", substring($data, $offset, 8));

        switch ($action) {
            case self::PACKET_TYPE_CONNECT:
                return ConnectionInput::fromUdpPacket($peer, $data);
            case self::PACKET_TYPE_ANNOUNCE:
                return AnnounceInput::fromUdpPacket($peer, $data);
            case self::PACKET_TYPE_SCRAPE:
                return ScrapeInput::fromUdpPacket($peer, $data);
        }
    }
}
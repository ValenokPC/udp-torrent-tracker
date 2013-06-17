<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:36
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


use Devristo\UdpTorrentTracker\Messages\Input;
use Devristo\UdpTorrentTracker\Exceptions\ProtocolViolationException;
use Zend\Math\BigInteger\BigInteger;

class AnnounceInput extends Input{


    protected $infoHash;
    protected $peerId;
    protected $downloaded;
    protected $left;
    protected $uploaded;
    protected $event;
    protected $ipv4;
    protected $key;
    protected $num_want;
    protected $port;
    protected $extensions;

    protected $credentials = null;

    /**
     * @return null
     */
    public function getRequestString()
    {
        return $this->requestString;
    }

    /**
     * @return mixed
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return null
     */
    public function getCredentials()
    {
        return $this->credentials;
    }
    protected $requestString = null;

    /**
     * @param mixed $downloaded
     */
    public function setDownloaded($downloaded)
    {
        $this->downloaded = $downloaded;
    }

    /**
     * @return mixed
     */
    public function getDownloaded()
    {
        return $this->downloaded;
    }

    /**
     * @param mixed $infoHash
     */
    public function setInfoHash($infoHash)
    {
        $this->infoHash = $infoHash;
    }

    /**
     * @return mixed
     */
    public function getInfoHash()
    {
        return $this->infoHash;
    }

    /**
     * @param mixed $ipv4
     */
    public function setIpv4($ipv4)
    {
        $this->ipv4 = $ipv4;
    }

    /**
     * @return mixed
     */
    public function getIpv4()
    {
        return $this->ipv4;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $left
     */
    public function setLeft($left)
    {
        $this->left = $left;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param mixed $num_want
     */
    public function setNumWant($num_want)
    {
        $this->num_want = $num_want;
    }

    /**
     * @return mixed
     */
    public function getNumWant()
    {
        return $this->num_want;
    }

    /**
     * @param mixed $peerId
     */
    public function setPeerId($peerId)
    {
        $this->peerId = $peerId;
    }

    /**
     * @return mixed
     */
    public function getPeerId()
    {
        return $this->peerId;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $uploaded
     */
    public function setUploaded($uploaded)
    {
        $this->uploaded = $uploaded;
    }

    /**
     * @return mixed
     */
    public function getUploaded()
    {
        return $this->uploaded;
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

    public static function fromUdpPacket($peer, $data){
        if(strlen($data) < 20)
            throw new ProtocolViolationException("Data packet should be at least 20 bytes long");


        $offset = 0;
        $connectionId = substr($data, $offset, 8);
        $offset += 8;

        list($action, $transactionId) = array_values(unpack("N2", substr($data, $offset, 8)));
        $offset += 8;

        $infoHash = substr($data, $offset, 20);
        $offset += 20;

        $peerId = substr($data, $offset, 20);
        $offset += 20;

        $bi = BigInteger::getDefaultAdapter();

        $downloaded = $bi->binToInt(substr($data, $offset, 8));
        $offset += 8;

        $left = $bi->binToInt(substr($data, $offset, 8));
        $offset += 8;

        $uploaded = $bi->binToInt(substr($data, $offset, 8));
        $offset += 8;

        list($event, $ipv4, $key, $numWant, $port) = array_values(unpack("N5", substr($data, $offset, 4*5)));
        $offset += 4*5;




        $o = new self();

        $o->setConnectionId($connectionId);
        $o->setAction($action);
        $o->setTransactionId($transactionId);

        $o->setInfoHash($infoHash);
        $o->setPeerId($peerId);
        $o->setDownloaded($downloaded);
        $o->setLeft($left);
        $o->setUploaded($uploaded);
        $o->setEvent($event);
        $o->setIpv4($ipv4);
        $o->setkey($key);
        $o->setNumWant($numWant);
        $o->setPort($port);

        # We have extensions
        if($offset + 2 <= strlen($data)){
            list($extensions) = array_values(unpack("n", substr($data, $offset,2)));
            $offset += 2;

            if($extensions & 1)
                $o->credentials = AuthenticationExtension::fromBytes($data, $offset);

            if($extensions & 2)
                $o->requestString = RequestStringExtension::fromBytes($data, $offset);
        }

        if($action !== 1)
            throw new ProtocolViolationException("Action should be 1 for a ANNOUNCE INPUT");

        return $o;
    }
}
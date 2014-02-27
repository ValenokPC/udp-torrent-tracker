<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:20
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker;


use Devristo\UdpTorrentTracker\Exceptions\ProtocolViolationException;
use Devristo\UdpTorrentTracker\Messages\AnnounceInput;
use Devristo\UdpTorrentTracker\Messages\AnnounceOutput;
use Devristo\UdpTorrentTracker\Messages\ConnectionInput;
use Devristo\UdpTorrentTracker\Messages\ConnectionOutput;
use Devristo\UdpTorrentTracker\Messages\ErrorOutput;
use Devristo\UdpTorrentTracker\Messages\Input;
use Devristo\UdpTorrentTracker\Messages\ScrapeInput;
use Devristo\UdpTorrentTracker\Messages\ScrapeOutput;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Math\Rand;
use DateTime;

class Server implements EventManagerAwareInterface {
    protected $port;
    protected $ip;

    protected $socket;
    protected $_eventManager = null;

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
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
     * @var Connection[]
     */
    protected $_connections = array();

    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return void
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->_eventManager = $eventManager;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if(null === $this->_eventManager)
            $this->_eventManager = new EventManager(get_class($this));

        return $this->_eventManager;
    }

    public function run(){
        $this->socket = $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);//stream_socket_server("udp://{$this->getIp()}:{$this->getPort()}", $errno, $errstr, STREAM_SERVER_BIND);
        socket_set_option ($socket, SOL_SOCKET, SO_REUSEADDR, 1);


        if (!@socket_bind($socket,$this->getIp(), $this->getPort())) {
            die(socket_last_error($socket));
        }

        $this->getEventManager()->trigger("listen-start", $this);

        $buf = null;
        $from = null;
        $port = null;

        $result = false;

        do {
            try{
                $result = socket_recvfrom($socket, $buf, 1500,0, $from, $port);

                if($result === false)
                    exit("Error occurred!");

                $inputPacket = Input::fromUdpPacket($from, $port, $buf);

                if($inputPacket instanceof ConnectionInput)
                    $this->onConnect($inputPacket);
                elseif($inputPacket instanceof AnnounceInput)
                    $this->onAnnounce($inputPacket);
                elseif($inputPacket instanceof ScrapeInput)
                    $this->onScrape($inputPacket);

                # Trigger events
                $this->getEventManager()->trigger("input", $this, array("packet" => $inputPacket));
            } catch(\Exception $exception){
                $this->getEventManager()->trigger("exception", $this, array("exception" => $exception));
            }

        } while ($result !== false);
    }

    private function onConnect(ConnectionInput $in){
        do{
            $connectionId = bin2hex(pack("NN",rand(0,2000000000),rand(0,2000000000)));
        } while(array_key_exists($connectionId, $this->_connections));

        $this->_connections[$connectionId] = new Connection($connectionId, new DateTime());

        $reply = new ConnectionOutput();
        $reply->setConnectionId($connectionId);
        $reply->setTransactionId($in->getTransactionId());

        $buff = $reply->toBytes();
        socket_sendto($this->socket, $buff, strlen($buff), 0, $in->getPeerIp(), $in->getPeerPort());

        $this->getEventManager()->trigger("connect", $this, array("request" => $in));

    }

    private function onAnnounce(AnnounceInput $announce){
        if(!array_key_exists($announce->getConnectionId(), $this->_connections)){
            $this->sendError($announce->getPeerIp(), $announce->getPeerPort(), $announce->getTransactionId(), "Client not connected");
            return;
        }

        # Heartbeat
        $this->_connections[$announce->getConnectionId()]->setLastHeartbeat(new DateTime());

        $this->getEventManager()->trigger("announce", $this, compact("announce"));
    }

    public function sendError($peerIp, $peerPort, $transactionId, $message)
    {
        $error = new ErrorOutput();
        $error->setTransactionId($transactionId);
        $error->setMessage($message);

        $buff = $error->toBytes();
        socket_sendto($this->socket, $buff, strlen($buff),0, $peerIp, $peerPort);
    }

    public function replyAnnounce(AnnounceInput $input, $seeders, $leechers, array $peers){
        $output = new AnnounceOutput();
        $output->setTransactionId($input->getTransactionId());
        $output->setSeeders($seeders);
        $output->setLeechers($leechers);

        foreach($peers as $peer)
            $output->addPeer($peer);

        $buff = $output->toBytes();
        socket_sendto($this->socket, $buff, strlen($buff), 0, $input->getPeerIp(), $input->getPeerPort());
    }

    public function replyScrape(ScrapeInput $input, array $seeders, array $completed, array $leechers){
        $output = new ScrapeOutput();

        $output->setSeeders($seeders);
        $output->setCompleted($completed);
        $output->setLeechers($leechers);

        $buff = $output->toBytes();
        socket_sendto($this->socket, $buff, strlen($buff), 0, $input->getPeerIp(), $input->getPeerPort());
    }

    private function onScrape(ScrapeInput $scrape)
    {
        if(!array_key_exists($scrape->getConnectionId(), $this->_connections)){
            $this->sendError($scrape->getPeerIp(), $scrape->getPeerPort(), $scrape->getTransactionId(), "Client not connected");
            return;
        }

        # Heartbeat
        $this->_connections[$scrape->getConnectionId()]->setLastHeartbeat(new DateTime());

        $this->getEventManager()->trigger("scrape", $this,compact("scrape"));
    }
}
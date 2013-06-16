<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 20:20
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTracker;


use Devristo\UdpTorrentTracker\Messages\AnnounceInput;
use Devristo\UdpTorrentTracker\Messages\AnnounceOutput;
use Devristo\UdpTorrentTracker\Messages\ConnectionInput;
use Devristo\UdpTorrentTracker\Messages\ConnectionOutput;
use Devristo\UdpTorrentTracker\Messages\ErrorOutput;
use Devristo\UdpTorrentTracker\Messages\Input;
use Devristo\UdpTorrentTracker\Messages\ScrapeInput;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Math\Rand;
use DateTime;

class Server implements EventManagerAwareInterface {
    protected $socket;
    protected $_eventManager = null;

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
        $this->socket = $socket = stream_socket_server("udp://0.0.0.1:1113", $errno, $errstr, STREAM_SERVER_BIND);

        if (!$socket) {
            die("$errstr ($errno)");
        }

        do {
            $udpPacket = stream_socket_recvfrom($socket, 1, 0, $peer);

            $inputPacket = Input::fromUdpPacket($peer, $udpPacket);

            if($inputPacket instanceof ConnectionInput)
                $this->onConnect($inputPacket);
            elseif($inputPacket instanceof AnnounceInput)
                $this->onAnnounce($inputPacket);
            elseif($inputPacket instanceof ScrapeInput)
                $this->onScrape($inputPacket);

            # Trigger events
            $this->getEventManager()->trigger("input", array("packet" => $inputPacket));
        } while ($udpPacket !== false);
    }

    private function onConnect(ConnectionInput $in){
        do{
            $connectionId = Rand::getBytes("8");
        } while(array_key_exists($connectionId, $this->_connections));

        $this->_connections[$connectionId] = new Connection($connectionId, new DateTime());

        $reply = new ConnectionOutput();
        $reply->setConnectionId($connectionId);
        $reply->setTransactionId($in->getTransactionId());

        stream_socket_sendto($this->socket, $reply->toBytes(), $in->getPeer());
    }

    private function onAnnounce(AnnounceInput $announce){
        if(!array_key_exists($announce->getConnectionId(), $this->_connections)){
            $this->sendError($announce->getPeer(), $announce->getTransactionId(), "Client not connected");
            return;
        }

        # Heartbeat
        $this->_connections[$announce->getConnectionId()]->setLastHeartbeat(new DateTime());

        $this->getEventManager()->trigger("announce", compact("announce"));
    }

    private function sendError($peer, $transactionId, $message)
    {
        $error = new ErrorOutput();
        $error->setTransactionId($transactionId);
        $error->setMessage($message);

        stream_socket_sendto($this->socket, $error->toBytes(), $peer);
    }

    public function announce(AnnounceInput $input, array $peers){
        $output = new AnnounceOutput();

        foreach($peers as $peer)
            $output->addPeer($peer);

        stream_socket_sendto($this->socket, $output->toBytes(), $input->getPeer());
    }

    private function onScrape(ScrapeInput $scrape)
    {
        if(!array_key_exists($scrape->getConnectionId(), $this->_connections)){
            $this->sendError($scrape->getPeer(), $scrape->getTransactionId(), "Client not connected");
            return;
        }

        # Heartbeat
        $this->_connections[$scrape->getConnectionId()]->setLastHeartbeat(new DateTime());

        $this->getEventManager()->trigger("scrape", compact("scrape"));
    }
}
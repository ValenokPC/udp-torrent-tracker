<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Chris
 * Date: 16-6-13
 * Time: 23:14
 * To change this template use File | Settings | File Templates.
 */

namespace Devristo\UdpTorrentTracker\Messages;


class RequestStringExtension {
    protected $requestString;

    /**
     * @param mixed $requestString
     */
    public function setRequestString($requestString)
    {
        $this->requestString = $requestString;
    }

    /**
     * @return mixed
     */
    public function getRequestString()
    {
        return $this->requestString;
    }

    public static function fromBytes($data, &$offset){
        list($length) = unpack("n", substr($data, $offset, 2));
        $offset += 2;

        $o = new self();
        $o->requestString = substr($data, $offset, $length);
        $offset += $length;

        return $o;
    }
}
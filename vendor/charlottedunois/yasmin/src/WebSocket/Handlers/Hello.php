<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

/**
 * WS Event handler
 * @internal
 */
class Hello implements \CharlotteDunois\Yasmin\Interfaces\WSHandlerInterface {
    public $heartbeat = null;
    protected $wshandler;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSHandler $wshandler) {
        $this->wshandler = $wshandler;
        
        $this->wshandler->wsmanager->on('close', function () {
            $this->close();
        });
    }
    
    function handle($packet) {
        $this->wshandler->client->emit('debug', 'Connected to Gateway via '.\implode(', ', $packet['d']['_trace']));
        
        $interval = $packet['d']['heartbeat_interval'] / 1000;
        $this->wshandler->wsmanager->ratelimits['heartbeatRoom'] = (int) \ceil($this->wshandler->wsmanager->ratelimits['total'] / $interval);
        
        $this->heartbeat = $this->wshandler->client->getLoop()->addPeriodicTimer($interval, function () {
            $this->wshandler->wsmanager->heartbeat();
        });
    }
    
    private function close() {
        if($this->heartbeat !== null) {
            $this->heartbeat->cancel();
            $this->heartbeat = null;
        }
    }
}

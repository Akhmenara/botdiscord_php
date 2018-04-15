<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#ready
 * @internal
 */
class Ready implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $ready = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $wsmanager->once('ready', function () {
            $this->ready = true;
        });
    }
    
    function handle(array $data) {
        if(empty($data['user']['bot'])) {
            $this->client->wsmanager()->emit('self.ws.error', 'User accounts are not supported');
            return;
        }
        
        $this->client->wsmanager()->setAuthenticated(true);
        $this->client->wsmanager()->setSessionID($data['session_id']);
        $this->client->wsmanager()->emit('self.ws.ready');
        
        if($this->ready && $this->client->user !== null) {
            $this->client->user->_patch($data['user']);
            $this->client->wsmanager()->emit('ready');
            return;
        }
        
        $this->client->setClientUser($data['user']);
        
        foreach($data['guilds'] as $guild) {
            if(!$this->client->guilds->has($guild['id'])) {
                $guild = new \CharlotteDunois\Yasmin\Models\Guild($this->client, $guild);
                $this->client->guilds->set($guild->id, $guild);
            }
        }
        
        // Emit ready after waiting N guilds * 1.2 seconds - we waited long enough for Discord to get the guilds to us
        $timer = $this->client->addTimer(\ceil(($this->client->guilds->count() * 1.2)), function () {
            if($this->ready === false) {
                $this->client->wsmanager()->emit('ready');
            }
        });
        
        $this->client->wsmanager()->on('guildCreate', function () use (&$timer) {
            if($this->client->getWSstatus() === \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTED) {
                return;
            }
            
            $unavailableGuilds = 0;
            foreach($this->client->guilds as $guild) {
                if($guild->available === false) {
                    $unavailableGuilds++;
                }
            }
            
            if($unavailableGuilds === 0) {
                $this->client->cancelTimer($timer);
                $this->client->wsmanager()->emit('ready');
            }
        });
    }
}

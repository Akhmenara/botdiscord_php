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
 * @see https://discordapp.com/developers/docs/topics/gateway#user-update
 * @internal
 */
class UserUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('userUpdate', (array) $clones));
    }
    
    function handle(array $data) {
        $user = $this->client->users->get($data['id']);
        if($user) {
            $oldUser = null;
            if($this->clones) {
                $oldUser = clone $user;
            }
            
            $user->_patch($data);
            
            $this->client->emit('userUpdate', $user, $oldUser);
        }
    }
}

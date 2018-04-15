<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a message reaction.
 *
 * @property \CharlotteDunois\Yasmin\Models\Emoji        $emoji     The emoji this message reaction is for.
 * @property int                                         $count     Times this emoji has been reacted.
 * @property bool                                        $me        Whether the current user has reacted using this emoji.
 * @property \CharlotteDunois\Yasmin\Models\Message      $message   The message this reaction belongs to.
 * @property \CharlotteDunois\Yasmin\Utils\Collection    $users     The users that have given this reaction, mapped by their ID.
 */
class MessageReaction extends ClientBase {
    protected $message;
    protected $emoji;
    
    protected $count;
    protected $me;
    protected $users;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Message $message, \CharlotteDunois\Yasmin\Models\Emoji $emoji, array $reaction) {
        parent::__construct($client);
        $this->message = $message;
        $this->emoji = $emoji;
        
        $this->count = (int) $reaction['count'];
        $this->me = (bool) $reaction['me'];
        $this->users = new \CharlotteDunois\Yasmin\Utils\Collection();
    }
    
    /**
     * @inheritDoc
     *
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Fetches all the users that gave this reaction. Resolves with a Collection of User instances, mapped by their IDs.
     * @param int     $limit   The maximum amount of users to fetch, defaults to 100.
     * @param string  $before  Limit fetching users to those with an ID smaller than the given ID.
     * @param string  $after   Limit fetching users to those with an ID greater than the given ID.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\User
     */
    function fetchUsers(int $limit = 100, string $before = '', string $after = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($limit, $before, $after) {
            $query = array('limit' => $limit);
            
            if(!empty($before)) {
                $query['before'] = $before;
            }
            
            if(!empty($after)) {
                $query['after'] = $after;
            }
            
            $this->client->apimanager()->endpoints->channel->getMessageReactions($this->message->channel->id, $this->message->id, $this->emoji->identifier, $query)->done(function ($data) use ($resolve) {
                foreach($data as $react) {
                    $user = $this->client->users->patch($react);
                    $this->users->set($user->id, $user);
                }
                
                $resolve($this->users);
            }, $reject);
        }));
    }
    
    /**
     * Removes an user from the reaction. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\User|string  $user  Defaults to the client user.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    function remove($user = null) {
        if($user !== null) {
            $user = $this->client->users->resolve($user);
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($user) {
            $this->client->apimanager()->endpoints->channel->deleteMessageUserReaction($this->message->channel->id, $this->message->id, ($this->emoji->id ?? \rawurlencode($this->emoji->name)), ($user !== null ? $user->id : '@me'))->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Increments the count.
     * @internal
     */
    function _incrementCount() {
        $this->count++;
    }
    
    /**
     * Decrements the count.
     * @internal
     */
    function _decrementCount() {
        $this->count--;
    }
}

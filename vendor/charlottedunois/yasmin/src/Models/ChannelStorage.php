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
 * Channel Storage to store channels, which utilizes Collection.
 */
class ChannelStorage extends Storage {
    /**
     * Channel Types.
     * @var array
     * @source
     */
    const CHANNEL_TYPES = array(
        0 => 'text',
        1 => 'dm',
        2 => 'voice',
        3 => 'group',
        4 => 'category',
        
        'text' => 0,
        'dm' => 1,
        'voice' => 2,
        'group' => 3,
        'category' => 4
    );
    
    /**
     * Resolves given data to a channel.
     * @param \CharlotteDunois\Yasmin\Interfaces\ChannelInterface|string|int  $channel  string/int = channel ID
     * @return \CharlotteDunois\Yasmin\Interfaces\ChannelInterface
     * @throws \InvalidArgumentException
     */
    function resolve($channel) {
        if($channel instanceof \CharlotteDunois\Yasmin\Interfaces\ChannelInterface) {
            return $channel;
        }
        
        if(\is_int($channel)) {
            $channel = (string) $channel;
        }
        
        if(\is_string($channel) && $this->has($channel)) {
            return $this->get($channel);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown channel');
    }
    
    /**
     * @inheritDoc
     */
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->channels) {
            $this->client->channels->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    function delete($key) {
        parent::delete($key);
        if($this !== $this->client->channels) {
            $this->client->channels->delete($key);
        }
        
        return $this;
    }
    
    /**
     * @internal
     */
    function factory(array $data, \CharlotteDunois\Yasmin\Models\Guild $guild = null) {
        if($guild === null) {
            $guild = (!empty($data['guild_id']) ? $this->client->guilds->get($data['guild_id']) : null);
        }
        
        if($this->has($data['id'])) {
            $channel = $this->get($data['id']);
            $channel->_patch($data);
            return $channel;
        }
        
        switch($data['type']) {
            default:
                throw new \InvalidArgumentException('Unknown channel type');
            break;
            case 0:
                if($guild === null) {
                    throw new \InvalidArgumentException('Unknown guild for guild channel');
                }
                
                $channel = new \CharlotteDunois\Yasmin\Models\TextChannel($this->client, $guild, $data);
            break;
            case 1:
                $channel = new \CharlotteDunois\Yasmin\Models\DMChannel($this->client, $data);
            break;
            case 2:
                if($guild === null) {
                    throw new \InvalidArgumentException('Unknown guild for guild channel');
                }
                
                $channel = new \CharlotteDunois\Yasmin\Models\VoiceChannel($this->client, $guild, $data);
            break;
            case 3:
                $channel = new \CharlotteDunois\Yasmin\Models\GroupDMChannel($this->client, $data);
            break;
            case 4:
                if($guild === null) {
                    throw new \InvalidArgumentException('Unknown guild for guild channel');
                }
                
                $channel = new \CharlotteDunois\Yasmin\Models\CategoryChannel($this->client, $guild, $data);
            break;
        }
        
        $this->set($channel->id, $channel);
        
        if($guild) {
            $guild->channels->set($channel->id, $channel);
        }
        
        return $channel;
    }
}

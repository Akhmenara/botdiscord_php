<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin;

/**
 * The client. What else do you expect this to say?
 *
 * @property \CharlotteDunois\Yasmin\Models\ChannelStorage   $channels   It holds all cached channels, mapped by ID.
 * @property \CharlotteDunois\Yasmin\Models\EmojiStorage     $emojis     It holds all emojis, mapped by ID (custom emojis) and/or name (unicode emojis).
 * @property \CharlotteDunois\Yasmin\Models\GuildStorage     $guilds     It holds all guilds, mapped by ID.
 * @property \CharlotteDunois\Yasmin\Models\PresenceStorage  $presences  It holds all cached presences (latest ones), mapped by user ID.
 * @property \CharlotteDunois\Yasmin\Models\UserStorage      $users      It holds all cached users, mapped by ID.
 * @property \CharlotteDunois\Yasmin\Models\ClientUser|null  $user       User that the client is logged in as. The instance gets created when the client turns ready.
 *
 * @method on(string $event, callable $listener)               Attach a listener to an event. The method is from the trait - only for documentation purpose here.
 * @method once(string $event, callable $listener)             Attach a listener to an event, for exactly once. The method is from the trait - only for documentation purpose here.
 * @method removeListener(string $event, callable $listener)   Remove specified listener from an event. The method is from the trait - only for documentation purpose here.
 * @method removeAllListeners($event = null)                   Remove all listeners from an event (or all listeners).
 */
class Client implements \CharlotteDunois\Events\EventEmitterInterface, \Serializable {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * The version of Yasmin.
     * @var string
     */
    const VERSION = '0.4.0';
    
    /**
     * WS connection status: Disconnected.
     * @var int
     */
    const WS_STATUS_DISCONNECTED = 0;
    
    /**
     * WS connection status: Connecting.
     * @var int
     */
    const WS_STATUS_CONNECTING = 1;
    
    /**
     * WS connection status: Reconnecting.
     * @var int
     */
    const WS_STATUS_RECONNECTING = 2;
    
    /**
     * WS connection status: Connected (not ready yet - nearly).
     * @var int
     */
    const WS_STATUS_NEARLY = 3;
    
    /**
     * WS connection status: Connected (ready).
     * @var int
     */
    const WS_STATUS_CONNECTED = 4;
    
    /**
     * WS connection status: Idling (disconnected and no reconnect planned).
     * @var int
     */
    const WS_STATUS_IDLE = 5;
    
    /**
     * WS default compression.
     * @var string
     */
    const WS_DEFAULT_COMPRESSION = 'zlib-stream';
    
    /**
     * It holds all cached channels, mapped by ID.
     * @var \CharlotteDunois\Yasmin\Models\ChannelStorage<\CharlotteDunois\Yasmin\Interfaces\ChannelInterface>
     * @internal
     */
    protected $channels;
    
    /**
     * It holds all emojis, mapped by ID (custom emojis) and/or name (unicode emojis).
     * @var \CharlotteDunois\Yasmin\Models\EmojiStorage<\CharlotteDunois\Yasmin\Models\Emoji>
     * @internal
     */
    protected $emojis;
    
    /**
     * It holds all guilds, mapped by ID.
     * @var \CharlotteDunois\Yasmin\Models\GuildStorage<\CharlotteDunois\Yasmin\Models\Guild>
     * @internal
     */
    protected $guilds;
    
    /**
     * It holds all cached presences (latest ones), mapped by user ID.
     * @var \CharlotteDunois\Yasmin\Models\PresenceStorage<\CharlotteDunois\Yasmin\Models\PresenceStorage>
     * @internal
     */
    protected $presences;
    
    /**
     * It holds all cached users, mapped by ID.
     * @var \CharlotteDunois\Yasmin\Models\UserStorage<\CharlotteDunois\Yasmin\Models\User>
     * @internal
     */
    protected $users;
    
    /**
     * The last 3 websocket pings (in ms).
     * @var int[]
     */
    public $pings = array();
    
    /**
     * The UNIX timestamp of the last emitted ready event (or null if none yet).
     * @var int|null
     */
    public $readyTimestamp = null;
    
    /**
     * The token.
     * @var string|null
     */
    public $token;
    
    /**
     * The event loop.
     * @var \React\EventLoop\LoopInterface
     * @internal
     */
    protected $loop;
    
    /**
     * Client Options.
     * @var array
     * @internal
     */
    protected $options = array();
    
    /**
     * The Client User.
     * @var \CharlotteDunois\Yasmin\Models\ClientUser|null
     * @internal
     */
    protected $user;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     * @internal
     */
    protected $api;
    
    /**
     * @var \CharlotteDunois\Yasmin\WebSocket\WSManager|null
     * @internal
     */
    protected $ws;
    
    /**
     * Gateway address.
     * @var string
     * @internal
     */
    protected $gateway;
    
    /**
     * Timers which automatically get cancelled on destroy and only get run when we have a WS connection.
     * @var array
     * @internal
     */
    protected $timers = array();
    
    /**
     * Loaded Utils with a loop instance.
     * @var array
     * @internal
     */
    protected $utils = array();
    
    /**
     * What do you expect this to do? It makes a new Client instance. Available client options are as following (all are optional):
     *
     * <pre>
     * array(
     *   'disableClones' => bool|string[], (disables cloning of class instances (for perfomance), affects update events - bool: true - disables all cloning)
     *   'disableEveryone' => bool, (disables the everyone and here mentions and replaces them with plaintext, defaults to true)
     *   'fetchAllMembers' => bool, (fetches all guild members, this should be avoided - necessary members get automatically fetched)
     *   'messageCacheLifetime' => int, (invalidates messages in the store older than the specified duration)
     *   'messageSweepInterval' => int, (interval when the message cache gets invalidated (see messageCacheLifetime), defaults to messageCacheLifetime)
     *   'shardID' => int, (shard ID, 0-indexed, always needs to be smaller than shardCount, important for sharding)
     *   'shardCount' => int, (shard count, important for sharding)
     *   'userSweepInterval' => int, (interval when the user cache gets invalidated (users sharing no mutual guilds get removed), defaults to 600)
     *   'http.restTimeOffset' => int|float, (specifies how many seconds should be waited after one REST request before the next REST request should be done)
     *   'ws.compression' => string, (Enables a specific one, defaults to zlib-stream, which is currently the only available compression)
     *   'ws.encoding' => string, (use a specific websocket encoding, JSON or ETF (if suggested package installed), recommended is JSON for now)
     *   'ws.disabledEvents' => string[], (disables specific websocket events (e.g. TYPING_START), only disable websocket events if you know what they do)
     *   'ws.largeThreshold' => int, (50-250, members threshold after which guilds gets counted as large, defaults to 250)
     *   'ws.presence' => array (the presence to send on WS connect, see https://discordapp.com/developers/docs/topics/gateway#gateway-status-update)
     * )
     * </pre>
     *
     * @param array                            $options  Any client options.
     * @param \React\EventLoop\LoopInterface   $loop     You can pass an event loop to the class, or it will automatically create one (you still need to make it run yourself).
     * @throws \Exception|\RuntimeException
     *
     * @see \CharlotteDunois\Yasmin\ClientEvents
     */
    function __construct(array $options = array(), ?\React\EventLoop\LoopInterface $loop = null) {
        if(\PHP_SAPI !== 'cli') {
            throw new \Exception('Yasmin can only be used in the CLI SAPI. Please use PHP CLI to run Yasmin.');
        }
        
        if(!empty($options)) {
            $this->validateClientOptions($options);
            $this->options = \array_merge($this->options, $options);
        }
        
        if(!$loop) {
            $loop = \React\EventLoop\Factory::create();
        }
        
        $this->loop = $loop;
        
        // ONLY use this if you know to 100% the consequences and know what you are doing
        if(!empty($options['internal.api.instance'])) {
            if(\is_string($options['internal.api.instance']) && !\class_exists($options['internal.api.instance'], true)) {
                throw new \RuntimeException('Custom API Manager class does not exist');
            }
            
            if(!\in_array('CharlotteDunois\\Yasmin\\HTTP\\APIManager', \class_parents($options['internal.api.instance']))) {
                throw new \RuntimeException('Custom API Manager does not extend Yasmin API Manager');
            }
            
            if(\is_string($options['internal.api.instance'])) {
                $api = $options['internal.api.instance'];
                $this->api = new $api($this);
            } else {
                $this->api = $options['internal.api.instance'];
            }
        } else {
            $this->api = new \CharlotteDunois\Yasmin\HTTP\APIManager($this);
        }
        
        // ONLY use this if you know to 100% the consequences and know what you are doing
        if(($options['internal.ws.disable'] ?? false) !== true) {
            // ONLY use this if you know to 100% the consequences and know what you are doing
            if(!empty($options['internal.ws.instance'])) {
                if(\is_string($options['internal.ws.instance']) && !\class_exists($options['internal.ws.instance'], true)) {
                    throw new \RuntimeException('Custom WS Manager class does not exist');
                }
                
                if(!\in_array('CharlotteDunois\\Yasmin\\WebSocket\\WSManager', \class_parents($options['internal.ws.instance']))) {
                    throw new \RuntimeException('Custom WS Manager does not extend Yasmin WS Manager');
                }
                
                if(\is_string($options['internal.ws.instance'])) {
                    $ws = $options['internal.ws.instance'];
                    $this->ws = new $ws($this);
                } else {
                    $this->ws = $options['internal.ws.instance'];
                }
            } else {
                $this->ws = new \CharlotteDunois\Yasmin\WebSocket\WSManager($this);
            }
        }
        
        $this->checkOptionsStorages();
        
        $this->channels = new $this->options['internal.storages.channels']($this);
        $this->emojis = new $this->options['internal.storages.emojis']($this);
        $this->guilds = new $this->options['internal.storages.guilds']($this);
        $this->presences = new $this->options['internal.storages.presences']($this);
        $this->users = new $this->options['internal.storages.users']($this);
        
        $this->registerUtils();
    }
    
    /**
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        $props = array('channels', 'emojis', 'guilds', 'presences', 'users', 'user');
        
        if(\in_array($name, $props)) {
            return $this->$name;
        }
        
        throw new \RuntimeException('Unknown property '.\get_class($this).'::$'.$name);
    }
    
    /**
     * Serializes the class.
     * @return string
     */
    function serialize() {
        $vars = \get_object_vars($this);
        
        unset($vars['loop'], $vars['ws'], $vars['api'], $vars['timers'],
                $vars['onceListeners'], $vars['listeners']);
        
        return \serialize($vars);
    }
    
    /**
     * Unserializes the class and re-registers utils. Automatically creates an event loop.
     * @param string $vars
     * @throws \RuntimeException
     */
    function unserialize($vars) {
        \CharlotteDunois\Yasmin\Models\ClientBase::$serializeClient = $this;
        $this->loop = \React\EventLoop\Factory::create();
        
        $vars = \unserialize($vars);
        
        foreach($vars as $name => $val) {
            $this->$name = $val;
        }
        
        if(!empty($this->options['internal.api.instance'])) {
            if(\is_string($this->options['internal.api.instance']) && !\class_exists($this->options['internal.api.instance'], true)) {
                throw new \RuntimeException('Custom API Manager class does not exist');
            }
            
            if(!\in_array('CharlotteDunois\\Yasmin\\HTTP\\APIManager', \class_parents($this->options['internal.api.instance']))) {
                throw new \RuntimeException('Custom API Manager does not extend Yasmin API Manager');
            }
            
            if(\is_string($this->options['internal.api.instance'])) {
                $api = $this->options['internal.api.instance'];
                $this->api = new $api($this);
            } else {
                $this->api = $this->options['internal.api.instance'];
            }
        } else {
            $this->api = new \CharlotteDunois\Yasmin\HTTP\APIManager($this);
        }
        
        foreach($this->utils as $name) {
            if(\method_exists($name, 'setLoop')) {
                $name::setLoop($this->loop);
            }
        }
    }
    
    /**
     * You don't need to know.
     * @return \CharlotteDunois\Yasmin\HTTP\APIManager
     * @internal
     */
    function apimanager() {
        return $this->api;
    }
    
    /**
     * You don't need to know.
     * @return \CharlotteDunois\Yasmin\WebSocket\WSManager
     * @internal
     */
    function wsmanager() {
        return $this->ws;
    }
    
    /**
     * Get the React Event Loop that is stored in this class.
     * @return \React\EventLoop\LoopInterface
     */
    function getLoop() {
        return $this->loop;
    }
    
    /**
     * Get a specific option, or the default value.
     * @param string  $name
     * @param mixed   $default
     * @return mixed
     */
    function getOption($name, $default = null) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    /**
     * Gets the average ping. Or NAN.
     * @return int|float
     */
    function getPing() {
        $cpings = \count($this->pings);
        if($cpings === 0) {
            return \NAN;
        }
        
        return ((int) \ceil(\array_sum($this->pings) / $cpings));
    }
    
    /**
     * Returns the WS status.
     * @return int
     */
    function getWSstatus() {
        return $this->ws->status;
    }
    
    /**
     * Login into Discord. Opens a WebSocket Gateway connection. Resolves once a WebSocket connection has been successfully established (does not mean the client is ready).
     * @param string $token  Your token.
     * @param bool   $force  Forces the client to get the gateway address from Discord.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \RuntimeException
     */
    function login(string $token, bool $force = false) {
        $token = \trim($token);
        
        if(empty($token)) {
            throw new \RuntimeException('Token can not be empty');
        }
        
        $this->token = $token;
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($force) {
            if($this->ws === null) {
                return $resolve();
            }
            
            if($this->gateway && !$force) {
                $gateway = \React\Promise\resolve(array('url' => $this->gateway));
            } elseif($this->gateway) {
                $gateway = $this->api->getGateway();
            } else {
                $gateway = $this->api->getGatewaySync();
            }
            
            $gateway->then(function ($url) {
                $this->gateway = $url['url'];
                
                $wsquery = \CharlotteDunois\Yasmin\WebSocket\WSManager::WS;
                $encoding = $this->getOption('ws.encoding');
                
                if(!empty($encoding) && \is_string($encoding)) {
                    $wsquery['encoding'] = $encoding;
                }
                
                return $this->ws->connect($url['url'], $wsquery);
            })->done($resolve, function ($error) use ($reject) {
                $this->api->clear();
                $this->ws->destroy();
                
                $this->cancelTimers();
                $this->destroyUtils();
                
                $this->emit('error', $error);
                $reject($error);
            });
        }));
    }
    
    /**
     * Cleanly logs out of Discord.
     * @param bool  $destroyUtils  Stop timers of utils which have an instance of the event loop.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function destroy(bool $destroyUtils = true) {
        return (new \React\Promise\Promise(function (callable $resolve) use ($destroyUtils) {
            $this->api->clear();
            
            if($this->ws !== null) {
                $this->ws->destroy();
            }
            
            $this->cancelTimers();
            
            if($destroyUtils) {
                $this->destroyUtils();
            }
            
            $resolve();
        }));
    }
    
    /**
     * Creates a new guild. Resolves with an instance of Guild. Options is as following, everything is optional unless specified:
     *
     * <pre>
     * array(
     *   'name' => string, (required)
     *   'region' => \CharlotteDunois\Yasmin\Models\VoiceRegion|string, (required)
     *   'icon' => string, (an URL, a filepath or data)
     *   'verificationLevel' => int, (0-4)
     *   'defaultMessageNotifications' => int, (0 or 1)
     *   'explicitContentFilter' => int, (0-2)
     *   'roles' => array, (an array of role arrays*)
     *   'channels' => array (an array of channel arrays**)
     *
     *     * array( // role array
     *     *   'name' => string, (required)
     *     *   'permissions' => \CharlotteDunois\Yasmin\Models\Permissions|int,
     *     *   'color' => int|string,
     *     *   'hoist' => bool,
     *     *   'mentionable' => bool
     *     * )
     *
     *     ** array( // channel array
     *     **   'name' => string, (required)
     *     **   'type' => 'text'|'voice', (category is not supported by the API, defaults to 'text')
     *     **   'bitrate' => int, (only for voice channels)
     *     **   'userLimit' => int, (only for voice channels, 0 = unlimited)
     *     **   'permissionOverwrites' => array, (an array of permission overwrite arrays***)
     *     **   'nsfw' => bool (only for text channels)
     *     ** )
     *
     *     *** array( // overwrite array, all required
     *     ***   'id' => \CharlotteDunois\Yasmin\Models\User|string, (string = user ID or role name (of above role array!))
     *     ***   'allow' => \CharlotteDunois\Yasmin\Models\Permissions|int,
     *     ***   'deny' => \CharlotteDunois\Yasmin\Models\Permissions|int
     *     *** )
     * )
     * </pre>
     *
     * @param array  $options
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Models\Guild
     */
    function createGuild(array $options) {
        if(empty($options['name'])) {
            throw new \InvalidArgumentException('Guild name can not be empty');
        }
        
        if(empty($options['region'])) {
            throw new \InvalidArgumentException('Guild region can not be empty');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options) {
            $data = array(
                'name' => $options['name'],
                'region' => ($options['region'] instanceof \CharlotteDunois\Yasmin\Models\VoiceRegion ? $options['region']->id : $options['region']),
                'verification_level' => ((int) ($options['verificationLevel'] ?? 0)),
                'default_message_notifications' => ((int) ($options['defaultMessageNotifications'] ?? 0)),
                'explicit_content_filter' => ((int) ($options['explicitContentFilter'] ?? 0)),
                'roles' => array(
                    array(
                        'id' => 0,
                        'name' => '@everyone',
                        'permissions' => 0,
                        'color' => 0,
                        'hoist' => false,
                        'mentionable' => false
                    )
                ),
                'channels' => array()
            );
            
            $rolemap = array(
                '@everyone' => 0
            );
            $roleint = 1;
            
            if(!empty($options['roles'])) {
                foreach($options['roles'] as $role) {
                    $role = array(
                        'id' => $roleint,
                        'name' => ((string) $role['name']),
                        'permissions' => ($role['permissions'] ?? 0),
                        'color' => (!empty($role['color']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor($role['color']) : 0),
                        'hoist' => ((bool) ($role['hoist'] ?? false)),
                        'mentionable' => ((bool) ($role['mentionable'] ?? false))
                    );
                    
                    if($role['name'] === '@everyone') {
                        $data['roles'][0] = $data['roles'];
                    } else {
                        $data['roles'][] = $role;
                        $rolemap[$role['name']] = $roleint++;
                    }
                }
            }
            
            if(!empty($options['channels'])) {
                foreach($options['channels'] as $channel) {
                    $cdata = array(
                        'name' => ((string) $channel['name']),
                        'type' => (\CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES[($channel['type'] ?? 'text')] ?? 0),
                    );
                    
                    if(isset($channel['bitrate'])) {
                        $cdata['bitrate'] = (int) $channel['bitrate'];
                    }
                    
                    if(isset($channel['userLimit'])) {
                        $cdata['user_limit'] = $channel['userLimit'];
                    }
                    
                    if(isset($channel['permissionOverwrites'])) {
                        $overwrites = array();
                        
                        foreach($channel['permissionOverwrites'] as $overwrite) {
                            $id = ($overwrite['id'] instanceof \CharlotteDunois\Yasmin\Models\User ? $overwrite['id']->id : ($rolemap[$overwrite['id']] ?? $overwrite['id']));
                            
                            $overwrites[] = array(
                                'id' => $id,
                                'type' => (isset($rolemap[$overwrite['id']]) ? 'role' : 'member'),
                                'allow' => ($overwrite['allow'] ?? 0),
                                'deny' => ($overwrite['deny'] ?? 0)
                            );
                        }
                        
                        $cdata['permission_overwrites'] = $overwrites;
                    }
                    
                    if(isset($channel['parent'])) {
                        $cdata['parent_id'] = ($channel['parent'] instanceof \CharlotteDunois\Yasmin\Models\CategoryChannel ? $channel['parent']->id : $channel['parent']);
                    }
                    
                    if(isset($channel['nsfw'])) {
                        $cdata['nsfw'] = $channel['nsfw'];
                    }
                    
                    $data['channels'][] = $cdata;
                }
            }
            
            if(!empty($options['icon'])) {
                $pr = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($options['icon'])->then(function ($icon) use (&$data) {
                    $data['icon'] = $icon;
                });
            } else {
                $pr = \React\Promise\resolve(null);
            }
            
            $pr->then(function () use (&$data) {
                return $this->api->endpoints->guild->createGuild($data)->then(function ($gdata) {
                    return $this->guilds->factory($gdata);
                });
            })->done($resolve, $reject);
        }));
    }
    
    /**
     * Obtains the OAuth Application of the bot from Discord.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\OAuthApplication
     */
    function fetchApplication() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->api->endpoints->getCurrentApplication()->done(function ($data) use ($resolve) {
                $app = new \CharlotteDunois\Yasmin\Models\OAuthApplication($this, $data);
                $resolve($app);
            }, $reject);
        }));
    }
    
    /**
     * Obtains an invite from Discord. Resolves with an instance of Invite.
     * @param string  $invite  The invite code or an invite URL.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Invite
     */
    function fetchInvite(string $invite) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($invite) {
            \preg_match('/discord(?:app\.com\/invite|\.gg)\/([\w-]{2,255})/i', $invite, $matches);
            if(!empty($matches[1])) {
                $invite = $matches[1];
            }
            
            $this->api->endpoints->invite->getInvite($invite)->done(function ($data) use ($resolve) {
                $invite = new \CharlotteDunois\Yasmin\Models\Invite($this, $data);
                $resolve($invite);
            }, $reject);
        }));
    }
    
    /**
     * Fetches an User from the API. Resolves with an User.
     * @param string  $userid  The User ID to fetch.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\User
     */
    function fetchUser(string $userid) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use  ($userid) {
            if($this->users->has($userid)) {
                return $resolve($this->users->get($userid));
            }
            
            $this->api->endpoints->user->getUser($userid)->then(function ($user) use ($resolve) {
                $user = $this->users->factory($user, true);
                $resolve($user);
            }, $reject);
        }));
    }
    
    /**
     * Obtains the available voice regions from Discord. Resolves with a Collection of Voice Region instances, mapped by their ID.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\VoiceRegion
     */
    function fetchVoiceRegions() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->api->endpoints->voice->listVoiceRegions()->done(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $region) {
                    $voice = new \CharlotteDunois\Yasmin\Models\VoiceRegion($this, $region);
                    $collect->set($voice->id, $voice);
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Fetches a webhook from Discord. Resolves with an instance of Webhook.
     * @param string       $id
     * @param string|null  $token
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function fetchWebhook(string $id, ?string $token = null) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($id, $token) {
            $method = (!empty($token) ? 'getWebhookToken' : 'getWebhook');
            
            $this->api->endpoints->webhook->$method($id, $token)->done(function ($data) use ($resolve) {
                $hook = new \CharlotteDunois\Yasmin\Models\Webhook($this, $data);
                $resolve($hook);
            }, $reject);
        }));
    }
    
    /**
     * Generates a link that can be used to invite the bot to a guild. Resolves with a string.
     * @param string|int  ...$permissions
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    function generateOAuthInvite(...$permissions) {
        $perm = new \CharlotteDunois\Yasmin\Models\Permissions();
        if(!empty($permissions)) {
            $perm->add(...$permissions);
        }
        
        return $this->fetchApplication()->then(function ($app) use ($perm) {
            return 'https://discordapp.com/oauth2/authorize?client_id='.$app->id.'&permissions='.$perm->bitfield.'&scope=bot';
        });
    }
    
    /**
     * Adds a "client-dependant" timer. The timer gets automatically cancelled on destroy. The callback can only accept one argument, the client.
     * @param float|int  $timeout
     * @param callable   $callback
     * @return \React\EventLoop\Timer\Timer
     */
    function addTimer($timeout, callable $callback) {
        $timer = $this->loop->addTimer($timeout, function () use ($callback, &$timer) {
            $callback($this);
            $this->cancelTimer($timer);
        });
        
        $this->timers[] = $timer;
        return $timer;
    }
    
    /**
     * Adds a "client-dependant" periodic timer. The timer gets automatically cancelled on destroy. The callback can only accept one argument, the client.
     * @param float|int  $interval
     * @param callable   $callback
     * @return \React\EventLoop\Timer\Timer
     */
    function addPeriodicTimer($interval, callable $callback) {
        $timer = $this->loop->addPeriodicTimer($interval, function () use ($callback) {
            $callback($this);
        });
        
        $this->timers[] = $timer;
        return $timer;
    }
    
    /**
     * Cancels a timer.
     * @param \React\EventLoop\Timer\Timer  $timer
     * @return bool
     */
    function cancelTimer(\React\EventLoop\Timer\Timer $timer) {
        $timer->cancel();
        
        $key = \array_search($timer, $this->timers, true);
        if($key !== false) {
            unset($this->timers[$key]);
        }
        
        return true;
    }
    
    /**
     * Cancels all timers.
     */
    function cancelTimers() {
        foreach($this->timers as $key => $timer) {
            $timer->cancel();
            unset($this->timers[$key]);
        }
    }
    
    /**
     * Make an instance of {ClientUser} and store it.
     * @internal
     */
    function setClientUser(array $user) {
        $this->user = new \CharlotteDunois\Yasmin\Models\ClientUser($this, $user);
        $this->users->set($this->user->id, $this->user);
    }
    
    /**
     * Returns an array of classes with are registered as Util.
     * @return string[]
     */
    function getUtils() {
        return $this->utils;
    }
    
    /**
     * Registers an Util, if it has a setLoop method. All methods used need to be static.
     * It will set the event loop through <code>setLoop</code> and on destroy will call <code>destroy</code>.
     * @param string $name
     */
    function registerUtil(string $name) {
        if(\method_exists($name, 'setLoop')) {
            $name::setLoop($this->loop);
            $this->utils[] = $name;
        }
    }
    
    /**
     * Destroys an Util and calls <code>destroy</code> (requires that it is registered as such).
     * @param string $name
     */
    function destroyUtil(string $name) {
        $pos = \array_search($name, $this->utils, true);
        if($pos !== false) {
            if(\method_exists($name, 'destroy')) {
                $name::destroy();
            }
            
            unset($this->utils[$pos]);
        }
    }
    
    /**
     * Registers Utils which have a setLoop method.
     * @internal
     */
    function registerUtils() {
        $utils = \glob(__DIR__.'/Utils/*.php');
        foreach($utils as $util) {
            $parts = \explode('/', \str_replace('\\', '/', $util));
            $name = \substr(\array_pop($parts), 0, -4);
            $fqn = '\\CharlotteDunois\\Yasmin\\Utils\\'.$name;
            
            if(\method_exists($fqn, 'setLoop')) {
                $fqn::setLoop($this->loop);
                $this->utils[] = $fqn;
            }
        }
    }
    
    /**
     * Destroys or stops all timers from Utils (requires that they are registered as such).
     * @internal
     */
    function destroyUtils() {
        foreach($this->utils as $util) {
            if(\method_exists($util, 'destroy')) {
                $util::destroy();
            }
        }
    }
    
    /**
     * Emits an error for an unhandled promise rejection.
     * @internal
     */
    function handlePromiseRejection($error) {
        $this->emit('error', $error);
    }
    
    /**
     * Validates the passed client options.
     * @param array
     * @throws \InvalidArgumentException
     */
    protected function validateClientOptions(array $options) {
        $validator = \CharlotteDunois\Validation\Validator::make($options, array(
            'disableClones' => 'boolean|array:string',
            'disableEveryone' => 'boolean',
            'fetchAllMembers' => 'boolean',
            'messageCacheLifetime' => 'integer|min:0',
            'messageSweepInterval' => 'integer|min:0',
            'shardID' => 'integer|min:0',
            'shardCount' => 'integer|min:1',
            'userSweepInterval' => 'integer|min:0',
            'http.restTimeOffset' => 'integer',
            'ws.compression' => 'string',
            'ws.disabledEvents' => 'array:string',
            'ws.encoding' => 'string',
            'ws.largeThreshold' => 'integer|min:50|max:250',
            'ws.presence' => 'array',
            'internal.storages.channels' => 'string',
            'internal.storages.emojis' => 'string',
            'internal.storages.guilds' => 'string',
            'internal.storages.messages' => 'string',
            'internal.storages.members' => 'string',
            'internal.storages.presences' => 'string',
            'internal.storages.roles' => 'string',
            'internal.storages.users' => 'string'
        ));
        
        if($validator->fails()) {
            $errors = $validator->errors();
            
            $name = \array_keys($errors)[0];
            $error = $errors[$name];
            
            throw new \InvalidArgumentException('Client Option '.$name.' '.\lcfirst($error));
        }
    }
    
    /**
     * Validates the passed client options storages.
     * @throws \RuntimeException
     */
    protected function checkOptionsStorages() {
        $storages = array(
            'channels' => '\\CharlotteDunois\\Yasmin\\Models\\ChannelStorage',
            'emojis' => '\\CharlotteDunois\\Yasmin\\Models\\EmojiStorage',
            'guilds' => '\\CharlotteDunois\\Yasmin\\Models\\GuildStorage',
            'messages' => '\\CharlotteDunois\\Yasmin\\Models\\MessageStorage',
            'members' => '\\CharlotteDunois\\Yasmin\\Models\\GuildMemberStorage',
            'presences' => '\\CharlotteDunois\\Yasmin\\Models\\PresenceStorage',
            'roles' => '\\CharlotteDunois\\Yasmin\\Models\\RoleStorage',
            'users' => '\\CharlotteDunois\\Yasmin\\Models\\UserStorage'
        );
        
        foreach($storages as $name => $base) {
            if(!empty($this->options['internal.storages.'.$name])) {
                if(!\class_exists($this->options['internal.storages.'.$name], true)) {
                    throw new \RuntimeException('Custom Storage class for "'.$name.'" does not exist');
                }
                
                if(!\in_array('CharlotteDunois\\Yasmin\\Interfaces\\StorageInterface', \class_implements($this->options['internal.storages.'.$name]))) {
                    throw new \RuntimeException('Custom Storage class for "'.$name.'" does not implement StorageInterface');
                }
            } else {
                $this->options['internal.storages.'.$name] = $base;
            }
        }
    }
}

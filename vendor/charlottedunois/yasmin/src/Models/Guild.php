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
 * Represents a guild. It's recommended to see if a guild is available before performing operations or reading data from it.
 *
 * @property string                                                         $id                           The guild ID.
 * @property bool                                                           $available                    Whether the guild is available.
 * @property string                                                         $name                         The guild name.
 * @property int                                                            $createdTimestamp             The timestamp when this guild was created.
 * @property string|null                                                    $icon                         The guild icon hash, or null.
 * @property string|null                                                    $splash                       The guild splash hash, or null.
 * @property string                                                         $ownerID                      The ID of the owner.
 * @property bool                                                           $large                        Whether the guild is considered large.
 * @property int                                                            $memberCount                  How many members the guild has.
 * @property \CharlotteDunois\Yasmin\Models\ChannelStorage                  $channels                     Holds a guild's channels, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Models\EmojiStorage                    $emojis                       Holds a guild's emojis, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Models\GuildMemberStorage              $members                      Holds a guild's cached members, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Models\RoleStorage                     $roles                        Holds a guild's roles, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Models\PresenceStorage                 $presences                    Holds a guild's presences of members, mapped by user ID.
 * @property string                                                         $defaultMessageNotifications  The type of message that should notify you. ({@see Guild::DEFAULT_MESSAGE_NOTIFICATIONS})
 * @property string                                                         $explicitContentFilter        The explicit content filter level of the guild. ({@see Guild::EXPLICIT_CONTENT_FILTER})
 * @property string                                                         $region                       The region the guild is located in.
 * @property string                                                         $verificationLevel            The verification level of the guild. ({@see Guild::VERIFICATION_LEVEL})
 * @property string|null                                                    $systemChannelID              The ID of the system channel, or null.
 * @property string|null                                                    $afkChannelID                 The ID of the afk channel, or null.
 * @property int|null                                                       $afkTimeout                   The time in seconds before an user is counted as "away from keyboard".
 * @property string[]                                                       $features                     An array of guild features.
 * @property string                                                         $mfaLevel                     The required MFA level for the guild. ({@see Guild::MFA_LEVEL})
 * @property string|null                                                    $applicationID                Application ID of the guild creator, if it is bot-created.
 * @property bool                                                           $embedEnabled                 Whether the guild is embeddable or not (e.g. widget).
 * @property string|null                                                    $embedChannelID               The ID of the embed channel, or null.
 * @property bool                                                           $widgetEnabled                Whether the guild widget is enabled or not.
 * @property string|null                                                    $widgetChannelID              The ID of the widget channel, or null.
 *
 * @property \CharlotteDunois\Yasmin\Models\VoiceChannel|null               $afkChannel                   The guild's afk channel, or null.
 * @property \DateTime                                                      $createdAt                    The DateTime instance of createdTimestamp.
 * @property \CharlotteDunois\Yasmin\Models\Role                            $defaultRole                  The guild's default role.
 * @property \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface|null  $embedChannel                 The guild's embed channel, or null.
 * @property \CharlotteDunois\Yasmin\Models\GuildMember                     $me                           The guild member of the client user.
 * @property string                                                         $nameAcronym                  The acronym that shows up in place of a guild icon.
 * @property \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface|null  $systemChannel                The guild's system channel, or null.
 * @property bool                                                           $vanityURL                    Whether the guild has a vanity invite url.
 * @property bool                                                           $verified                     Whether the guild is verified.
 * @property \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface|null  $widgetChannel                The guild's widget channel, or null.
 */
class Guild extends ClientBase {
    /**
     * Guild default message notifications.
     * @var array
     * @source
     */
    const DEFAULT_MESSAGE_NOTIFICATIONS = array(
        0 => 'EVERYTHING',
        1 => 'ONLY_MENTIONS'
    );
    
    /**
     * Guild explicit content filter.
     * @var array
     * @source
     */
    const EXPLICIT_CONTENT_FILTER = array(
        0 => 'DISABLED',
        1 => 'MEMBERS_WITHOUT_ROLES',
        2 => 'ALL_MEMBERS'
    );
    
    /**
     * Guild MFA level.
     * @var array
     * @source
     */
    const MFA_LEVEL = array(
        0 => 'NONE',
        1 => 'ELEVATED'
    );
    
    /**
     * Guild verification level.
     * @var array
     * @source
     */
    const VERIFICATION_LEVEL = array(
        0 => 'NONE',
        1 => 'LOW',
        2 => 'MEDIUM',
        3 => 'HIGH',
        4 => 'VERY_HIGH'
    );
    
    protected $channels;
    protected $emojis;
    protected $members;
    protected $presences;
    protected $roles;
    
    protected $id;
    protected $available;
    
    protected $name;
    protected $icon;
    protected $splash;
    protected $ownerID;
    protected $large;
    protected $memberCount = 0;
    
    protected $defaultMessageNotifications;
    protected $explicitContentFilter;
    protected $region;
    protected $verificationLevel;
    protected $systemChannelID;
    
    protected $afkChannelID;
    protected $afkTimeout;
    protected $features;
    protected $mfaLevel;
    protected $applicationID;
    
    protected $embedEnabled;
    protected $embedChannelID;
    protected $widgetEnabled;
    protected $widgetChannelID;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $guild) {
        parent::__construct($client);
        
        $this->client->guilds->set($guild['id'], $this);
        
        $channels = $this->client->getOption('internal.storages.channels');
        $emojis = $this->client->getOption('internal.storages.emojis');
        $members = $this->client->getOption('internal.storages.members');
        $presences = $this->client->getOption('internal.storages.presences');
        $roles = $this->client->getOption('internal.storages.roles');
        
        $this->channels = new $channels($client);
        $this->emojis = new $emojis($client, $this);
        $this->members = new $members($client, $this);
        $this->presences = new $presences($client);
        $this->roles = new $roles($client, $this);
        
        $this->id = $guild['id'];
        $this->available = (empty($guild['unavailable']));
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
        if($this->available) {
            $this->_patch($guild);
        }
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
        
        switch($name) {
            case 'afkChannel':
                return $this->channels->get($this->afkChannelID);
            break;
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'defaultRole':
                return $this->roles->get($this->id);
            break;
            case 'embedChannel':
                return $this->channels->get($this->embedChannelID);
            break;
            case 'me':
                return $this->members->get($this->client->user->id);
            break;
            case 'nameAcronym':
                \preg_match_all('/\w+/iu', $this->name, $matches);
                
                $name = '';
                foreach($matches[0] as $word) {
                    $name .= $word[0];
                }
                
                return \mb_strtoupper($name);
            break;
            case 'systemChannel':
                return $this->channels->get($this->systemChannelID);
            break;
            case 'vanityURL':
                return \in_array('VANITY_URL', $this->features);
            break;
            case 'verified':
                return \in_array('VERIFIED', $this->features);
            break;
            case 'widgetChannel':
                return $this->channels->get($this->widgetChannelID);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Adds the given user to the guild using the OAuth Access Token. Requires the CREATE_INSTANT_INVITE permission. Resolves with $this.
     *
     * Options are as following (all fields are optional):
     *
     * <pre>
     * array(
     *   'nick' => string, (the nickname for the user, requires MANAGE_NICKNAMES permissions)
     *   'roles' => array|\CharlotteDunois\Yasmin\Utils\Collection, (array or Collection of Role instances or role IDs, requires MANAGE_ROLES permission)
     *   'mute' => bool, (whether the user is muted, requires MUTE_MEMBERS permission)
     *   'deaf' => bool, (whether the user is deafened, requires DEAFEN_MEMBERS permission)
     * )
     * </pre>
     *
     * @param \CharlotteDunois\Yasmin\Models\User|string  $user         A guild member or User instance, or the user ID.
     * @param string                                      $accessToken  The OAuth Access Token for the given user.
     * @param array                                       $options      Any options.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function addMember($user, string $accessToken, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($user, $accessToken, $options) {
            if($user instanceof \CharlotteDunois\Yasmin\Models\User) {
                $user = $user->id;
            }
            
            $opts = array(
                'access_token' => $accessToken
            );
            
            if(!empty($options['nick'])) {
                $opts['nick'] = $options['nick'];
            }
            
            if(!empty($options['roles'])) {
                if($options['roles'] instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    $options['roles'] = $options['roles']->all();
                }
                
                $opts['roles'] = \array_values(\array_map(function ($role) {
                    if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
                        return $role->id;
                    }
                    
                    return $role;
                }, $options['roles']));
            }
            
            if(isset($options['mute'])) {
                $opts['mute'] = (bool) $options['mute'];
            }
            
            if(isset($options['deaf'])) {
                $opts['deaf'] = (bool) $options['deaf'];
            }
            
            $this->client->apimanager()->endpoints->guild->addGuildMember($this->id, $user, $opts)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Bans the given user. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|\CharlotteDunois\Yasmin\Models\User|string  $user     A guild member or User instance, or the user ID.
     * @param int                                                                                    $days     Number of days of messages to delete (0-7).
     * @param string                                                                                 $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function ban($user, int $days = 0, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($user, $days, $reason) {
            if($user instanceof \CharlotteDunois\Yasmin\Models\User || $user instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
                $user = $user->id;
            }
            
            $this->client->apimanager()->endpoints->guild->createGuildBan($this->id, $user, $days, $reason)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Creates a new channel in the guild. Resolves with an instance of GuildChannelInterface.
     *
     * Options are as following (all fields except name are optional):
     *
     * <pre>
     * array(
     *   'name' => string,
     *   'type' => 'category'|'text'|'voice', (defaults to 'text')
     *   'bitrate' => int, (only for voice channels)
     *   'userLimit' => int, (only for voice channels, 0 = unlimited)
     *   'permissionOverwrites' => \CharlotteDunois\Yasmin\Utils\Collection|array, (an array or Collection of PermissionOverwrite instances or permission overwrite arrays*)
     *   'parent' => \CharlotteDunois\Yasmin\Models\CategoryChannel|string, (string = channel ID)
     *   'nsfw' => bool (only for text channels)
     * )
     *
     *   *  array(
     *   *      'id' => string, (an user/member or role ID)
     *   *      'type' => 'member'|'role',
     *   *      'allow' => \CharlotteDunois\Yasmin\Models\Permissions|int,
     *   *      'deny' => \CharlotteDunois\Yasmin\Models\Permissions|int
     *   *  )
     * </pre>
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface
     */
    function createChannel(array $options, string $reason = '') {
        if(empty($options['name'])) {
            throw new \InvalidArgumentException('Channel name can not be empty');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options, $reason) {
            $data = array(
                'name' => $options['name'],
                'type' => (\CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES[($options['type'] ?? 'text')] ?? 0)
            );
            
            if(isset($options['bitrate'])) {
                $data['bitrate'] = (int) $options['bitrate'];
            }
            
            if(isset($options['userLimit'])) {
                $data['user_limit'] = $options['userLimit'];
            }
            
            if(isset($options['permissionOverwrites'])) {
                if($options['permissionOverwrites'] instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    $options['permissionOverwrites'] = $options['permissionOverwrites']->all();
                }
                
                $data['permission_overwrites'] = \array_values($options['permissionOverwrites']);
            }
            
            if(isset($options['parent'])) {
                $data['parent_id'] = ($options['parent'] instanceof \CharlotteDunois\Yasmin\Models\CategoryChannel ? $options['parent']->id : $options['parent']);
            }
            
            if(isset($options['nsfw'])) {
                $data['nsfw'] = $options['nsfw'];
            }
            
            $this->client->apimanager()->endpoints->guild->createGuildChannel($this->id, $data, $reason)->done(function ($data) use ($resolve) {
                $channel = $this->client->channels->factory($data, $this);
                $resolve($channel);
            }, $reject);
        }));
    }
    
    /**
     * Creates a new custom emoji in the guild. Resolves with an instance of Emoji.
     * @param string                                           $file   Filepath or URL, or file data.
     * @param string                                           $name
     * @param array|\CharlotteDunois\Yasmin\Utils\Collection   $roles  An array or Collection of Role instances or role IDs.
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Emoji
     */
    function createEmoji(string $file, string $name, $roles = array(), string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($file, $name, $roles, $reason) {
            \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($file)->done(function ($file) use ($name, $roles, $reason, $resolve, $reject) {
                if($roles instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    $roles = $roles->all();
                }
                
                $roles = \array_map(function ($role) {
                    if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
                        return $role->id;
                    }
                    
                    return $role;
                }, $roles);
                
                $options = array(
                    'name' => $name,
                    'image' => \CharlotteDunois\Yasmin\Utils\DataHelpers::makeBase64URI($file),
                    'roles' => $roles
                );
                
                $this->client->apimanager()->endpoints->emoji->createGuildEmoji($this->id, $options, $reason)->done(function ($data) use ($resolve) {
                    $emoji = $this->emojis->factory($data);
                    $resolve($emoji);
                }, $reject);
            }, $reject);
        }));
    }
    
    /**
     * Creates a new role in the guild. Resolves with an instance of Role.
     *
     * Options are as following (all are optional):
     *
     * <pre>
     * array(
     *   'name' => string,
     *   'permissions' => int|\CharlotteDunois\Yasmin\Models\Permissions,
     *   'color' => int|string,
     *   'hoist' => bool,
     *   'mentionable' => bool
     * )
     * </pre>
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Models\Role
     */
    function createRole(array $options, string $reason = '') {
        if(!empty($options['color'])) {
            $options['color'] = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor($options['color']);
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options, $reason) {
            $this->client->apimanager()->endpoints->guild->createGuildRole($this->id, $options, $reason)->done(function ($data) use ($resolve) {
                $role = $this->roles->factory($data);
                $resolve($role);
            }, $reject);
        }));
    }
    
    /**
     * Deletes the guild.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function delete() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->guild->deleteGuild($this->id)->done(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Edits the guild. Resolves with $this.
     *
     * Options are as following (at least one is required):
     *
     * <pre>
     * array(
     *   'name' => string,
     *   'region' => string,
     *   'verificationLevel' => int,
     *   'explicitContentFilter' => int,
     *   'defaultMessageNotifications' => int,
     *   'afkChannel' => string|\CharlotteDunois\Yasmin\Models\VoiceChannel|null,
     *   'afkTimeout' => int|null,
     *   'systemChannel' => string|\CharlotteDunois\Yasmin\Models\TextChannel|null,
     *   'owner' => string|\CharlotteDunois\Yasmin\Models\GuildMember,
     *   'icon' => string, (file path or URL, or data)
     *   'splash' => string, (file path or URL, or data)
     *   'region' => string|\CharlotteDunois\Yasmin\Models\VoiceRegion
     * )
     * </pre>
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function edit(array $options, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options, $reason) {
            $data = array();
            
            if(!empty($options['name'])) {
                $data['name'] = $options['name'];
            }
            
            if(!empty($options['region'])) {
                $data['region'] = $options['region'];
            }
            
            if(isset($options['verificationLevel'])) {
                $data['verification_level'] = (int) $options['verificationLevel'];
            }
            
            if(isset($options['verificationLevel'])) {
                $data['explicit_content_filter'] = (int) $options['explicitContentFilter'];
            }
            
            if(isset($options['defaultMessageNotifications'])) {
                $data['default_message_notifications'] = (int) $options['defaultMessageNotifications'];
            }
            
            if(\array_key_exists('afkChannel', $options)) {
                $data['afk_channel_id'] = ($options['afkChannel'] === null ? null : ($options['afkChannel'] instanceof \CharlotteDunois\Yasmin\Models\VoiceChannel ? $options['afkChannel']->id : $options['afkChannel']));
            }
            
            if(\array_key_exists('afkTimeout', $options)) {
                $data['afk_timeout'] = $options['afkTimeout'];
            }
            
            if(\array_key_exists('systemChannel', $options)) {
                $data['system_channel_id'] = ($options['systemChannel'] === null ? null : ($options['systemChannel'] instanceof \CharlotteDunois\Yasmin\Models\TextChannel ? $options['systemChannel']->id : $options['systemChannel']));
            }
            
            if(isset($options['owner'])) {
                $data['owner_id'] = ($options['owner'] instanceof \CharlotteDunois\Yasmin\Models\GuildMember ? $options['owner']->id : $options['owner']);
            }
            
            if(isset($options['region'])) {
                $data['region'] = ($options['region'] instanceof \CharlotteDunois\Yasmin\Models\VoiceRegion ? $options['region']->id : $options['region']);
            }
            
            $handleImg = function ($img) {
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeBase64URI($img);
            };
            
            $files = array(
                (isset($options['icon']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($options['icon'])->then($handleImg) : \React\Promise\resolve(null)),
                (isset($options['splash']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($options['splash'])->then($handleImg) : \React\Promise\resolve(null))
            );
            
            \React\Promise\all($files)->done(function ($files) use (&$data, $reason, $resolve, $reject) {
                if(\is_string($files[0])) {
                    $data['icon'] = $files[0];
                }
                
                if(\is_string($files[1])) {
                    $data['splash'] = $files[1];
                }
                
                $this->client->apimanager()->endpoints->guild->modifyGuild($this->id, $data, $reason)->done(function () use ($resolve) {
                    $resolve($this);
                }, $reject);
            }, $reject);
        }));
    }
    
    /**
     * Fetch audit log for the guild. Resolves with an instance of AuditLog.
     *
     * Options are as following (all are optional):
     *
     * <pre>
     * array(
     *   'before' => string|\CharlotteDunois\Yasmin\Models\AuditLogEntry, (string = Audit Log Entry ID)
     *   'after' => string|\CharlotteDunois\Yasmin\Models\AuditLogEntry, (string = Audit Log Entry ID)
     *   'limit' => int,
     *   'user' => string|\CharlotteDunois\Yasmin\Models\User,
     *   'type' => string|int
     * )
     * </pre>
     *
     * @param array  $options
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\AuditLog
     */
    function fetchAuditLog(array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options) {
            if(!empty($options['user'])) {
                $options['user'] = ($options['user'] instanceof \CharlotteDunois\Yasmin\Models\User ? $options['user']->id : $options['user']);
            }
            
            $this->client->apimanager()->endpoints->guild->getGuildAuditLog($this->id, $options)->done(function ($data) use ($resolve) {
                $audit = new \CharlotteDunois\Yasmin\Models\AuditLog($this->client, $this, $data);
                $resolve($audit);
            }, $reject);
        }));
    }
    
    /**
     * Fetch all bans of the guild. Resolves with a Collection of array('reason' => string|null, 'user' => User), mapped by the user ID.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function fetchBans() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->guild->getGuildBans($this->id)->done(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $ban) {
                    $user = $this->client->users->patch($ban['user']);
                    $collect->set($user->id, array(
                        'reason' => ($ban['reason'] ?? null),
                        'user' => $user
                    ));
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Fetches all invites of the guild. Resolves with a Collection of Invite instances, mapped by their code.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Invite
     */
    function fetchInvites() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->guild->getGuildInvites($this->id)->done(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $inv) {
                    $invite = new \CharlotteDunois\Yasmin\Models\Invite($this->client, $inv);
                    $collect->set($invite->code, $invite);
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Fetches a specific guild member. Resolves with an instance of GuildMember.
     * @param string  $userid  The ID of the guild member.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\GuildMember
     */
    function fetchMember(string $userid) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($userid) {
            if($this->members->has($userid) && ($this->members->get($userid) instanceof \CharlotteDunois\Yasmin\Models\GuildMember)) {
                return $resolve($this->members->get($userid));
            }
            
            $this->client->apimanager()->endpoints->guild->getGuildMember($this->id, $userid)->done(function ($data) use ($resolve) {
                $resolve($this->_addMember($data, true));
            }, $reject);
        }));
    }
    
    /**
     * Fetches all guild members. Resolves with $this.
     * @param string  $query  Limit fetch to members with similar usernames
     * @param int     $limit  Maximum number of members to request
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function fetchMembers(string $query = '', int $limit = 0) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($query, $limit) {
            if($this->members->count() === $this->memberCount) {
                $resolve($this);
                return;
            }
            
            $listener = function ($guild) use(&$listener, $resolve) {
                if($guild->id !== $this->id) {
                    return;
                }
                
                if($this->members->count() === $this->memberCount) {
                    $this->client->removeListener('guildMembersChunk', $listener);
                    $resolve($this);
                }
            };
            
            $this->client->on('guildMembersChunk', $listener);
            
            $this->client->wsmanager()->send(array(
                'op' => \CharlotteDunois\Yasmin\WebSocket\WSManager::OPCODES['REQUEST_GUILD_MEMBERS'],
                'd' => array(
                    'guild_id' => $this->id,
                    'query' => $query,
                    'limit' => $limit
                )
            ));
            
            $this->client->addTimer(120, function () use (&$listener, $reject) {
                if($this->members->count() < $this->memberCount) {
                    $this->client->removeListener('guildMembersChunk', $listener);
                    $reject(new \Exception('Members did not arrive in time'));
                }
            });
        }));
    }
    
    /**
     * Fetches the guild voice regions. Resolves with a Collection of Voice Region instances, mapped by their ID.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\VoiceRegion
     */
    function fetchVoiceRegions() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->guild->getGuildVoiceRegions($this->id)->done(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $region) {
                    $voice = new \CharlotteDunois\Yasmin\Models\VoiceRegion($this->client, $region);
                    $collect->set($voice->id, $voice);
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Fetches the guild's webhooks. Resolves with a Collection of Webhook instances, mapped by their ID.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function fetchWebhooks() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->webhook->getGuildsWebhooks($this->id)->done(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $web) {
                    $hook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $web);
                    $collect->set($hook->id, $hook);
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Returns the guild's icon URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getIconURL(string $format = 'png', ?int $size = null) {
        if($this->icon !== null) {
            return \CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['url'].\CharlotteDunois\Yasmin\HTTP\APIEndpoints::format(\CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['icons'], $this->id, $this->icon, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Returns the guild's splash URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getSplashURL(string $format = 'png', ?int $size = null) {
        if($this->splash !== null) {
            return \CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['url'].\CharlotteDunois\Yasmin\HTTP\APIEndpoints::format(\CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['splashes'], $this->id, $this->splash, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Returns the vanity invite. The guild must be partnered, i.e. have 'VANITY_URL' in guild features. Resolves with an instance of Invite.
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\Invite
     */
    function getVanityInvite() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->guild->getGuildVanityURL($this->id)->then(function ($data) {
                return $this->client->apimanager()->endpoints->invite->getInvite($data['code']);
            })->done(function ($data) use ($resolve) {
                $invite = new \CharlotteDunois\Yasmin\Models\Invite($this->client, $data);
                $resolve($invite);
            }, $reject);
        }));
    }
    
    /**
     * Leaves the guild.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function leave() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->user->leaveUserGuild($this->id)->done(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Prunes members from the guild based on how long they have been inactive. Resolves with an integer.
     * @param int     $days
     * @param bool    $dry
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function pruneMembers(int $days, bool $dry = false, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($days, $dry, $reason) {
            $method = ($dry ? 'getGuildPruneCount' : 'beginGuildPrune');
            $this->client->apimanager()->endpoints->guild->$method($this->id, $days, $reason)->done(function ($data) use ($resolve) {
                $resolve($data['pruned']);
            }, $reject);
        }));
    }
    
    /**
     * Edits the AFK channel of the guild. Resolves with $this.
     * @param string|\CharlotteDunois\Yasmin\Models\VoiceChannel|null  $channel
     * @param string                                                   $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setAFKChannel($channel, string $reason = '') {
        return $this->edit(array('afkChannel' => $channel), $reason);
    }
    
    /**
     * Edits the AFK timeout of the guild. Resolves with $this.
     * @param int|null $timeout
     * @param string   $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setAFKTimeout($timeout, string $reason = '') {
        return $this->edit(array('afkTimeout' => $timeout), $reason);
    }
    
    /**
     * Batch-updates the guild's channels positions. Channels is an array of <code>channel ID (string)|GuildChannelInterface => position (int)</code> pairs. Resolves with $this.
     * @param array   $channels
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setChannelPositions(array $channels, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($channels, $reason) {
            $options = array();
            
            foreach($channels as $chan => $position) {
                if($chan instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                    $chan = $chan->id;
                }
                
                $options[] = array('id' => $chan, 'position' => (int) $position);
            }
            
            $this->client->apimanager()->endpoints->guild->modifyGuildChannelPositions($this->id, $options, $reason)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Batch-updates the guild's roles positions. Roles is an array of <code>role ID (string)|Role => position (int)</code> pairs. Resolves with $this.
     * @param array   $roles
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setRolePositions(array $roles, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($roles, $reason) {
            $options = array();
            
            foreach($roles as $role => $position) {
                if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
                    $role = $role->id;
                }
                
                $options[] = array('id' => $role, 'position' => (int) $position);
            }
            
            $this->client->apimanager()->endpoints->guild->modifyGuildRolePositions($this->id, $options, $reason)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Edits the level of the explicit content filter. Resolves with $this.
     * @param int     $filter
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setExplicitContentFilter(int $filter, string $reason = '') {
        return $this->edit(array('explicitContentFilter' => $filter), $reason);
    }
    
    /**
     * Updates the guild icon. Resolves with $this.
     * @param string  $icon    A filepath or URL, or data.
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setIcon(string $icon, string $reason = '') {
        return $this->edit(array('icon' => $icon), $reason);
    }
    
    /**
     * Edits the name of the guild. Resolves with $this.
     * @param string  $name
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setName(string $name, string $reason = '') {
        return $this->edit(array('name' => $name), $reason);
    }
    
    /**
     * Sets a new owner for the guild. Resolves with $this.
     * @param string|\CharlotteDunois\Yasmin\Models\GuildMember  $owner
     * @param string                                             $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setOwner($owner, string $reason = '') {
        return $this->edit(array('owner' => $owner), $reason);
    }
    
    /**
     * Edits the region of the guild. Resolves with $this.
     * @param string|\CharlotteDunois\Yasmin\Models\VoiceRegion  $region
     * @param string                                             $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setRegion($region, string $reason = '') {
        return $this->edit(array('region' => $region), $reason);
    }
    
    /**
     * Updates the guild splash. Resolves with $this.
     * @param string  $splash  A filepath or URL, or data.
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setSplash(string $splash, string $reason = '') {
        return $this->edit(array('splash' => $splash), $reason);
    }
    
    /**
     * Edits the system channel of the guild. Resolves with $this.
     * @param string|\CharlotteDunois\Yasmin\Models\TextChannel|null  $channel
     * @param string                                                  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setSystemChannel($channel, string $reason = '') {
        return $this->edit(array('systemChannel' => $channel), $reason);
    }
    
    /**
     * Edits the verification level of the guild. Resolves with $this.
     * @param int     $level
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setVerificationLevel(int $level, string $reason = '') {
        return $this->edit(array('verificationLevel' => $level), $reason);
    }
    
    /**
     * Unbans the given user. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\User|string  $user     An User instance or the user ID.
     * @param string                                      $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function unban($user, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($user, $reason) {
            if($user instanceof \CharlotteDunois\Yasmin\Models\User) {
                $user = $user->id;
            }
            
            $this->client->apimanager()->endpoints->guild->removeGuildBan($this->id, $user, $reason)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * @internal
     */
    function _addMember(array $member, bool $initial = false) {
        $guildmember = $this->members->factory($member);
        
        if(!$initial) {
            $this->memberCount++;
        }
        
        return $guildmember;
    }
    
    /**
     * @internal
     */
    function _removeMember(string $userid) {
        if($this->members->has($userid)) {
            $member = $this->members->get($userid);
            $this->members->delete($userid);
            
            $this->memberCount--;
            return $member;
        }
        
        return null;
    }
    
    /**
     * @internal
     */
    function _patch(array $guild) {
        $this->available = (empty($guild['unavailable']));
        
        if($this->available === false) {
            return;
        }
        
        $this->name = $guild['name'];
        $this->icon = $guild['icon'];
        $this->splash = $guild['splash'];
        $this->ownerID = $guild['owner_id'];
        $this->large = (bool) ($guild['large'] ?? $this->large);
        $this->memberCount = $guild['member_count']  ?? $this->memberCount;
        
        $this->defaultMessageNotifications = self::DEFAULT_MESSAGE_NOTIFICATIONS[$guild['default_message_notifications']] ?? $this->defaultMessageNotifications;
        $this->explicitContentFilter = self::EXPLICIT_CONTENT_FILTER[$guild['explicit_content_filter']] ?? $this->explicitContentFilter;
        $this->region = $guild['region'];
        $this->verificationLevel = self::VERIFICATION_LEVEL[$guild['verification_level']] ?? $this->verificationLevel;
        $this->systemChannelID = $guild['system_channel_id'];
        
        $this->afkChannelID = $guild['afk_channel_id'];
        $this->afkTimeout = $guild['afk_timeout'];
        $this->features = $guild['features'];
        $this->mfaLevel = self::MFA_LEVEL[$guild['mfa_level']] ?? $this->mfaLevel;
        $this->applicationID = $guild['application_id'];
        
        $this->embedEnabled = (bool) ($guild['embed_enabled'] ?? $this->embedEnabled);
        $this->embedChannelID = $guild['embed_channel_id'] ?? $this->embedChannelID;
        $this->widgetEnabled = (bool) ($guild['widget_enabled'] ?? $this->widgetEnabled);
        $this->widgetChannelID = $guild['widget_channel_id'] ?? $this->widgetChannelID;
        
        foreach($guild['roles'] as $role) {
            $this->roles->factory($role);
        }
        
        foreach($guild['emojis'] as $emoji) {
            $this->emojis->factory($emoji);
        }
        
        if(!empty($guild['channels'])) {
            foreach($guild['channels'] as $channel) {
                $this->channels->factory($channel, $this);
            }
        }
        
        if(!empty($guild['members'])) {
            foreach($guild['members'] as $member) {
                $this->_addMember($member, true);
            }
        }
        
        if(!empty($guild['presences'])) {
            foreach($guild['presences'] as $presence) {
                $this->presences->factory($presence);
            }
        }
        
        if(!empty($guild['voice_states'])) {
            foreach($guild['voice_states'] as $state) {
                $member = $this->members->get($state['user_id']);
                if($member) {
                    $member->_setVoiceState($state);
                }
            }
        }
    }
}

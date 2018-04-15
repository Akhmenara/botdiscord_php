<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP\Endpoints;

/**
 * Handles the API endpoints "Voice".
 * @internal
 */
final class Voice {
    /**
     * Endpoints Voice.
     * @var array
     */
    const ENDPOINTS = array(
        'regions' => 'voice/regions'
    );
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager $api
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api) {
        $this->api = $api;
    }
    
    function listVoiceRegions() {
        $url = self::ENDPOINTS['regions'];
        return $this->api->makeRequest('GET', $url, array());
    }
}

<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Compression;

/**
 * Handles WS compression.
 * @internal
 */
class ZlibStream implements \CharlotteDunois\Yasmin\Interfaces\WSCompressionInterface {
    protected $context;
    
    /**
     * Returns compression name (for gateway query string).
     * @return string
     */
    function getName(): string {
        return 'zlib-stream';
    }
    
    /**
     * Returns a boolean for the OP code 2 IDENTIFY packet 'compress' parameter. The parameter is for payload compression.
     * @return bool
     */
    function isPayloadCompression(): bool {
        return false;
    }
    
    /**
     * Initializes the context.
     */
    function init(): void {
        $this->context = \inflate_init(\ZLIB_ENCODING_DEFLATE);
        if(!$this->context) {
            throw new \Exception('Unable to initialize Zlib Inflate');
        }
    }
    
    /**
     * Destroys the context.
     */
    function destroy(): void {
        $this->context = null;
    }
    
    /**
     * Checks if the system supports it.
     * @throws \Exception
     */
    static function supported(): void {
        if(!\function_exists('\inflate_init')) {
            throw new \RuntimeException('Zlib is not supported by this PHP installation');
        }
    }
    
    /**
     * Decompresses data.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decompress(string $data): string {
        if(!$this->context) {
            throw new \BadMethodCallException('No inflate context initialized');
        }
        
        $uncompressed = \inflate_add($this->context, $data);
        if($uncompressed === false) {
            throw new \InvalidArgumentException('The inflate context was unable to decompress the data');
        }
        
        return $uncompressed;
    }
}

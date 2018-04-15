<?php
/**
 * EventEmitter
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/EventEmitter/blob/master/LICENSE
*/

namespace CharlotteDunois\Events;

interface EventEmitterInterface {
    /**
     * Attach a listener to an event.
     * @param string    $event
     * @param callable  $listener
     * @return $this
     * @throws \InvalidArgumentException
     */
    function on($event, callable $listener);
    
    /**
     * Attach a listener to an event, for exactly once.
     * @param string    $event
     * @param callable  $listener
     * @return $this
     * @throws \InvalidArgumentException
     */
    function once($event, callable $listener);
    
    /**
     * Remove specified listener from an event.
     * @param string    $event
     * @param callable  $listener
     * @return $this
     * @throws \InvalidArgumentException
     */
    function removeListener($event, callable $listener);
    
    /**
     * Remove all listeners from an event (or all listeners).
     * @param string|null  $event
     * @return $this
     */
    function removeAllListeners($event = null);
    
    /**
     * Get listeners for a specific events, or all listeners.
     * @param string|null  $event
     * @return array
     */
    function listeners($event = null);
    
    /**
     * Emits an event, catching all exceptions and emitting an error event for these exceptions.
     * @param string  $event
     * @param mixed   $arguments
     * @throws \InvalidArgumentException
     */
    function emit($event, ...$arguments);
}

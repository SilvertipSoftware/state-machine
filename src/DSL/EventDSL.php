<?php namespace SilvertipSoftware\StateMachine\DSL;

class EventDSL {
    
    private $event;

    public function __construct( $event ) {
        $this->event = $event;
    }

    public function transitions( $from, $to, $options = [] ) {
        return $this->event->transitions( $from, $to, $options );
    }
}
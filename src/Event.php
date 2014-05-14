<?php namespace SilvertipSoftware\StateMachine;

class Event {
    public $label;
    protected $transitions = [];
    protected $options;

    public function __construct( $label, $options = [] ) {
        $this->label = $label;
        $this->options = $options;
        if ( !isset( $options['before'] ) ) {
            $this->options['before'] = [];
        }
        if ( !isset( $options['after'] ) )
            $this->options['after'] = [];
    }

    public function transitions( $fromArray, $to, $options = [] ) {
        foreach ( (array)$fromArray as $from ) {
            $this->transitions[] = new Transition( $from, $to, $options );
        }
        return $this;
    }

    public function canBeTriggered( $obj, $fromState, $args ) {
        return ( $this->firstAvailableTransition($obj, $fromState, $args) != null );
    }

    public function firstAvailableTransition( $obj, $fromState, $args ) {
        $possibleTransitions = $this->getPossibleTransitions( $fromState );
        foreach ( $possibleTransitions as $transition ) {
            if ( call_user_func_array([$transition,'canExecute'], array_merge( [$obj], $args ) ) ) 
                return $transition;
        }
        return null;        
    }

    public function fireBeforeCallbacks( $obj, $args = [] ) {
        $this->invokeCallbacks( $this->options['before'], $obj, $args );
    }

    public function fireAfterCallbacks( $obj, $args = [] ) {
        $this->invokeCallbacks( $this->options['after'], $obj, $args );
    }

    protected function getPossibleTransitions( $startingState ) {
        return array_filter( $this->transitions, function($transition) use ($startingState) {
            return $transition->from == $startingState->label;
        });        
    }

    protected function invokeCallbacks( $callback, $obj, $args ) {
        if ( is_string( $callback ) ) {
            call_user_func_array( [$obj,$callback], $args );
            return true;
        }
        elseif ( $callback instanceof \Closure ) {
            call_user_func_array( $callback, array_merge([$obj], $args) );
            return true;
        }
        elseif ( is_array($callback) ) {
            foreach ( $callback as $cb ) {
                $this->invokeCallbacks( $cb, $obj, $args );
            }
            return true;
        }
        return false;
    }
}
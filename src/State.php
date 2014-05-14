<?php namespace SilvertipSoftware\StateMachine;

class State {
    public $label;
    public $value;
    protected $options;

    public function __construct( $label, $options ) {
        $this->label = $label;
        $this->value = ( isset($options['value']) ? $options['value'] : $label );
        $this->options = $options;
        if ( !isset($this->options['onExit']) )
            $this->options['onExit'] = [];
        if ( !isset($this->options['onEnter']) )
            $this->options['onEnter'] = [];        
    }

    public function fireOnExit( $obj, $args ) {
        return $this->invokeCallbacks( $this->options['onExit'], $obj, $args );
    }

    public function fireOnEnter( $obj, $args ) {
        return $this->invokeCallbacks( $this->options['onEnter'], $obj, $args );
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
<?php namespace SilvertipSoftware\StateMachine;

class Transition {
    public $from;
    public $to;
    public $guards = array();

    public function __construct( $from, $to, $options ) {
        $this->from = $from;
        $this->to = $to;
        $this->options = $options;
        if ( !isset( $options['onTransition'] ) )
            $this->options['onTransition'] = array();
        
        if ( isset($options['guards']) )
            $this->guards = $options['guards'];
        elseif ( isset($options['guard']) )
            $this->guards = [ $options['guard'] ];
    }

    public function canExecute( $obj ) {
        foreach ( $this->guards as $guard ) {
            $allowed = true;
            if ( is_string($guard) ) {
                $allowed = call_user_func_array( [$obj,$guard], array_slice(func_get_args(), 1) );
            }
            else {
                $allowed = call_user_func_array( $guard, func_get_args() );
            }

            if ( !$allowed )
                return false;
        }
        return true;
    }

    public function execute( $obj, $args = array() ) {
        $this->invokeTransitionCallbacks( $this->options['onTransition'], $obj, $args );
    }

    protected function invokeTransitionCallbacks( $callback, $obj, $args ) {
        if ( is_string( $callback ) ) {
            call_user_func_array( [$obj,$callback], $args );
        }
        elseif ( $callback instanceof \Closure ) {
            call_user_func_array( $callback, array_merge([$obj], $args) );
        }
        elseif ( is_array($callback) ) {
            foreach ( $callback as $cb ) {
                $this->invokeTransactionCallbacks( $cb, $obj, $args );
            }
        }
    }
}
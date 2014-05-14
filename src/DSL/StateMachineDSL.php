<?php namespace SilvertipSoftware\StateMachine\DSL;

class StateMachineDSL {
    
    public function __construct( $machine ) {
        $this->machine = $machine;
    }

    public function options( $optArray ) {
        $this->machine->setOptions( $optArray );
        return $this;
    }

    public function state( $label, $opts = [] ) {
        $this->machine->addState( $label, $opts );
        return $this;
    }

    public function event( $label, $options, $closure ) {
        $event = $this->machine->addEvent( $label, $options );
        $closure( new EventDSL($event) );
        return $this;
    }
}
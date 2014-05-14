<?php namespace SilvertipSoftware\StateMachine;

class StateMachineProxy {
    
    public function __construct( $machine, $obj ) {
        $this->machine = $machine;
        $this->obj = $obj;
    }

    public function getCurrentStateValue() {
        return $this->machine->getCurrentStateValue( $this->obj );
    }

    public function setState( $state ) {
        return $this->machine->setState( $this->obj, $state );
    }

    public function getCurrentState() {
        return $this->machine->getCurrentState( $this->obj );
    }

    public function canTriggerEvent( $label ) {
        $args = array_slice( func_get_args(), 1 );
        return $this->machine->canTriggerEvent( $this->obj, $label, $args );
    }

    public function triggerEvent( $eventLabel, $shouldSave ) {
        $args = array_slice( func_get_args(), 2 );
        return $this->machine->triggerEvent( $this->obj, $eventLabel, $shouldSave, $args );
    }
}
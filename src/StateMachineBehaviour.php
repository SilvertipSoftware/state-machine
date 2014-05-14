<?php namespace SilvertipSoftware\StateMachine;

use \SilvertipSoftware\StateMachine\DSL\StateMachineDSL;

trait StateMachineBehaviour {
    
    protected static function defineStateMachine( $dsl ) {
        // override in classes
    }

    protected static function bootStateMachineBehaviour() {
        $machine = new StateMachine( get_called_class() );
        static::defineStateMachine( new StateMachineDSL($machine) );
    }

    protected $_stateMachineProxy = null;

    protected function getStateMachine() {
        if ( $this->_stateMachineProxy == null )
            $this->_stateMachineProxy = new StateMachineProxy( StateMachine::getStateMachineFor( get_called_class() ), $this );
        return $this->_stateMachineProxy;
    }
}
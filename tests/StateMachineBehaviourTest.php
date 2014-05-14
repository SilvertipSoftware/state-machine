<?php

use \SilvertipSoftware\StateMachine\StateMachine;
use \SilvertipSoftware\StateMachine\StateMachineProxy;

class StateMachineBehaviourTest extends TestCase {
    
    public function testRegistersMachine() {
        $stub = new SimpleStub();
        $this->assertNotNull( StateMachine::getStateMachineFor('SimpleStub') );
    }

    public function testProvidesAProxy() {
        $stub = new SimpleStub();
        $this->assertInstanceOf( StateMachineProxy::class, $stub->exposeProxy() );
    }
}
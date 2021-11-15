<?php

use \SilvertipSoftware\StateMachine\StateMachine;
use PHPUnit\Framework\TestCase;

class StateMachineTest extends TestCase {

	public function testAutoRegistersMachine() {
		$machine = new StateMachine( 'DynamicModel' );
		$this->assertEquals( $machine, StateMachine::getStateMachineFor( 'DynamicModel' ) );
	}

	public function testStateHelpersAreCreatedByDefault()
	{
		$machine = with(new StateMachine( 'DynamicModel'))->setStateAttributeName( 'status' );
		$states = [ 'stopped', 'running' ];
		foreach ( $states as $stateLabel ) {
			$machine->addState( $stateLabel );
			$this->assertTrue( DynamicModel::hasDynamicMethod( 'is'.ucfirst($stateLabel) ) );
		}
	}

	public function testAnyClassWillDo() {
		$machine = with(new StateMachine( 'NonDynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$obj = new NonDynamicModel();
		$obj->status = 'stopped';
		$this->assertEquals( 'stopped', $machine->getCurrentStateValue( $obj ) );
	}

	public function testStateHelpersAreNotCreatedIfTurnedOff() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'NonDynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$states = [ 'stopped', 'running' ];
		foreach ( $states as $stateLabel ) {
			$machine->addState( $stateLabel );
			$this->assertFalse( DynamicModel::hasDynamicMethod( 'is'.ucfirst($stateLabel) ) );
		}
	}

	public function testEventHelpersAreCreatedByDefault() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'NonDynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$events = [ 'start', 'stop' ];
		foreach ( $events as $event ) {
			$machine->addEvent( $event, [] );
			$this->assertFalse( DynamicModel::hasDynamicMethod( 'can'.ucfirst($event) ) );
			$this->assertFalse( DynamicModel::hasDynamicMethod( $event ) );
		}		
	}

	public function testStateHelpersAreCorrect() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$this->assertTrue( $obj->isStopped() );
		$this->assertFalse( $obj->isRunning() );
		$obj->status = 'running';
		$this->assertFalse( $obj->isStopped() );
		$this->assertTrue( $obj->isRunning() );
	}

	public function testCanTriggerChecksEventExists() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		foreach ( [ 'start', 'stop' ] as $event ) {
			$machine->addEvent( $event );
		}
		$obj = new DynamicModel();
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'UNKNOWN') );
	}

	public function testCanTriggerChecksCurrentState() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running' );
		$obj = new DynamicModel();
		$obj->status = 'running';
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'start') );
		$obj->status = 'stopped';
		$this->assertTrue( $machine->canTriggerEvent( $obj, 'start') );		
	}

	public function testModelGetsValueNotLabel() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped', ['value'=>1] );
		$machine->addState( 'running', ['value'=>2] );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running');
		$obj = new DynamicModel();
		$obj->status = 1;
		$obj->start();
		$this->assertEquals( 2, $obj->status );
	}

	public function testSingleClosureGuard() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', [
			'guard'=>function($obj) {
				return $obj->transition_can_happen;
			}
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->transition_can_happen = false;
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'start' ) );
		$obj->transition_can_happen = true;
		$this->assertTrue( $machine->canTriggerEvent( $obj, 'start' ) );
	}

	public function testCanHelper() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', [
			'guard'=>function($obj) {
				return $obj->transition_can_happen;
			}
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->transition_can_happen = false;
		$this->assertFalse( $obj->canStart() );
		$obj->transition_can_happen = true;
		$this->assertTrue( $obj->canStart() );
	}

	public function testSingleMethodGuard() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', ['guard'=>'checkCondition']);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->transition_can_happen = false;
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'start' ) );
		$obj->transition_can_happen = true;
		$this->assertTrue( $machine->canTriggerEvent( $obj, 'start' ) );
	}

	public function testCanTriggerPassesArgs() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', [
			'guard'=>function($obj, $value) {
				return ( $value == 5 );
			}
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'start', 21 ) );
		$this->assertTrue( $machine->canTriggerEvent( $obj, 'start', 5 ) );
	}

	public function testAllGuardsMustBeTrue() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' )->setOptions( ['helpers'=>false] );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', [
			'guards'=>['checkCondition',function($obj) {
				return true;
			}]
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->transition_can_happen = false;
		$this->assertFalse( $machine->canTriggerEvent( $obj, 'start' ) );
		$obj->transition_can_happen = true;
		$this->assertTrue( $machine->canTriggerEvent( $obj, 'start' ) );
	}

	public function testEventBeforeClosureCalled() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start', [
			'before' => function($obj) { $obj->callback_did_occur = true; }
		])->transitions( 'stopped', 'running' );
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertTrue( $obj->callback_did_occur );
	}

	public function testEventAfterClosureCalled() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start', [
			'after' => function($obj) { $obj->callback_did_occur = true; }
		])->transitions( 'stopped', 'running' );
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertTrue( $obj->callback_did_occur );
	}

	public function testTransitionClosureCalled() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped' );
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running', [
			'onTransition' => function( $obj ) {
				$obj->callback_did_occur = true;
			}
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertTrue( $obj->callback_did_occur );
	}

	public function testOnExitCallbackCalled() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped', [
			'onExit' => function($obj) {
				$obj->callback_did_occur = true;
			}
		]);
		$machine->addState( 'running' );
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running' );
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertTrue( $obj->callback_did_occur );
	}

	public function testAfterEventCallbackCalledOnSuccess() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped', []);
		$machine->addState( 'running', [
			'onEnter' => function($obj) {
				$obj->callback_did_occur = true;
			}
		]);
		$machine->addEvent( 'start' )->transitions( 'stopped', 'running' );
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertTrue( $obj->callback_did_occur );
	}

	public function testOrderingOfCallbacks() {
		DynamicModel::clearDynamicMethods();
		$machine = with(new StateMachine( 'DynamicModel' ))->setStateAttributeName( 'status' );
		$machine->addState( 'stopped', [
			'onExit'=> function($obj) { $obj->callback_did_occur = $obj->callback_did_occur . 'Oes';}
		]);
		$machine->addState( 'running', [
			'onEnter' => function($obj) {$obj->callback_did_occur = $obj->callback_did_occur.'Oer';}
		]);
		$machine->addEvent( 'start', [
			'before' => function($obj) { $obj->callback_did_occur = $obj->callback_did_occur . 'Bs';},
			'after' => function($obj) { $obj->callback_did_occur = $obj->callback_did_occur . 'As';}
		])->transitions( 'stopped', 'running', [
			'onTransition' => function($obj) { $obj->callback_did_occur = $obj->callback_did_occur . 'Ot';}
		]);
		$obj = new DynamicModel();
		$obj->status = 'stopped';
		$obj->start();
		$this->assertEquals( 'BsOesOtOerAs', $obj->callback_did_occur );
	}
}

<?php namespace SilvertipSoftware\StateMachine;

class StateMachine {
    protected static $stateMachines = [];
    public $clazz;
    protected $stateAttribute = 'state';
    protected $defineHelpers = true;
    protected $states = array();
    protected $events = array();

    public static function getStateMachineFor( $clazz ) {
        if ( isset(static::$stateMachines[$clazz]) )
            return static::$stateMachines[$clazz];
        return null;
    }

    public static function setStateMachineFor( $clazz, $machine ) {
        static::$stateMachines[$clazz] = $machine;
    }

    public function __construct( $clazz ) {
        $this->clazz = $clazz;
        static::setStateMachineFor( $clazz, $this );
    }
    
    public function setStateAttributeName( $stateAttribute ) {
        $this->stateAttribute = $stateAttribute;
        return $this;
    }

    public function setOptions( $options ) {
        if ( isset( $options['attribute'] ) )
            $this->stateAttribute = $options['attribute'];
        if ( isset( $options['helpers'] ) )
            $this->defineHelpers = $options['helpers'];
        return $this;
    }

    public function addState( $label, $options = [] ) {
        $this->states[$label] = $state = new State($label, $options);
        if ( $this->defineHelpers ) {
            $self = $this;
            $this->addHelperToClass( 'is'.ucfirst($label), function() use ($self, $state) {
                return ( $self->getCurrentStateValue( $this ) == $state->value );
            });
        }
        return $state;
    }
    
    public function addEvent( $label, $options = [] ) {
        $this->events[$label] = $event = new Event( $label, $options );
        if ( $this->defineHelpers ) {
            $self = $this;
            $this->addHelperToClass( 'can'.ucfirst($label), function() use ($self, $label) {
                return $self->canTriggerEvent( $this, $label );
            });
            $this->addHelperToClass( $label, function() use ($self, $label) {
                return $self->triggerEvent( $this, $label, false );
            });
            $this->addHelperToClass( $label.'AndSave', function() use ($self, $label) {
                return $self->triggerEvent( $this, $label, true );
            });
        }
        return $event;
    }

    private function addHelperToClass( $method, $closure ) {
        call_user_func_array( [$this->clazz, 'addDynamicMethod'], [ $method, $closure ] );
    }

    public function getCurrentStateValue( $obj ) {
        return $obj->{$this->stateAttribute};
    }

    public function setState( $obj, $state ) {
        $obj->{$this->stateAttribute} = $state->value;
    }

    public function getCurrentState( $obj ) {
        $value = $this->getCurrentStateValue( $obj );
        foreach( $this->states as $state ) {
            if ( $state->value === $value )
                return $state;
        }
        return null;
    }

    public function canTriggerEvent( $obj, $label, $args = [] ) {
        if ( !isset($this->events[$label]) )
            return false;
        $event = $this->events[$label];

        if ( !is_array( $args ) )
            $args = [ $args ];
        return $event->canBeTriggered( $obj, $this->getCurrentState($obj), $args );
    }

    public function triggerEvent( $obj, $eventLabel, $shouldSave, $args = [] ) {
        if ( !isset( $this->events[$eventLabel] ) )
            return false;
        $event = $this->events[$eventLabel];
        $oldState = $this->getCurrentState( $obj );

        if ( !is_array( $args ) )
            $args = [ $args ];

        $transition = $event->firstAvailableTransition( $obj, $oldState, $args );
        if ( $transition == null )
            return false;

        $newStateName = $transition->to;
        if ( !isset( $this->states[$newStateName] ) )
            return false;
        $newState = $this->states[$newStateName];

        $event->fireBeforeCallbacks( $obj );
        $oldState->fireOnExit( $obj, $args );
        $this->setState( $obj, $newState );
        $transition->execute( $obj );
        $newState->fireOnEnter( $obj, $args );
        $event->fireAfterCallbacks( $obj );
    }
}

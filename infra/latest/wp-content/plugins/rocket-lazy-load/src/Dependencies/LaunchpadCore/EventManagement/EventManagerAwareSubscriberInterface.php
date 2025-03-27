<?php
namespace RocketLazyLoadPlugin\Dependencies\LaunchpadCore\EventManagement;

interface EventManagerAwareSubscriberInterface extends SubscriberInterface {
	/**
	 * Set the WordPress event manager for the subscriber.
	 *
	 * @author Remy Perona
	 *
	 * @param EventManager $event_manager Event_Manager instance.
	 */
	public function set_event_manager( EventManager $event_manager );
}

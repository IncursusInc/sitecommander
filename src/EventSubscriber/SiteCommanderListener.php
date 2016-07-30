<?php

namespace Drupal\sitecommander\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactory;

class SiteCommanderListener implements EventSubscriberInterface
{
	protected $currentUser;
	protected $configFactory;

	public function __construct( AccountInterface $account, ConfigFactory $configFactory ) {
		$this->currentUser = $account;
		$this->configFactory = $configFactory;
	}

	public static function getSubscribedEvents()
	{
		$events[KernelEvents::RESPONSE][] = array('onKernelResponse', 1000);
    return $events;
	}

	public function onKernelResponse( $event )
	{
		if(!$this->configFactory->get('sitecommander.settings')->get('enableAnonymousUserTracking'))
			return;

		if($this->currentUser->isAnonymous() === TRUE)
		{
			$visitorIpAddressTTL = $this->configFactory->get('sitecommander.settings')->get('visitorIpAddressTTL');
			$redisDatabaseIndex = $this->configFactory->get('sitecommander.settings')->get('redisDatabaseIndex');

			// Try to get existing Redis connection, if one is available
			$redis = \Drupal\redis\ClientFactory::getClient();

			if (!$redis)
			{
				// We might need to connect manually
				$redisHostName = $this->configFactory->get('sitecommander.settings')->get('redisHostName');
				$redisPort = $this->configFactory->get('sitecommander.settings')->get('redisPort');

				$redis = new \Redis();
				$redis->connect($redisHostName, $redisPort);
			}
			$redis->select($redisDatabaseIndex);

			// Do not allow PhpRedis serialize itself data, we are going to do it
			// ourself. This will ensure less memory footprint on Redis size when
			// we will attempt to store small values.
			$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

			$ipAddress = \Drupal::request()->getClientIp();

			// Sets key & value, with 15 minute TTL
			// TODO - make the 15 minute interval configurable
			$redis->setEx('siteCommander_anon_user_' . $ipAddress, $visitorIpAddressTTL * 60, '1'); 
		}
	}
}

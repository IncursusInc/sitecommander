<?php

namespace Drupal\drupalstat;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DrupalStatListener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::RESPONSE => 'onKernelRequest',
		];
	}

	public function onKernelRequest($event)
	{
		// If this visitor is not authenticated, then add them to the IP address list in Redis with a 15 minute expiration stamp
		if(\Drupal::currentUser()->isAnonymous())
		{
			$redisHostName = \Drupal::config('drupalstat.settings')->get('redisHostName');
			$redisPort = \Drupal::config('drupalstat.settings')->get('redisPort');
			$visitorIpAddressTTL = \Drupal::config('drupalstat.settings')->get('visitorIpAddressTTL');

			if (class_exists('Redis') && $redisHostName && $redisPort) {

				$redis = new \Redis();

				$redis->connect($redisHostName, $redisPort);

				// Do not allow PhpRedis serialize itself data, we are going to do it
				// ourself. This will ensure less memory footprint on Redis size when
				// we will attempt to store small values.
				$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

				$ipAddress = \Drupal::request()->getClientIp();

				// Sets key & value, with 15 minute TTL
				// TODO - make the 15 minute interval configurable
				$redis->setEx('drupalStat_anon_user_' . $ipAddress, $visitorIpAddressTTL * 60, '1'); 
			}
		}
	}
}

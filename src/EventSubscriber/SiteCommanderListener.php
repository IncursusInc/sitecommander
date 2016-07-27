<?php

namespace Drupal\sitecommander\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SiteCommanderListener implements EventSubscriberInterface
{
	protected $currentUser;

	//public function __construct( Drupal\Core\Session\AccountInterface $account ) {
		//$this->currentUser = $account;
	//}

	public static function getSubscribedEvents()
	{
		$events[KernelEvents::RESPONSE][] = array('onKernelResponse', 100);
    return $events;
	}

	public function onKernelResponse( $event )
	{
//$fp = fopen('/tmp/eventlog.txt', 'a');
//fputs($fp, "onKernelRequest()\n");
//fclose($fp);

		// If this visitor is not authenticated, then add them to the IP address list in Redis with a 15 minute expiration stamp
		if(\Drupal::currentUser()->isAnonymous() == TRUE)
		//if($this->currentUser->isAnonymous() === TRUE)
		{
//$fp = fopen('/tmp/eventlog.txt', 'a');
//fputs($fp, time() . "\n");
//fclose($fp);

			$redisHostName = \Drupal::config('sitecommander.settings')->get('redisHostName');
			$redisPort = \Drupal::config('sitecommander.settings')->get('redisPort');
			$visitorIpAddressTTL = \Drupal::config('sitecommander.settings')->get('visitorIpAddressTTL');
			$redisDatabaseIndex = \Drupal::config('sitecommander.settings')->get('redisDatabaseIndex');

			if (class_exists('Redis') && $redisHostName && $redisPort) {

				$redis = new \Redis();

				$redis->connect($redisHostName, $redisPort);
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
}

<?php

namespace Drupal\drupalstat;

class DrupalStatUtils {

  /**
   * {@inheritdoc}
   */

	public static function formatBytes($size, $precision = 2)
	{
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');   

		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}

	public static function elapsedTime( $pastTimeStamp )
  {
    $seconds  = strtotime(date('Y-m-d H:i:s')) - $pastTimeStamp;

    $months = floor($seconds / (3600*24*30));
    $day = floor($seconds / (3600*24));
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds - ($hours*3600)) / 60);
    $secs = floor($seconds % 60);

    if($seconds < 60)
      $time = $secs." seconds ago";
    else if($seconds < 60*60 )
      $time = $mins." min ago";
    else if($seconds < 24*60*60)
      $time = $hours." hours ago";
    else if($seconds < 24*60*60)
      $time = $day." day ago";
    else
      $time = $months." month ago";

    return $time;
  }

}
?>

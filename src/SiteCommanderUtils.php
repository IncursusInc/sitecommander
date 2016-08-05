<?php

namespace Drupal\sitecommander;

class SiteCommanderUtils {

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
      $time = $mins." min(s) ago";
    else if($seconds < 24*60*60)
      $time = $hours." hour(s) ago";
    else if($seconds < 24*60*60)
      $time = $day." day(s) ago";
    else
      $time = $months." month(s) ago";

    return $time;
  }

	/**
   * Returns the number of available CPU cores
   * 
   *  Should work for Linux, Windows, Mac & BSD
   * 
   * @return integer 
   */
	public static function getNumCores()
	{
		$numCores = 1;

		// *NIX systems, especially Linux
		if(file_exists('/proc/cpuinfo'))
		{
			$cpuinfo = file_get_contents('/proc/cpuinfo');
			preg_match_all('/^processor/m', $cpuinfo, $matches);
			$numCores = count($matches[0]);
		}
		else if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			// Windows systems
			$process = @popen('wmic cpu get NumberOfCores', 'rb');
			if (false !== $process)
			{
				fgets($process);
				$numCores = intval(fgets($process));
				pclose($process);
			}
		}
		else
		{
			// *NIX fallback (won't always work, depending on version of kernel and sysctl!
			$process = @popen('sysctl -a', 'rb');
			if (false !== $process)
			{
				$output = stream_get_contents($process);
				preg_match('/hw.ncpu: (\d+)/', $output, $matches);
				if ($matches)
				{
					$numCores = intval($matches[1][0]);
				}
				pclose($process);
			}
		}
  
		return $numCores;
	}

	public static function makePidFile( $pidFileName )
	{
		$fp = fopen(drupal_get_path('module', 'sitecommander') . '/' . $pidFileName, 'w');
		fputs($fp, posix_getpid());
		fclose($fp);
	}

	public static function deletePidFile( $pidFileName )
	{
		@unlink(drupal_get_path('module', 'sitecommander') . '/' . $pidFileName);
	}

	public static function isProcessRunning( $processString )
	{
		ob_start();
		system('ps xa | grep "' . $processString . '" | grep -v grep');
		$rawOutput = ob_get_contents();
		ob_end_clean();

		$outputArray = array_filter(explode('\n', $rawOutput));

		if(count($outputArray) > 0) return true; else return false;
	}

  public static function formatNumber($value) {
		// first strip any formatting;
		$n = (0+str_replace(",", "", $value));

		// is this a number?
		if (!is_numeric($n)) return false;

		// now filter it;
		if ($n > 1000000000000) return round(($n/1000000000000), 2).' T';
		elseif ($n > 1000000000) return round(($n/1000000000), 2).' B';
		elseif ($n > 1000000) return round(($n/1000000), 2).' M';
		elseif ($n > 1000) return round(($n/1000), 2).' K';

		return number_format($n);
 	}

	public static function formatUptime( $uptime )
	{
    $seconds = (integer)$uptime % 60;
    $minutes = (integer)(( $uptime % 3600 ) / 60);
    $hours   = (integer)(( $uptime % 86400 ) / 3600);
    $days    = (integer)($uptime / 86400);

		if ( $days > 0 ) {
        $uptimestring = "${days}d ${hours}h ${minutes}m ${seconds}s";
    }
    elseif ( $hours > 0 ) {
        $uptimestring = "${hours}h ${minutes}m ${seconds}s";
    }
    elseif ( $minutes > 0 ) {
        $uptimestring = "${minutes}m ${seconds}s";
    }
    else {
        $uptimestring = "${seconds}s";
    }
    return $uptimestring;
	}
}

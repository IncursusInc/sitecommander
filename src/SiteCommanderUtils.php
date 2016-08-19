<?php

namespace Drupal\sitecommander;

/**
 * @file
 * Contains \Drupal\sitecommander\SiteCommanderUtils.
 */
class SiteCommanderUtils {

  /**
   * Returns a formatted string with human readable bytes.
   *
   * @param int $size
   * @param int $precision
   *
   * @return int
   */
  public static function formatBytes($size, $precision = 2) {

    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
  }

  /**
   * Returns a formatted string with human readable elapsed time.
   *
   * @param long $pastTimeStamp
   *
   * @return string
   */
  public static function elapsedTime($pastTimeStamp) {

    $seconds  = strtotime(date('Y-m-d H:i:s')) - $pastTimeStamp;

    $months = floor($seconds / (3600 * 24 * 30));
    $day = floor($seconds / (3600 * 24));
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds - ($hours * 3600)) / 60);
    $secs = floor($seconds % 60);

    if ($seconds < 60) {
      $time = $secs . " seconds ago";
    }
    elseif ($seconds < 60 * 60) {
      $time = $mins . " min(s) ago";
    }
    elseif ($seconds < 24 * 60 * 60) {
      $time = $hours . " hour(s) ago";
    }
    elseif ($seconds < 24 * 60 * 60) {
      $time = $day . " day(s) ago";
    }
    else {
      $time = $months . " month(s) ago";
    }

    return $time;
  }

  /**
   * Returns the number of available CPU cores.
   *
   *  Should work for Linux, Windows, Mac & BSD.
   *
   * @return int
   */
  public static function getNumCores() {

    $numCores = 1;

    // *NIX systems, especially Linux.
    if (file_exists('/proc/cpuinfo')) {
      $cpuinfo = file_get_contents('/proc/cpuinfo');
      preg_match_all('/^processor/m', $cpuinfo, $matches);
      $numCores = count($matches[0]);
    }
    elseif (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
      // Windows systems.
      $process = @popen('wmic cpu get NumberOfCores', 'rb');
      if (FALSE !== $process) {
        // Skip header line.
        fgets($process);

        // Get number of cores.
        fgets($process);
        $numCores = intval(fgets($process));

        pclose($process);
      }
    }
    else {
      // *NIX fallback (won't always work, depending on version of kernel and sysctl!
      $process = @popen('sysctl -a', 'rb');
      if (FALSE !== $process) {
        $output = stream_get_contents($process);
        preg_match('/hw.ncpu: (\d+)/', $output, $matches);
        if ($matches) {
          $numCores = intval($matches[1][0]);
        }
        pclose($process);
      }
    }

    return $numCores;
  }

  /**
   * Creates a PID file.
   *
   * @param string $pidFileName
   *
   * @return
   */
  public static function makePidFile($pidFileName) {
    $fp = fopen(drupal_get_path('module', 'sitecommander') . '/' . $pidFileName, 'w');
    fwrite($fp, posix_getpid());
    fclose($fp);
  }

  /**
   * Deletes the passed filename.
   *
   * @param string $pidFileName
   */
  public static function deletePidFile($pidFileName) {
    @unlink(drupal_get_path('module', 'sitecommander') . '/' . $pidFileName);
  }

  /**
   *
   */
  public static function isProcessRunning($processString) {

    ob_start();
    system('ps xa | grep "' . $processString . '" | grep -v grep');
    $rawOutput = ob_get_contents();
    ob_end_clean();

    $outputArray = array_filter(explode('\n', $rawOutput));

    if (count($outputArray) > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   *
   */
  public static function formatNumber($value) {
    // First strip any formatting;.
    $n = (0 + str_replace(",", "", $value));

    // Is this a number?
    if (!is_numeric($n)) {
      return FALSE;
    }

    // Now filter it;.
    if ($n > 1000000000000) {
      return round(($n / 1000000000000), 2) . ' T';
    }
    elseif ($n > 1000000000) {
      return round(($n / 1000000000), 2) . ' B';
    }
    elseif ($n > 1000000) {
      return round(($n / 1000000), 2) . ' M';
    }
    elseif ($n > 1000) {
      return round(($n / 1000), 2) . ' K';
    }

    return number_format($n);
  }

  /**
   *
   */
  public static function formatUptime($uptime) {

    $seconds = (int) $uptime % 60;
    $minutes = (int) (($uptime % 3600) / 60);
    $hours   = (int) (($uptime % 86400) / 3600);
    $days    = (int) ($uptime / 86400);

    if ($days > 0) {
      $uptimestring = "${days}d ${hours}h ${minutes}m ${seconds}s";
    }
    elseif ($hours > 0) {
      $uptimestring = "${hours}h ${minutes}m ${seconds}s";
    }
    elseif ($minutes > 0) {
      $uptimestring = "${minutes}m ${seconds}s";
    }
    else {
      $uptimestring = "${seconds}s";
    }
    return $uptimestring;
  }

}

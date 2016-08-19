<?php

namespace Drupal\sitecommander\TwigExtension;

/**
 * Class DefaultService.
 *
 * @package Drupal\SiteCommanderTwigFilters
 */
class SiteCommanderTwigFilters extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'SiteCommanderTwigFilters.twig_extension';
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('format_size', array($this, 'formatSize'), array('is_safe' => array('html'))),
      new \Twig_SimpleFilter('format_number', array($this, 'formatNumber'), array('is_safe' => array('html'))),
      new \Twig_SimpleFilter('format_uptime', array($this, 'formatUptime'), array('is_safe' => array('html'))),
    ];
  }

  /**
   *
   */
  public static function formatSize($value) {
    return format_size($value);
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

    $seconds = (integer) $uptime % 60;
    $minutes = (integer) (($uptime % 3600) / 60);
    $hours   = (integer) (($uptime % 86400) / 3600);
    $days    = (integer) ($uptime / 86400);

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

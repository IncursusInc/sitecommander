<?php

namespace Drupal\sitecommander\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystem;
// Use Drupal\Core\Plugin\ContainerFactoryPluginInterface;.
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sitecommander\Ajax\ReadMessageCommand;

/**
 *
 */
class BackupController extends ControllerBase {

  protected $connection;
  protected $fileSystem;
  protected $configFactory;
  protected $translation;

  /**
   *
   */
  public function __construct(Connection $connection, FileSystem $fileSystem, ConfigFactory $configFactory) {

    $this->connection = $connection;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('file_system'),
      $container->get('config.factory')
    );
  }

  /**
   *
   */
  public static function scScanDir($dir) {

    $r = array();
    $dh = @opendir($dir);

    if ($dh) {
      while (($fname = readdir($dh)) !== FALSE) {
        if (is_dir($dir . '/' . $fname)) {
          continue;
        }

        $r[$fname] = stat("$dir/$fname");

        // Make a pretty filesize.
        $r[$fname]['fileSizeHuman'] = format_size($r[$fname]['size']);

        // Make a field we can use as a DOM ID.
        $r[$fname]['filenameId'] = str_replace('.', '-', $fname);
      }
      closedir($dh);

      // Sort files by created timestamp (reverse chronological order)
      $customSort = function ($a, $b) {
        return $a['ctime'] < $b['ctime'];
      };

      uasort($r, $customSort);

      return $r;
    }
  }

  /**
   *
   */
  public static function getBackupList($backupDir) {

    $fileList = self::scScanDir($backupDir, 'ctime');
    return $fileList;
  }

  /**
   *
   */
  public function deleteBackup($fileName) {
    $backupDirectory = $this->configFactory->get('sitecommander.settings')->get('backupDirectory');
    @unlink($backupDirectory . '/' . $fileName);

    // Create AJAX Response object.
    $response = new AjaxResponse();

    $responseData = new \StdClass();
    $responseData->command = 'readMessage';
    $responseData->siteCommanderCommand = 'deleteBackup';
    $responseData->payload = str_replace('.', '-', $fileName);
    $response->addCommand(new ReadMessageCommand($responseData));

    // Return ajax response.
    return $response;
  }

  /**
   *
   */
  public function runBackup() {
  }

  /**
   *
   */
  public function makeBackupBackground() {
    $this->makeBackup(TRUE);

    $response = new AjaxResponse();

    $responseData = new \StdClass();
    $responseData->command = 'readMessage';
    $responseData->siteCommanderCommand = 'makeBackupBackground';
    $response->addCommand(new ReadMessageCommand($responseData));
    return $response;
  }

  /**
   *
   */
  public function makeBackup($backgroundMode = FALSE) {

    // Get the config options we need.
    $config = $this->configFactory->get('sitecommander.settings');
    $backupDirectory = $config->get('backupDirectory');
    $drushPath = $config->get('drushPath');

    // Build the drush command line.
    $archiveFileName = 'sitecommander-backup.' . time() . '.tar.gz';
    $cmd = $drushPath . ' archive-dump --destination=' . $backupDirectory . '/' . $archiveFileName;

    if ($backgroundMode) {
      shell_exec('/usr/bin/nohup ' . $cmd . ' 2>/dev/null >/dev/null &');
      return;
    }
    else {
      ob_start();
      system($cmd);
      $backupResult = ob_get_contents();
      ob_end_clean();
    }

    // Optional mirroring of backups.
    $mirrorEnabled = $config->get('mirrorEnabled');
    if ($mirrorEnabled) {
      self::mirrorFile($backupDirectory . '/' . $archiveFileName);
    }

    // Create AJAX Response object.
    $response = new AjaxResponse();

    $responseData = new \StdClass();
    $responseData->command = 'readMessage';
    $responseData->siteCommanderCommand = 'makeBackup';
    $responseData->rowId = str_replace('.', '-', $archiveFileName);
    $responseData->fileName = $archiveFileName;
    $responseData->fileSize = format_size(filesize($backupDirectory . '/' . $archiveFileName));
    $responseData->fileDate = date('Y.m.d H:i:s', filectime($backupDirectory . '/' . $archiveFileName));
    $response->addCommand(new ReadMessageCommand($responseData));

    // Return ajax response.
    return $response;
  }

  /**
   *
   */
  function restoreBackup($fileName) {

    // Get the config options we need.
    $backupDirectory = $this->configFactory->get('sitecommander.settings')->get('backupDirectory');
    $drushPath = $this->configFactory->get('sitecommander.settings')->get('drushPath');

    // Prepare the site for restoration?
    // Rename old web root temporarily.
    $webRoot = DRUPAL_ROOT;

    if (!rename($webRoot, $webRoot . '-renamed')) {
      // Create AJAX Response object.
      $response = new AjaxResponse();

      $responseData = new \StdClass();
      $responseData->command = 'readMessage';
      $responseData->siteCommanderCommand = 'restoreBackup';
      $responseData->errorMessage = 'Could not rename the current document root! Likely a permission problem.';
      $response->addCommand(new ReadMessageCommand($responseData));

      // Return ajax response.
      return $response;
    }

    // Make the new web root and set perms.
    mkdir($webRoot);

    // Locate backup file.
    $backupImage = $backupDirectory . '/' . $fileName;

    // Drush it!
    $cmd = $drushPath . ' archive-restore ' . $backupImage . ' --destination=' . $webRoot;

    ob_start();
    system($cmd);
    $backupResult = ob_get_contents();
    ob_end_clean();

    // Prepare the site post-restoration
    // Clear cache, etc.?
    // Create AJAX Response object.
    $response = new AjaxResponse();

    $responseData = new \StdClass();
    $responseData->command = 'readMessage';
    $responseData->siteCommanderCommand = 'restoreBackup';
    $response->addCommand(new ReadMessageCommand($responseData));

    // Return ajax response.
    return $response;
  }

  /**
   *
   */
  public static function mirrorFile($fileName) {

    $config = \Drupal::config('sitecommander.settings');
    $mirrorMode = $config->get('mirrorMode');
    $remoteUserName = $config->get('remoteUserName');
    $remotePassword = $config->get('remotePassword');
    $remoteHost = $config->get('remoteHost');
    $remotePort = $config->get('remotePort');
    $remoteDir = $config->get('remoteDir');

    if ($mirrorMode == 'SFTP') {
      try {
        $connection = ssh2_connect($remoteHost, $remotePort);

        if (!$connection) {
          echo ("Could not connect to $remoteHost on port $remotePort.");
        }

        if (!ssh2_auth_password($connection, $remoteUserName, $remotePassword)) {
          echo ("Could not authenticate with username $remoteUserName " . "and password $remotePassword.");
        }

        $sftp = ssh2_sftp($connection);
        if (!$sftp) {
          echo ("Could not initialize SFTP subsystem.");
        }

        $pathParts = pathinfo($fileName);

        if (!ssh2_scp_send($connection, $fileName, $remoteDir . '/' . $pathParts['filename'])) {
          echo ("Could not send file.");
        }

      }
      catch (Exception $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
        return FALSE;
      }

      return TRUE;
    }

  }

}

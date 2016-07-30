<?php

/**
 * @file
 * Contains \Drupal\sitecommander\Controller\BackupController.
 */

namespace Drupal\sitecommander\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Statement;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sitecommander\Ajax\ReadMessageCommand;
use Drupal\sitecommander\SiteCommanderUtils;
use Drupal\Core\Template\TwigEnvironment;

class BackupController extends ControllerBase {

	protected $connection;
	protected $fileSystem;
	protected $configFactory;
	protected $translation;

	public function __construct( Connection $connection, FileSystem $fileSystem, ConfigFactory $configFactory )
	{
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

  public static function scScanDir( $dir ) {

		$r = array(); 
		$dh = @opendir($dir); 

		if ($dh) { 
			while (($fname = readdir($dh)) !== false) { 
				if(is_dir($dir . '/' . $fname)) continue;

				$r[$fname] = stat("$dir/$fname"); 

				// Make a pretty filesize
				$r[$fname]['fileSizeHuman'] = format_size($r[$fname]['size']);

				// Make a field we can use as a DOM ID 
				$r[$fname]['filenameId'] = str_replace('.', '-', $fname);
			} 
			closedir($dh); 

			$r = array_reverse($r);

			return $r;
		} 
	}

	public static function getBackupList( $backupDir )
	{
		$fileList = self::scScanDir($backupDir, 'ctime');
		return $fileList;
	}

  public function deleteBackup( $fileName ) {
		$backupDirectory = $this->configFactory->get('sitecommander.settings')->get('backupDirectory');
		@unlink($backupDirectory . '/' . $fileName);

    // Create AJAX Response object.
    $response = new AjaxResponse();

		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'deleteBackup';
		$responseData->payload = str_replace('.', '-', $fileName);
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

  public function runBackup() {
	}

  public function makeBackup() {

		// Get the config options we need
		$backupDirectory = $this->configFactory->get('sitecommander.settings')->get('backupDirectory');
		$drushPath = $this->configFactory->get('sitecommander.settings')->get('drushPath');
		$backupMaxAgeInDays = $this->configFactory->get('sitecommander.settings')->get('backupMaxAgeInDays');

		// Build the drush command line
		$archiveFileName = 'sitecommander-backup.' . time() . '.tar.gz';
		$cmd = $drushPath . ' archive-dump --destination=' . $backupDirectory . '/' . $archiveFileName;

		ob_start();
		system($cmd);
		$backupResult = ob_get_contents();
		ob_end_clean();

    // Create AJAX Response object.
    $response = new AjaxResponse();

		$responseData = new \StdClass();
		$responseData->command = 'readMessage';
		$responseData->siteCommanderCommand = 'makeBackup';
		$responseData->payload = $backupResult;
    $response->addCommand( new ReadMessageCommand($responseData));

		// Return ajax response.
		return $response;
	}

}

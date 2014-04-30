<?

defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Class Concrete5_Library_ApplicationUpdate
 */
class Concrete5_Library_ApplicationUpdate {

    /**
     * @var
     */
    protected $version;
    /**
     * @var
     */
    protected $identifier;

    /**
     *
     */
    const E_UPDATE_WRITE_CONFIG = 10;

    /**
     * @return mixed
     */
    public function getUpdateVersion() {return $this->version;}

    /**
     * @return mixed
     */
    public function getUpdateIdentifier() {return $this->identifier;}

    /**
     * @param $version
     * @return mixed
     */
    public static function getByVersionNumber($version) {
		$upd = new Update();
		$updates = $upd->getLocalAvailableUpdates();
		foreach($updates as $up) {
			if ($up->getUpdateVersion() == $version) {
				return $up;
			}
		}
	}
	
	/** 
	 * Writes the core pointer into config/site.php
	 */
	public function apply() {
		if (!is_writable(DIR_BASE . '/config/site.php')) {
			return self::E_UPDATE_WRITE_CONFIG;
		}
		
		$configFile = DIR_BASE . '/config/site.php';
		$contents = Loader::helper('file')->getContents($configFile);
		$contents = trim($contents);
		// remove any instances of app pointer
		
		$contents = preg_replace("/define\('DIRNAME_APP_UPDATED', '(.+)'\);/i", "", $contents);
		
		file_put_contents($configFile, $contents);
		
		if (substr($contents, -2) == '?>') {
			file_put_contents($configFile, "<" . "?" . "p" . "hp define('DIRNAME_APP_UPDATED', '" . $this->getUpdateIdentifier() . "');?>", FILE_APPEND);
		} else {
			file_put_contents($configFile, "?><" . "?" . "p" . "hp define('DIRNAME_APP_UPDATED', '" . $this->getUpdateIdentifier() . "');?>", FILE_APPEND);
		}
		
		return true;
	}

    /**
     * @param string $dir
     * @return ApplicationUpdate
     */
    public function get($dir) {
		$APP_VERSION = false;
		// given a directory, we figure out what version of the system this is
		$version = DIR_APP_UPDATES . '/' . $dir . '/' . DIRNAME_APP . '/config/version.php';
		@include($version);
		if ($APP_VERSION != false) {
			$obj = new ApplicationUpdate();
			$obj->version = $APP_VERSION;
			$obj->identifier = $dir;
			return $obj;
		}		
	}

}
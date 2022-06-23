<?php

namespace Packinst\Package;

use Packinst\Utils\ArrayTokenScanner;
use Packinst\Package\GitPackage;
use Packinst\Package\Downloader\GitPackageDownloader;
use Packinst\Package\Installer\GitPackageInstaller;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Just basic capabilities of a Git package
 *
 */
final class PackageManager
{
	/**
	 *	@const string DS
	 */
	private const DS = DIRECTORY_SEPARATOR;

	/**
	 *	@const string INIT_FILE
	 */
	public const INIT_FILE = 'init.php';

	/**
	 *	@const string INIT_CONTENT_REGEX
	 */
	public const INIT_CONTENT_REGEX = '#plat_plugin_register\\(\\s*\\[([^\\x00]*)\\]\\s*\\);#i';

	/**
	 *	@var string $location;
	 */
	private static $location = null;

	/**
	 *	@var array $packageList;
	 */
	private static $packageList = [];

	/**
	 *	Performs the scan of a php array code string and converts it 
	 *	in a live PHP array.
	 *
	 *	@param	string	$arrayCode
	 *	@return	array|false
	 */
	private static function arrayCodeToArray(string $arrayCode)
	{
		$ats = new ArrayTokenScanner();
		//
		try 
		{
			return $ats->scan($arrayCode);
		}
		catch (Throwable $e)
		{
			return false;
		}
	}

	/**
	 *	Scans the specified package path for info on the plugin
	 *
	 *	@param	string	$packagePath
	 *	@return	array|bool
	 */
	private static function scanPackage(string $packagePath)
	{
		if (empty($packagePath) || !is_dir($packagePath))
		{
			return false;
		}
		//
		$initFile = $packagePath . self::DS . self::INIT_FILE;
		//
		if (!file_exists($initFile))
		{
			return false;
		}
		//
		if ($contents = file_get_contents($initFile))
		{
			$data = [];
			//
			if (preg_match(self::INIT_CONTENT_REGEX, $contents, $data))
			{
				$code = '[' . $data[1] . ']';
				//
				return self::arrayCodeToArray($code);
			}
		}
		//
		return false;
	}

	/**
	 *	Scans the 'vendor' folder for info on installed plugins
	 *
	 *	@return	bool
	 */
	private static function scanLocationForPackages()
	{
		if (empty(self::$location))
		{
			return false;
		}
		//
		self::$packageList = [];
		//
		$mainLocation = self::$location;
		//
		$vendors = array_diff(scandir($mainLocation), ['..', '.']);
		foreach ($vendors as $vendor) 
		{
			$vendorLocation = $mainLocation . self::DS . $vendor;
			//
			if (!is_dir($vendorLocation))
			{
				continue;
			}
			//
			$packages = array_diff(scandir($vendorLocation), ['..', '.']);
			foreach ($packages as $package)
			{
				$packagePath = $vendorLocation . self::DS . $package;
				//
				if (!is_dir($packagePath))
				{
					continue;
				}
				//
				if ($info = self::scanPackage($packagePath))
				{
					$info['plugin_path'] = $packagePath;
					$info['classes_path'] = $packagePath . self::DS . $info['classes_folder'];
					//
					self::$packageList[$info['plugin']] = $info;
				}
			}
		}
		//
		return true;
	}

	/**
	 *	Defines the location of the 'vendor' folder
	 *
	 *	@param	string	$location
	 *	@return	void
	 */
	public static function setLocation(string $location)
	{
		self::$location = $location;
	}

	/**
	 *	Returns a list of all installed plugins or an associative array
	 *	with their info, with indexes named after their names.
	 *
	 *	@param	bool	$loadInfo = false
	 *	@return	array
	 */
	public static function getInstalledPackages(bool $loadInfo = false)
	{
		if (empty(self::$packageList))
		{
			self::scanLocationForPackages();
		}
		//
		$data = [];
		//
		if ($loadInfo)
		{
			foreach (self::$packageList as $name => $info)
			{
				$data[$name] = $info;
			}
		}
		else
		{
			foreach (self::$packageList as $name => $info)
			{
				$data[] = $name;
			}
		}
		//
		return $data;
	}

	/**
	 *	Calls the listener set by the user (if any)
	 *
	 *	@param	mixed	...$arguments
	 *	@return	void
	 */
	private static function callListener(...$arguments)
	{
		echo implode('', ($arguments ?? [])) . "\r\n";
	}

	/**
	 *	Performs installation steps for the given package
	 *
	 *	@param	Packinst\Package\GitPackage	$package
	 *	@param	bool	$fetchInfo = false
	 *	@return	bool
	 */
	public static function install(GitPackage $package, bool $fetchInfo = false)
	{
		if (empty(self::$location))
		{
			return false;
		}
		//
		if (empty($package))
		{
			return false;
		}
		//
		if ($fetchInfo)
		{
			$package->fetchRepositoryInfo();
		}
		//
		$group = $package->getVendor();
		$project = $package->getProject();
		//
		$to_path = self::$location . self::DS . $group . self::DS . $project;
		$to_zip = $to_path . '/master.zip';
		//
		@mkdir($to_path, 0777, true);
		//
		$downloader = new GitPackageDownloader();
		$downloader->setPackage($package);
		//
		if ($downloader->downloadTo($to_zip))
		{
			$listener = function(...$event)
			{
				PackageManager::callListener(...$event);
			};
			//
			return (new GitPackageInstaller())
				->setLogListener($listener)
				->setPackageDownloader($downloader)
				->install();
		}
		//
		return false;
	}
	
}


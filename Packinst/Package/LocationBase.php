<?php

namespace Packinst\Package;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Just basic capabilities of a Git package
 *
 */
final class LocationBase
{
	/**
	 *	@const string DS
	 */
	private const DS = DIRECTORY_SEPARATOR;

	private static $location = null;

	private static $packageList = [];


	private static function arrayCodeToArray(string $arrayCode)
	{

	}



	private static function scanLocationForPackages()
	{
		if (empty(self::$location))
		{
			return false;
		}

		self::$packageList = [];

		$mainLocation = self::$location;

		$vendors = array_diff(scandir($mainLocation), ['..', '.']);

		foreach ($vendors as $vendor)
		{
			$vendorLocation = $mainLocation . self::DS . $vendor;

			$packages = array_diff(scandir($vendorLocation), ['..', '.']);

			foreach ($packages as $package)
			{
				$packageInitFile = $vendorLocation . self::DS . $package . self::DS . 'init.php';

				$grossInfo = file_get_contents($packageInitFile);
				$netInfo = '';
				$matched = [];

				if (preg_match('#plat_plugin_register\(([^\x00]*)\);#i', $grossInfo, $matched))
				{
					$netInfo = $matched[1];
				}



			}



		}


	}


	public static function setLocation(string $location)
	{
		self::$location = $location;
	}

	public static function getInstalledPackages(bool $loadInfo = false)
	{

	}
	
}


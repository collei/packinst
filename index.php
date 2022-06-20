<?php

include 'Packinst\Package\GitPackage.php';
include 'Packinst\Package\Installable.php';
include 'Packinst\Package\Downloader\GitPackageDownloader.php';
include 'Packinst\Package\Installer\GitPackageInstaller.php';

use Packinst\Package\GitPackage;
use Packinst\Package\Installable;
use Packinst\Package\Downloader\GitPackageDownloader;
use Packinst\Package\Installer\GitPackageInstaller;


?>
<!doctype html>
<html>
<head>
</head>
<body>
<hr>
<form action="" method="post">
<p>
	In order to install a package from GITHUB,
	please inform the repository in format <b>groupname/projectname</b>
	in the field below and then hit <b>DO IT</b>.
</p>
<p>
	<input type="text" name="git_package" />
	&nbsp; &nbsp;
	<input type="submit" name="git_package_installer" value="DO IT" />
</p>
</form>
<hr>
<pre>
<?php

$nl = "\r\n";

$git_package = $_REQUEST['git_package'] ?? '';


function install_git_into($group, $project, $destination)
{
	@mkdir("./{$destination}/{$group}");
	@mkdir("./{$destination}/{$group}/{$project}");

	$to = "./{$destination}/{$group}/{$project}/master.zip";

	$res = (new GitPackageDownloader())
		->setPackage(new GitPackage($group, $project))
		->downloadTo($to);

	if ($res)
	{
		$listener = function($event)
		{
			echo $event . "\r\n";
		};

		echo "- listener set \r\n";

		$gpi = (new GitPackageInstaller())
			->setLogListener($listener)
			->setPackage($to)
			->install();

		return $gpi;
	}

	return false;
}

if (!empty($git_package))
{
	$matches = [];

	if (preg_match('/([\w_\-.]+)[\\/\\\\]([\w_\-.]+)/', $git_package, $matches))
	{
		echo '<fieldset>' . print_r($matches, true) . '</fieldset>' . $nl;

		if (install_git_into($matches[1], $matches[2], 'vendor'))
		{
			echo "- Package $git_package installed successfully. $nl";
		}
		else
		{
			echo "- Error occurred while installing $git_package. Please verify. $nl";
		}
	}
	else
	{
		echo "- Invalid package: <b>$git_package</b> $nl";
	}
}

?>
</pre>
</body>
</html>

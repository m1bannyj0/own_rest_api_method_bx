<?

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class local_refreshnews extends CModule
{
	var $MODULE_ID = 'local.refreshnews';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__ . "/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("LOCAL_INFOBLOCK_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("LOCAL_INFOBLOCK_MODULE_DESC");

		$this->PARTNER_NAME = getMessage("LOCAL_INFOBLOCK_PARTNER_NAME");
		$this->PARTNER_URI = getMessage("LOCAL_INFOBLOCK_PARTNER_URI");

		$this->exclusionAdminFiles = array(
			'..',
			'.',
			'menu.php',
			'operation_description.php',
			'task_description.php'
		);
	}

	function InstallDB($arParams = array())
	{
		$this->createNecessaryIblocks();
		$this->createNecessaryUserFields();
	}

	function createNecessaryIblocks()
	{
		return true;
	}

	function createNecessaryUserFields()
	{
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		\Bitrix\Main\Config\Option::delete($this->MODULE_ID);
		$this->deleteNecessaryIblocks();
		$this->deleteNecessaryUserFields();
	}

	function deleteNecessaryIblocks()
	{
		return true;
	}

	function deleteNecessaryUserFields()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		$path = $this->GetPath() . "/install/components";
		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
			CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
		}

		$path = $this->GetPath() . "/install/wizards";
		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
			CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/wizards", true, true);
		}

		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
			CopyDirFiles($this->GetPath() . "/install/admin/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item, $this->exclusionAdminFiles))
						continue;
					file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $item,
						'<' . '? require($_SERVER["DOCUMENT_ROOT"]."' . $this->GetPath(true) . '/admin/' . $item . '");?' . '>');
				}
				closedir($dir);
			}
		}

		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/install/files')) {
			$this->copyArbitraryFiles();
		}

		return true;
	}

	function GetPath($notDocumentRoot = false)
	{
		if ($notDocumentRoot) {
			return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
		} else {
			return dirname(__DIR__);
		}
	}

	function copyArbitraryFiles()
	{
		$rootPath = $_SERVER["DOCUMENT_ROOT"];
		$localPath = $this->GetPath() . '/install/files';

		$dirIterator = new RecursiveDirectoryIterator($localPath, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $object) {
			$destPath = $rootPath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			($object->isDir()) ? mkdir($destPath) : copy($object, $destPath);
		}
	}

	function UnInstallFiles()
	{
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/' . $this->MODULE_ID . '/');

		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/wizards/local/');

		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . $this->GetPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item, $this->exclusionAdminFiles))
						continue;
					\Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item);
				}
				closedir($dir);
			}
		}

		if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/install/files')) {
			$this->deleteArbitraryFiles();
		}

		return true;
	}

	function deleteArbitraryFiles()
	{
		$rootPath = $_SERVER["DOCUMENT_ROOT"];
		$localPath = $this->GetPath() . '/install/files';

		$dirIterator = new RecursiveDirectoryIterator($localPath, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $object) {
			if (!$object->isDir()) {
				$file = str_replace($localPath, $rootPath, $object->getPathName());
				\Bitrix\Main\IO\File::deleteFile($file);
			}
		}
	}

	function createNecessaryMailEvents()
	{
		return true;
	}

	function deleteNecessaryMailEvents()
	{
		return true;
	}

	function getSitesIdsArray()
	{
		$ids = array();
		$rsSites = CSite::GetList($by = "sort", $order = "desc");
		while ($arSite = $rsSites->Fetch()) {
			$ids[] = $arSite["LID"];
		}

		return $ids;
	}

	function DoInstall()
	{

		global $APPLICATION;
		if ($this->isVersionD7()) {
			\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

			if (!$this->isTypeSite()) {
//                $this->InstallDB();
			}

			//$this->createNecessaryMailEvents();
			$this->InstallEvents();
			//$this->InstallFiles();
		} else {
			$APPLICATION->ThrowException(Loc::getMessage("LOCAL_INFOBLOCK_INSTALL_ERROR_VERSION"));
		}

		$APPLICATION->IncludeAdminFile(Loc::getMessage("LOCAL_INFOBLOCK_INSTALL"), $this->GetPath() . "/install/step.php");
	}

	function isVersionD7()
	{
		return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
	}

	function isTypeSite()
	{
		return \Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/install/wizards');
	}

	function InstallEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			"rest"
			, "OnRestServiceBuildDescription"
			, $this->MODULE_ID
			, 'Local\Refreshnews\EventHandlers\OnRestServiceBuildDescriptionHandler'
			, "OnRestServiceBuildDescription"
		);
	}

	function DoUninstall()
	{

		global $APPLICATION;

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		//$this->UnInstallFiles();
		//$this->deleteNecessaryMailEvents();
		$this->UnInstallEvents();

		if ($request["savedata"] != "Y") {
			// $this->UnInstallDB();
		}
		\Bitrix\Main\Config\Option::delete($this->MODULE_ID);

		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

		$APPLICATION->IncludeAdminFile(Loc::getMessage("LOCAL_INFOBLOCK_UNINSTALL"), $this->GetPath() . "/install/unstep.php");
	}

	function UnInstallEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			"rest"
			, "OnRestServiceBuildDescription"
			, $this->MODULE_ID
			, 'Local\Refreshnews\EventHandlers\OnRestServiceBuildDescriptionHandler'
			, "OnRestServiceBuildDescription"
		);

		$connection = Bitrix\Main\Application::getConnection()->query('DELETE LOW_PRIORITY QUICK
 			 FROM b_module_to_module
  				WHERE TO_MODULE_ID = "' . $this->MODULE_ID . '"
  				LIMIT 1000;');

	}
}

?>

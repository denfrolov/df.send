<?

class df_send extends CModule
{
	var $MODULE_ID = "df.send";
	var $MODULE_NAME = "Модуль для отправки форм";
	var $MODULE_DESCRIPTION = "Модуль для отправки форм";
	var $MODULE_VERSION = "1.0";
	var $MODULE_VERSION_DATE = "2023-04-09 12:00:00";
	var $PARTNER_NAME = 'Denis Frolov';
	var $PARTNER_URI = 'https://df7.ru';




	public function InstallFiles(): bool
	{
		CopyDirFiles(__DIR__ . "/components",
			$_SERVER["DOCUMENT_ROOT"] . "/local/components", true, true);
		return true;
	}

	public function UnInstallFiles(): bool
	{
		DeleteDirFilesEx("/local/components/df/df.send");
		return true;
	}
	
	public function DoInstall(): void
	{
		$this->InstallFiles();
		\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
	}
	
	public function DoUninstall(): void
	{
		$this->UnInstallFiles();
		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
	}
}
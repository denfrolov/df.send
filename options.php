<?php
/*
 * Файл local/modules/scrollup/options.php
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

// получаем идентификатор модуля
$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialchars($request['mid'] != '' ? $request['mid'] : $request['id']);
// подключаем наш модуль
Loader::includeModule($module_id);

/*
 * Параметры модуля со значениями по умолчанию
 */
$aTabs = array(
	array(
		/*
		 * Первая вкладка «Основные настройки»
		 */
		'DIV' => 'edit1',
		'TAB' => "Основные настройки",
		'TITLE' => "Основные настройки",
		'OPTIONS' => array(
			array(
				'googleRecaptchaPublic',
				"Публичный ключ (Google reCAPTCHA)",
				'Y',
				array('text', 70)
			),
			array(
				'googleRecaptchaSecret',
				"Секретный ключ (Google reCAPTCHA)",
				'Y',
				array('text', 70)
			),
		)
	),
);

/*
 * Создаем форму для редактирвания параметров модуля
 */
$tabControl = new CAdminTabControl(
	'tabControl',
	$aTabs
);

$tabControl->begin();
?>
	<form action="<?= $APPLICATION->getCurPage(); ?>?mid=<?= $module_id; ?>&lang=<?= LANGUAGE_ID; ?>" method="post">
		<?= bitrix_sessid_post(); ?>
		<?php
		foreach ($aTabs as $aTab) { // цикл по вкладкам
			if ($aTab['OPTIONS']) {
				$tabControl->beginNextTab();
				__AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
			}
		}
		$tabControl->buttons();
		?>
		<input type="submit" name="apply"
		       value="Применить" class="adm-btn-save"/>
		<input type="submit" name="default"
		       value="Отменить"/>
	</form>

<?php
$tabControl->end();

/*
 * Обрабатываем данные после отправки формы
 */
if ($request->isPost() && check_bitrix_sessid()) {
	
	foreach ($aTabs as $aTab) { // цикл по вкладкам
		foreach ($aTab['OPTIONS'] as $arOption) {
			if (!is_array($arOption)) { // если это название секции
				continue;
			}
			if ($arOption['note']) { // если это примечание
				continue;
			}
			if ($request['apply']) { // сохраняем введенные настройки
				$optionValue = $request->getPost($arOption[0]);
				Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(',', $optionValue) : $optionValue);
			} elseif ($request['default']) { // устанавливаем по умолчанию
				Option::set($module_id, $arOption[0], $arOption[2]);
			}
		}
	}
	
	LocalRedirect($APPLICATION->getCurPage() . '?mid=' . $module_id . '&lang=' . LANGUAGE_ID);
	
}
?>
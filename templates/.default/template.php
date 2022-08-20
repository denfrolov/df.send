<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 */

use \Bitrix\Main\Localization\Loc;

$messages = Loc::loadLanguageFile(__FILE__);
if (SEND_JS != 'Y') {
	\Bitrix\Main\Page\Asset::getInstance()->addJs($this->GetFolder() . '/send.js');
	define('SEND_JS', 'Y');
}
?>
<? if ($arResult['ITEMS']): ?>
	<? if ($arResult['POSTED'] != 'Y'): ?>
		<form action="<?= $APPLICATION->GetCurPage() ?>" method="post" class="df_ajax_form" enctype="multipart/form-data">
			<input type="hidden" name="IBLOCK_ID" value="<?= $arParams['IBLOCK_ID'] ?>">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<div class="form-group">
					<label for="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>">
						<span><?= $arItem['NAME'] ?></span>
						<? if ($arItem['IS_REQUIRED'] == 'Y'): ?>
							<i style="color:red;">*</i>
						<? endif; ?>
					</label>
					<? if ($arItem['TYPE'] == 'textarea'): ?>
						<textarea class="form-control" name="<?= $arItem['CODE'] ?>"
						          id="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>"<?= $arItem['IS_REQUIRED'] == 'Y' ? ' required' : '' ?>></textarea>
					<? elseif ($arItem['TYPE'] == 'select'): ?>
						<select
							name="<?= $arItem['CODE'] ?><?= $arItem['MULTIPLE'] == 'Y' ? '[]' : '' ?>"
							<?= $arItem['MULTIPLE'] == 'Y' ? ' multiple' : '' ?> class="form-control">
							<option value="0" selected disabled>Не выбрано</option>
							<? foreach ($arItem['VALUES'] as $i2 => $arItem2): ?>
								<option value="<?= $arItem2['ID'] ?>"><?= $arItem2['VALUE'] ?></option>
							<? endforeach; ?>
						</select>
					<? else: ?>
						<input type="<?= $arItem['TYPE'] ?>" class="form-control"
						       name="<?= $arItem['CODE'] . ($arItem['MULTIPLE'] == 'Y' ? '[]' : '') ?>"<?= $arItem['MULTIPLE'] == 'Y' ? ' multiple' : '' ?>
						       id="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>"<?= $arItem['IS_REQUIRED'] == 'Y' ? ' required' : '' ?>>
					<? endif; ?>
				</div>
			<? endforeach; ?>
			<? if($arParams['USE_RECAPTCHA'] == 'Y'): ?>
				<input type="hidden" name="recaptcha_response" class="recaptcha_response">
			<? endif; ?>
			<button type="submit" class="btn btn-primary"><?= $arParams['BUTTON_TEXT'] ?></button>
		</form>
	<? else: ?>
		<div class="df_result"> Сообщение отправлено!</div>
	<? endif; ?>
<? endif; ?>

<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var object $component
 */

use \Bitrix\Main\Localization\Loc;

CJSCore::Init(array("fx"));
global $APPLICATION;
if ($arResult['recaptchaPublicKey'] && $arResult['recaptchaSecretKey'] && $arResult['useRecaptcha'] == 'Y' && !defined('RECAPTCHA_JS')) {
	Bitrix\Main\Page\Asset::getInstance()->addJs('https://www.google.com/recaptcha/api.js?render=' . $arResult['recaptchaPublicKey']);
}
if (!defined('SEND_JS')) {
	\Bitrix\Main\Page\Asset::getInstance()->addJs($component->getPath() . '/send.js');
	define('SEND_JS', true);
}
?>
<? if ($arResult['ITEMS']): ?>
	<h3 class="h3"><?= $arParams['FORM_TITLE'] ?></h3>
	<form action="<?= $APPLICATION->GetCurPage() ?>" method="post" class="df_ajax_form df-form"
	      enctype="multipart/form-data">
		<input type="hidden" name="IBLOCK_ID" value="<?= $arParams['IBLOCK_ID'] ?>">
		<input type="hidden" name="signedParameters" value="<?= $this->getComponent()->getSignedParameters() ?>">
		<? foreach ($arResult['ITEMS'] as $arItem): ?>
			<?php if ($arItem['TYPE'] == 'hidden'): ?>
				<input type="hidden" name="<?= $arItem['CODE'] ?>"
				       value="<?= stripos($arItem['CODE'], 'link') !== false ? $APPLICATION->GetCurPage() : '' ?>">
			<?php endif ?>
			<div class="df-form__group">
				<label class="df-form__label" for="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>">
					<span><?= $arItem['NAME'] ?></span>
					<? if ($arItem['IS_REQUIRED'] == 'Y'): ?>
						<i style="color:red;">*</i>
					<? endif; ?>
				</label>
				<? if ($arItem['TYPE'] == 'textarea'): ?>
					<textarea class="df-form__field" name="<?= $arItem['CODE'] ?>"
					          id="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>"<?= $arItem['IS_REQUIRED'] == 'Y' ? ' required' : '' ?>></textarea>
				<? elseif ($arItem['TYPE'] == 'select'): ?>
					<select
						name="<?= $arItem['CODE'] ?><?= $arItem['MULTIPLE'] == 'Y' ? '[]' : '' ?>"
						<?= $arItem['MULTIPLE'] == 'Y' ? ' multiple' : '' ?> class="df-form__field">
						<option value="0" selected disabled>Не выбрано</option>
						<? foreach ($arItem['VALUES'] as $i2 => $arItem2): ?>
							<option value="<?= $arItem2['ID'] ?>"><?= $arItem2['VALUE'] ?></option>
						<? endforeach; ?>
					</select>
				<? else: ?>
					<input type="<?= $arItem['TYPE'] ?>" class="df-form__field"
					       name="<?= $arItem['CODE'] . ($arItem['MULTIPLE'] == 'Y' ? '[]' : '') ?>"<?= $arItem['MULTIPLE'] == 'Y' ? ' multiple' : '' ?>
					       id="<?= $arItem['CODE'] ?>_<?= $arItem['ID'] ?>"<?= $arItem['IS_REQUIRED'] == 'Y' ? ' required' : '' ?>>
				<? endif; ?>
			</div>
		<? endforeach; ?>
		<? if ($arResult['recaptchaPublicKey'] && $arResult['recaptchaPublicKey']): ?>
			<input type="hidden" name="recaptcha_response" class="recaptcha_response">
		<? endif; ?>
		<div class="df-form__group"><?= $arParams['AGREE_TEXT'] ?></div>
		<button type="submit" class="df-form__btn"><?= $arParams['BUTTON_TEXT'] ?></button>
	</form>
<? endif; ?>

<script type="text/javascript">
	<?php if ($arResult['recaptchaPublicKey'] && $arResult['recaptchaPublicKey'] && $arResult['useRecaptcha'] == 'Y' && !defined('RECAPTCHA_JS')): ?>
	grecaptcha.ready(function () {
		grecaptcha.execute('<?= $arResult['recaptchaPublicKey'] ?>', {action: 'contact'}).then(function (token) {
			let recaptchaResponse = document.getElementsByClassName('recaptcha_response');
			for (let recaptchaResponseElement of recaptchaResponse) {
				recaptchaResponseElement.value = token
			}
		});
	});
	<?php
	define('RECAPTCHA_JS', true);
	endif ?>
</script>

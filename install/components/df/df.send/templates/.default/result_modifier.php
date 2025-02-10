<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var array $arParams
 * @var array $arResult
 */
$fullUrl = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
foreach ($arResult['ITEMS'] as $i => &$arItem) {
	if (stripos($arItem['CODE'], 'link') !== false) {
		$arItem['VALUE'] = $fullUrl;
	}
}

<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */
if (!CModule::IncludeModule("iblock"))
	return;


$arTypesEx = CIBlockParameters::GetIBlockTypes(array("-" => " "));

$arIBlocks = array();
$db_iblock = CIBlock::GetList(array("SORT" => "ASC"), array("SITE_ID" => $_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"] != "-" ? $arCurrentValues["IBLOCK_TYPE"] : "")));
while ($arRes = $db_iblock->Fetch())
	$arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];


$arProperty = array();
$rsProp = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", "IBLOCK_ID" => (isset($arCurrentValues["IBLOCK_ID"]) ? $arCurrentValues["IBLOCK_ID"] : $arCurrentValues["ID"])));
while ($arr = $rsProp->Fetch()) {
	$arProperty[$arr["CODE"]] = "[" . $arr["CODE"] . "] " . $arr["NAME"];
}


$arComponentParameters = array(
	"GROUPS" => array(),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => 'Тип инфоблока',
			"TYPE" => "LIST",
			"VALUES" => $arTypesEx,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => "Инфоблок",
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"DEFAULT" => '={$_REQUEST["ID"]}',
			"ADDITIONAL_VALUES" => "Y",
			"REFRESH" => "Y",
		),
		"USE_RECAPTCHA" => array(
			"PARENT" => "BASE",
			"NAME" => "Использовать Google Recaptcha v3",
			"TYPE" => "CHECKBOX",
		),
		"DEACTIVATE" => array(
			"PARENT" => "BASE",
			"NAME" => "Деактивировать элемент",
			"TYPE" => "CHECKBOX",
		),
		"FORM_TITLE" => array(
			"PARENT" => "BASE",
			"NAME" => "Заголовок формы",
			"TYPE" => "STRING",
			"DEFAULT" => 'Напишите нам'
		),
		"BUTTON_TEXT" => array(
			"PARENT" => "BASE",
			"NAME" => "Текст кнопки",
			"TYPE" => "STRING",
			"DEFAULT" => 'Отправить'
		)
	)
);

if ($arProperty) {
	$arComponentParameters['PARAMETERS']['REQUIRED_PROPERTIES'] = array(
		"PARENT" => "BASE",
		"NAME" => 'Выберите обязательные поля',
		"TYPE" => "LIST",
		'MULTIPLE' => 'Y',
		"VALUES" => $arProperty,
	);
}
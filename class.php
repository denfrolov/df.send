<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

/* Добавить в init.php для гугл-рекаптчи
define('GOOGLE_RECAPTCHA_KEY', 'YOUR_KEY');
define('GOOGLE_RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY');
 */

if (!function_exists('getCurl')) {
	function getCurl($data, $url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$return = curl_exec($ch);
		curl_close($ch);
		return json_decode($return, true);
	}
}


class dfForms extends CBitrixComponent
{
	function getElements($arParams)
	{
		CModule::IncludeModule("iblock");
		CModule::IncludeModule("main");
		$arFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'ACTIVE' => 'Y',
		);
		$rsProperty = CIBlockProperty::GetList(
			array(),
			$arFilter
		);
		while ($arProperty = $rsProperty->Fetch()) {
			if (in_array($arProperty['CODE'], $arParams['REQUIRED_PROPERTIES'])) {
				$arProperty['IS_REQUIRED'] = 'Y';
			} else {
				$arProperty['IS_REQUIRED'] = 'N';
			}
			if ($arProperty['USER_TYPE'] != 'HTML' && $arProperty['PROPERTY_TYPE'] != 'L') {
				if (stripos($arProperty['CODE'], 'mail') || stripos($arProperty['NAME'], Loc::getMessage("EMAIL"))) {
					$arProperty['TYPE'] = 'email';
				} elseif (stripos($arProperty['CODE'], 'phone') || stripos($arProperty['NAME'], Loc::getMessage("PHONE"))) {
					$arProperty['TYPE'] = 'tel';
				} elseif ($arProperty['PROPERTY_TYPE'] == 'F') {
					$arProperty['TYPE'] = 'file';
				} elseif (stripos($arProperty['CODE'], 'date') !== false) {
					$arProperty['TYPE'] = 'date';
				} elseif (stripos($arProperty['CODE'], '_hidden') !== false) {
					$arProperty['TYPE'] = 'hidden';
				} else {
					$arProperty['TYPE'] = 'text';
				}
			} else {
				if ($arProperty['PROPERTY_TYPE'] == 'L') {
					$arProperty['TYPE'] = 'select';
					$property_enums = CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "VALUE" => "ASC"), array("IBLOCK_ID" => $arParams['IBLOCK_ID'], "CODE" => $arProperty['CODE']));
					while ($enum_fields = $property_enums->GetNext()) {
						$arProperty['VALUES'][] = $enum_fields;
					}
				} else {
					$arProperty['TYPE'] = 'textarea';
				}
			}
			$arResult['ITEMS'][] = $arProperty;
		}
		usort($arResult['ITEMS'], function ($a, $b) {
			return ($a['SORT'] - $b['SORT']);
		});
		
		if ($_REQUEST['IBLOCK_ID'] == $arParams['IBLOCK_ID'] && $arResult['ITEMS'] && !defined('POSTED')) {
			if ($arParams['USE_RECAPTCHA'] == 'Y' && GOOGLE_RECAPTCHA_SECRET_KEY) {
				$recaptcha = getCurl(array(
					'secret' => GOOGLE_RECAPTCHA_SECRET_KEY,
					'response' => $_POST['recaptcha_response']
				), 'https://www.google.com/recaptcha/api/siteverify');
				if ($recaptcha['success'] === false) {
					die();
				}
			}
			$el = new CIBlockElement;
			$arProps = array();
			foreach ($arResult['ITEMS'] as $arItem) {
				if ($_REQUEST[$arItem['CODE']] != '') {
					$arProps[$arItem['CODE']] = $_REQUEST[$arItem['CODE']];
				}
			}
			$arFilesID = array();
			if ($_FILES) {
				foreach ($_FILES as $k => $FILE) {
					if (is_array($FILE['tmp_name'])) {
						foreach ($FILE['tmp_name'] as $kf => $item) {
							$fileArray = array(
								'name' => $FILE['name'][$kf],
								'size' => $FILE['size'][$kf],
								'tmp_name' => $FILE['tmp_name'][$kf],
								'type' => $FILE['type'][$kf],
							);
							$arFilesID[] = $arProps[$k][] = CFile::SaveFile($fileArray, "mailatt");
						}
					} else {
						$arFilesID[] = $arProps[$k][] = CFile::SaveFile($FILE, "mailatt");
					}
				}
			}
			
			$arLoadProductArray = array(
				"MODIFIED_BY" => 1, // элемент изменен текущим пользователем
				"IBLOCK_ID" => $arParams['IBLOCK_ID'],
				"PROPERTY_VALUES" => $arProps,
				"NAME" => ($arProps['NAME'] ? $arProps['NAME'] : $arProps['name']) . ' от ' . date("Y-m-d H:i:s"),
				"ACTIVE" => "Y"
			);
			if ($arParams['DEACTIVATE'] == 'Y') {
				$arLoadProductArray['ACTIVE'] = 'N';
			}
			
			if ($arResult['ID'] = $elementID = $el->Add($arLoadProductArray)) {
				$c = true;
				$message = '';
				unset($arProps['IBLOCK_ID']);
				unset($arProps['FILE']);
				foreach ($arProps as $key => $value) {
					$key_lang = array_search($key, array_column($arResult['ITEMS'], 'CODE'));
					if ($value != "" && $arResult['ITEMS'][$key_lang]['PROPERTY_TYPE'] != 'F') {
						$key_lang_name = $arResult['ITEMS'][$key_lang]['NAME'];
						if ($arResult['ITEMS'][$key_lang]['PROPERTY_TYPE'] == 'L') {
							$property_enums = CIBlockPropertyEnum::GetList(array("NAME" => "ASC", "SORT" => "ASC"), array("IBLOCK_ID" => $arParams['IBLOCK_ID'], "ID" => $value));
							$value_array = array();
							while ($enum_fields = $property_enums->GetNext()) {
								$value_array[] = $enum_fields["VALUE"];
							}
							$value = '';
							foreach ($value_array as $item) {
								$value .= $item . '<br>';
							}
						} elseif ($arResult['ITEMS'][$key_lang]['PROPERTY_TYPE'] == 'E') {
							$arSort = array("SORT" => "ASC");
							$arFilter = array("ACTIVE" => "Y", "ID" => $value);
							$arSelectFields = array("IBLOCK_ID", "ID", "ACTIVE", "NAME", 'DETAIL_PAGE_URL');
							$rsElements = CIBlockElement::GetList($arSort, $arFilter, FALSE, FALSE, $arSelectFields);
							if ($arElementObj = $rsElements->GetNextElement()) {
								$arElement = $arElementObj->GetFields();
								$value = '<a href="' . $arElement['DETAIL_PAGE_URL'] . '">' . $arElement['NAME'] . '</a>';
							}
						}
						$message .= "
			" . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
				<td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key_lang_name</b></td>
				<td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
			</tr>
			";
					}
				}
				$hasEventType = false;
				$arFilter = array(
					"TYPE_ID" => 'DF_UNIVERSAL'
				);
				$rsMess = CEventMessage::GetList($by = "site_id", $order = "desc", $arFilter);
				while ($arMess = $rsMess->GetNext()) {
					$hasEventType = true;
				}
				if (!$hasEventType) {
					$et = new CEventType;
					$et->Add(array(
						"LID" => 'ru',
						"EVENT_NAME" => 'DF_UNIVERSAL',
						"NAME" => 'Универсальный шаблон от Дениса Фролова',
						"DESCRIPTION" => '#TEXT# - содержит все свойства
#TARGET# - цель заявки
						'
					));
					
					$em = new CEventMEssage;
					$arFields = array(
						'ACTIVE' => 'Y',
						'LID' => SITE_ID,
						'BODY_TYPE' => 'html',
						'EVENT_NAME' => 'DF_UNIVERSAL',
						'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
						'EMAIL_TO' => '#DEFAULT_EMAIL_FROM#',
						'SUBJECT' => '#SITE_NAME#: #SUBJECT#',
						'MESSAGE' => '#TEXT#'
					);
					$em->Add($arFields);
				}
				
				$message = "<table style='width: 100%;'>$message</table>
			<a style='display:inline-block;margin-top: 30px;' href='/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . $arParams['IBLOCK_ID'] . "&type=" . $arParams['IBLOCK_TYPE'] . "&lang=ru&ID=" . $elementID . "&find_section_section=0&WF=Y'>Редактировать элемент</a>
";
				
				
				$res = CIBlock::GetByID($arParams['IBLOCK_ID']);
				$ar_res = $res->GetNext();
				\Bitrix\Main\Mail\Event::send(array(
					'EVENT_NAME' => 'DF_UNIVERSAL',
					'LID' => 's1',
					'C_FIELDS' => array(
						'TEXT' => $message,
						'SUBJECT' => $ar_res['NAME']
					),
					'FILE' => $arFilesID,
				));
			} else {
				echo "Error: " . $el->LAST_ERROR;
			}
			$arResult['POSTED'] = 'Y';
			define('POSTED', 'Y');
		}
		
		
		return $arResult;
	}
	
	public function executeComponent()
	{
		$this->arResult = array_merge($this->arResult, $this->getElements($this->arParams));
		$this->includeComponentTemplate();
	}
}

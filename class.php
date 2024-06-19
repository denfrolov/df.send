<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

use Bitrix\Main\Errorable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use CBitrixComponent;


class dfForms extends CBitrixComponent implements Controllerable, Errorable
{
	
	protected ErrorCollection $errorCollection;
	
	
	protected function listKeysSignedParameters(): array
	{
		return [
			'IBLOCK_ID',
			'IBLOCK_TYPE',
			'REQUIRED_PROPERTIES',
			'USE_RECAPTCHA',
			'SUCCESS_TEXT',
			'DEACTIVATE',
			'GOOGLE_RECAPTCHA_KEY',
			'GOOGLE_RECAPTCHA_SECRET_KEY',
		];
	}
	
	public function onPrepareComponentParams($arParams): array
	{
		$this->errorCollection = new ErrorCollection();
		return $arParams;
	}
	
	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}
	
	public function getErrorByCode($code): Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}
	
	// Описываем действия
	public function configureActions(): array
	{
		return [
			'sendMessage' => [
				'-prefilters' => [
					ActionFilter\Authentication::class,
				]
			]
		];
	}
	
	public function sendMessageAction(): array
	{
		$arParams = $this->arParams;
		$arResult = $this->getElements($this->arParams);
		if ($_REQUEST['IBLOCK_ID'] == $arParams['IBLOCK_ID']) {
			if ($arParams['USE_RECAPTCHA'] == 'Y' && $arParams['GOOGLE_RECAPTCHA_SECRET_KEY'] && $arParams['GOOGLE_RECAPTCHA_KEY']) {
				$httpClient = new \Bitrix\Main\Web\HttpClient();
				$recaptcha = $httpClient->post('https://www.google.com/recaptcha/api/siteverify', array(
					'secret' => $arParams['GOOGLE_RECAPTCHA_SECRET_KEY'],
					'response' => $_POST['recaptcha_response']
				));
				$recaptcha = json_decode($recaptcha, true);
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
			
			if ($elementID = $el->Add($arLoadProductArray)) {
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
		}
		return array('success_text' => $arParams['SUCCESS_TEXT'], $arParams);
	}
	
	
	function getElements($arParams): array
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
			if ($arParams['REQUIRED_PROPERTIES'] && in_array($arProperty['CODE'], $arParams['REQUIRED_PROPERTIES'])) {
				$arProperty['IS_REQUIRED'] = 'Y';
			} else {
				$arProperty['IS_REQUIRED'] = 'N';
			}
			if ($arProperty['USER_TYPE'] != 'HTML' && $arProperty['PROPERTY_TYPE'] != 'L') {
				if (stripos($arProperty['CODE'], 'mail') !== false || stripos($arProperty['NAME'], Loc::getMessage("EMAIL")) !== false) {
					$arProperty['TYPE'] = 'email';
				} elseif (stripos($arProperty['CODE'], 'phone') !== false || stripos($arProperty['NAME'], Loc::getMessage("PHONE")) !== false) {
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
		
		return $arResult;
	}
	
	public function executeComponent(): void
	{
		$this->arResult = array_merge($this->arResult, $this->getElements($this->arParams));
		$this->includeComponentTemplate();
	}
}

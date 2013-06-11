<?php
namespace OBX\Core\Wizard;
use \OBX\Core\Tools;
class ImportIBlock
{
	protected $_constructOK = false;
	protected $_iblockID = 0;
	protected $_iblockCode = null;
	protected $_iblockXmlID = null;
	protected $_iblockType = null;
	protected $_iblockXMLFile = null;
	protected $_iblockXMLDir = null;
	protected $_bReinstallData = null;

	static protected $_arConfig = array(
		'IBLOCK_TYPE' => array(),
		'IBLOCK' => array()
	);
	static protected $_bConfigInitialized = false;

	static protected $_arDefaultIBlockFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => array(
			'IBLOCK_SECTION' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'ACTIVE' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'Y',
			),
			'ACTIVE_FROM' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '=today',
			),
			'ACTIVE_TO' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SORT' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'NAME' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => '',
			),
			'PREVIEW_PICTURE' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'FROM_DETAIL' => 'N',
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'DELETE_WITH_DETAIL' => 'N',
					'UPDATE_WITH_DETAIL' => 'N',
				),
			),
			'PREVIEW_TEXT_TYPE' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'PREVIEW_TEXT' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'DETAIL_PICTURE' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
				),
			),
			'DETAIL_TEXT_TYPE' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'DETAIL_TEXT' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'XML_ID' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'CODE' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => array(
					'UNIQUE' => 'Y',
					'TRANSLITERATION' => 'Y',
					'TRANS_LEN' => 100,
					'TRANS_CASE' => 'L',
					'TRANS_SPACE' => '_',
					'TRANS_OTHER' => '_',
					'TRANS_EAT' => 'Y',
					'USE_GOOGLE' => 'Y',
				),
			),
			'TAGS' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_NAME' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_PICTURE' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'FROM_DETAIL' => 'N',
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'DELETE_WITH_DETAIL' => 'N',
					'UPDATE_WITH_DETAIL' => 'N',
				),
			),
			'SECTION_DESCRIPTION_TYPE' => array(
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'SECTION_DESCRIPTION' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_DETAIL_PICTURE' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
				),
			),
			'SECTION_XML_ID' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_CODE' => array(
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'UNIQUE' => 'N',
					'TRANSLITERATION' => 'N',
					'TRANS_LEN' => 100,
					'TRANS_CASE' => 'L',
					'TRANS_SPACE' => '_',
					'TRANS_OTHER' => '_',
					'TRANS_EAT' => 'Y',
					'USE_GOOGLE' => 'N',
				),
			),
		),
	);

	public function __construct($iblockCode, $configFilePath) {
		$arConfigFull = self::readConfig($configFilePath);
		if( $arConfigFull === null || !array_key_exists($iblockCode, $arConfigFull['IBLOCK']) ) {
			return;
		}
		$arConfig = &$arConfigFull['IBLOCK'][$iblockCode];
		$this->_iblockCode = $iblockCode;
		$this->_iblockXmlID = $arConfig['XML_ID'];
		$this->_iblockType = $arConfig['IBLOCK_TYPE_ID'];
		$this->_iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH.'/xml/'.LANGUAGE_ID.'/'.$arConfig['XML_FILE'];
		$this->_iblockXMLDir = WIZARD_SERVICE_RELATIVE_PATH.'/xml/'.LANGUAGE_ID.'/'.str_replace('.xml', '_files', $arConfig['XML_FILE']);
		if( !is_file($_SERVER['DOCUMENT_ROOT'].$this->_iblockXMLFile) || !file_exists($_SERVER['DOCUMENT_ROOT'].$this->_iblockXMLFile) ) {
			return;
		}
		if( !is_dir($_SERVER['DOCUMENT_ROOT'].$this->_iblockXMLDir) || !file_exists($_SERVER['DOCUMENT_ROOT'].$this->_iblockXMLDir) ) {
			return;
		}
		$rsIBlock = \CIBlock::GetList(array(), array("XML_ID" => $this->_iblockXmlID, "TYPE" => $this->_iblockType));
		if( ($arIBlock = $rsIBlock->Fetch()) ) {
			$this->_iblockID = $arIBlock['ID'];
		}
		$this->_constructOK = true;
	}

	static public function generateXmlID($iblockCode) {
		return md5('obx_wiz_ib_xml_id'.$iblockCode);
	}

	static public function readConfig($configFilePath) {
		if( !file_exists($configFilePath) ) {
			return null;
		}
		if( !self::$_bConfigInitialized ) {
			if( !\CModule::IncludeModule('iblock') ) return false;
			if( !\CModule::IncludeModule('obx.core') ) return false;
			$arRawConfig = require_once $configFilePath;
			if( !array_key_exists('IBLOCK_TYPE', $arRawConfig) ) {
				return null;
			}
			if( !array_key_exists('IBLOCK', $arRawConfig) ) {
				return null;
			}
			foreach($arRawConfig['IBLOCK_TYPE'] as $typeID => &$arRawIBType) {
				$arRawIBType['ID'] = $arRawIBType;
				if(
					!array_key_exists('SECTIONS', $arRawIBType)
					|| (
						$arRawIBType['SECTIONS'] != 'Y'
						&& $arRawIBType['SECTIONS'] != 'N'
					)
				) {
					$arRawIBType['SECTIONS'] = 'Y';
				}
				if(
					!array_key_exists('IN_RSS', $arRawIBType)
					|| (
						$arRawIBType['IN_RSS'] != 'Y'
						&& $arRawIBType['IN_RSS'] != 'N'
					)
				) {
					$arRawIBType['IN_RSS'] = 'Y';
				}
				if( !array_key_exists('IN_RSS', $arRawIBType) || !is_numeric($arRawIBType['IN_RSS'])) {
					$arRawIBType['IN_RSS'] = 100;
				}
				$arRawIBType['IS_EXISTS'] = false;
				$arRawIBType['__IBLOCKS'] = array();
				self::$_arConfig['IBLOCK_TYPE'][$typeID] = $arRawIBType;
			}
			foreach($arRawConfig['IBLOCK'] as $iblockCode => &$arRawIB) {
				if( !array_key_exists('IBLOCK_TYPE_ID', $arRawIB) || !array_key_exists($arRawIB['IBLOCK_TYPE_ID'], self::$_arConfig['IBLOCK_TYPE']) ) {
					continue;
				}
				if( !array_key_exists('XML_ID', $arRawIB) ) {
					$arRawIB['XML_ID'] = self::generateXmlID($iblockCode);
				}
				self::$_arConfig['IBLOCK'][$iblockCode] = $arRawIB;
				self::$_arConfig['IBLOCK_TYPE'][$arRawIB['IBLOCK_TYPE_ID']]['__IBLOCKS'][] = &self::$_arConfig['IBLOCK'][$iblockCode];
			}
			foreach($arRawConfig['IBLOCK_TYPE'] as $typeID => &$arRawIBType) {
				if( count(self::$_arConfig['IBLOCK_TYPE'][$typeID]['__IBLOCKS']) < 1 ) {
					unset(self::$_arConfig['IBLOCK_TYPE'][$typeID]);
				}
			}

			self::$_bConfigInitialized = true;
		}
		return self::$_arConfig;
	}

	static protected function getLanguages() {
		static $bInit = false;
		static $arLanguages = Array();
		if(!$bInit) {
			$rsLanguage = \CLanguage::GetList($by, $order, array());
			while( $arLanguage = $rsLanguage->Fetch() ) {
				$arLanguages[] = $arLanguage['LID'];
			}
			$bInit = true;
		}
		return $arLanguages;
	}

	static public function getIBlockSites($iblockID) {
		$db_res = \CIBlock::GetSite($iblockID);
		while ($res = $db_res->Fetch()) {
			$arSites[] = $res["LID"];
		}
		return $arSites;
	}

	static protected function setIBCombinedList() {
		static $bInit = false;
		if(!$bInit) {
			\COption::SetOptionString('iblock','combined_list_mode','Y');
			$bInit = true;
		}
	}

	static protected function __createIBlockType($typeID) {
		if( !array_key_exists($typeID, self::$_arConfig['IBLOCK_TYPE']) ) {
			return false;
		}
		$arType = array(
			'ID' => $typeID,
			'SECTIONS' => self::$_arConfig['IBLOCK_TYPE'][$typeID]['SECTIONS'],
			'IN_RSS' => self::$_arConfig['IBLOCK_TYPE'][$typeID]['IN_RSS'],
			'SORT' => self::$_arConfig['IBLOCK_TYPE'][$typeID]['SORT'],
			'LANG' => Array(),
		);
		if(self::$_arConfig['IBLOCK_TYPE'][$typeID]['IS_EXISTS'] == true) {
			return true;
		}
		$dbType = \CIBlockType::GetList(Array(),Array('=ID' => $arType['ID']));
		if($dbType->Fetch()) {
			self::$_arConfig['IBLOCK_TYPE'][$typeID]['IS_EXISTS'] = true;
			return true;
		}
		$arLanguages = self::getLanguages();
		foreach($arLanguages as $languageID)
		{
			\WizardServices::IncludeServiceLang('_iblock_types.php', $languageID);
			$code = strtoupper($arType['ID']);
			$arType['LANG'][$languageID]['NAME'] = GetMessage($code.'_TYPE_NAME');
			$arType['LANG'][$languageID]['ELEMENT_NAME'] = GetMessage($code.'_ELEMENT_NAME');

			if ($arType['SECTIONS'] == 'Y')
				$arType['LANG'][$languageID]['SECTION_NAME'] = GetMessage($code.'_SECTION_NAME');
		}
		$iblockType = new \CIBlockType;
		global $DB;
		$DB->StartTransaction();
		$res = $iblockType->Add($arType);
		if(!$res)
		{
			$DB->Rollback();
			echo 'Error creating iblock type: '.$iblockType->LAST_ERROR.'<br>';
			die();
		}
		else {
			self::$_arConfig['IBLOCK_TYPE'][$typeID]['IS_EXISTS'] = true;
			$DB->Commit();
		}
		return true;
	}

	static public function createIBlockTypes() {
		if(
			\COption::GetOptionString('store', 'wizard_installed', 'N', WIZARD_SITE_ID) == 'Y'
			&& !WIZARD_INSTALL_DEMO_DATA
		) return true;

		foreach(self::$_arConfig['IBLOCK_TYPE'] as $typeID => &$arTypeConfig) {
			self::__createIBlockType($typeID);
		}
		self::setIBCombinedList();
	}

	public function createIBlockType() {
		if(!$this->_constructOK) return false;
		if( ! self::__createIBlockType($this->_iblockType) )  return false;
		return true;
	}

	protected function deleteOldIBlockData() {
		global $DB;
		if(!$this->_constructOK) return false;
		if ($this->_iblockID > 0) {
			if (WIZARD_INSTALL_DEMO_DATA) {
				$DB->StartTransaction();
				$bDeleteSuccess = \CIBlock::Delete($this->_iblockID);
				if($bDeleteSuccess) {
					$this->_iblockID = 0;
					$DB->Commit();
				}
				else {
					$DB->Rollback();
					die('Error deleting iblock');
				}
			}
		}
		return true;
	}

	public function importXMLData() {
		if(!$this->_constructOK) return false;
		$bTypeSuccess = $this->createIBlockType();
		if(!$bTypeSuccess) return false;
		if( ! $this->deleteOldIBlockData() ) return false;
		// Это если мы реинсталлируем данные инфоблоков
		if( $this->_iblockID == 0 ) {
			$arFields = Tools::arrayMergeRecursiveDistinct(self::$_arDefaultIBlockFields, self::$_arConfig['IBLOCK'][$this->_iblockCode]);
			$arPermissions = $arFields['PERMISSIONS'];
			$dbGroup = \CGroup::GetList($by = "", $order = "", Array("STRING_ID" => "content_editor"));
			if($arGroup = $dbGroup -> Fetch())
			{
				$arPermissions[$arGroup["ID"]] = 'W';
			};
			unset($arFields['IBLOCK_TYPE_ID']);
			unset($arFields['XML_FILE']);
			unset($arFields['FORM_SETTINGS']);
			unset($arFields['PERMISSIONS']);

			$this->_iblockID = \WizardServices::ImportIBlockFromXML(
				$this->_iblockXMLFile,
				$this->_iblockCode,
				$this->_iblockType,
				WIZARD_SITE_ID,
				$arPermissions
			);

			if ($this->_iblockID < 1) {
				die('Error importing xml-data');
			}
			$iblock = new \CIBlock;
			$iblock->Update($this->_iblockID, $arFields);
		}
		// это если данные уже становлены просто добавим недостающие привязки к сайтам
		else {
			$arSites = self::getIBlockSites($this->_iblockID);
			if (!in_array(WIZARD_SITE_ID, $arSites))
			{
				$arSites[] = WIZARD_SITE_ID;
				$iblock = new \CIBlock;
				$iblock->Update($this->_iblockID, array("LID" => $arSites));
			}
		}
	}
}

///////////// ПРИМЕР КОНФИГА /////////////
//<?php
//$arIBlockInstallerConfig = array(
//	'IBLOCK_TYPE' => array(
//		'dvt_smoke_catalog' => array(
//			'SECTIONS' => 'Y',
//			'IN_RSS' => 'Y',
//			'SORT' => 200,
//		),
//		'dvt_articles' => array(
//			'SECTIONS' => 'Y',
//			'IN_RSS' => 'N',
//			'SORT' => 300,
//		)
//	),
//	'IBLOCK' => array(
//		'cig' => array(
//			'IBLOCK_TYPE_ID' => 'dvt_smoke_catalog',
//			'XML_FILE' => 'cig.xml',
//			'FORM_SETTINGS' => 'cig.form_settings',
//			'PERMISSIONS' => array(
//				'1' => 'X',
//				'2' => 'R'
//			),
//			'FIELDS' => array(
//				'PREVIEW_PICTURE' => array(
//					'FROM_DETAIL' => 'Y'
//				)
//			)
//		),
//		'fluid' => array(
//			'IBLOCK_TYPE_ID' => 'dvt_smoke_catalog',
//			'XML_FILE' => 'liq.xml',
//			'FORM_SETTINGS' => 'liq.form_settings',
//			'PERMISSIONS' => array(
//				'1' => 'X',
//				'2' => 'R'
//			),
//			'FIELDS' => array(
//				'PREVIEW_PICTURE' => array(
//					'FROM_DETAIL' => 'Y'
//				)
//			)
//		),
//		'kit' => array(
//			'IBLOCK_TYPE_ID' => 'dvt_smoke_catalog',
//			'XML_FILE' => 'kit.xml',
//			'FORM_SETTINGS' => 'kit.form_settings',
//			'PERMISSIONS' => array(
//				'1' => 'X',
//				'2' => 'R'
//			),
//			'FIELDS' => array(
//				'PREVIEW_PICTURE' => array(
//					'FROM_DETAIL' => 'Y'
//				)
//			)
//		),
//		'accessories' => array(
//			'IBLOCK_TYPE_ID' => 'dvt_smoke_catalog',
//			'XML_FILE' => 'acc.xml',
//			'FORM_SETTINGS' => 'acc.form_settings',
//			'PERMISSIONS' => array(
//				'1' => 'X',
//				'2' => 'R'
//			),
//			'FIELDS' => array(
//				'PREVIEW_PICTURE' => array(
//					'FROM_DETAIL' => 'Y'
//				)
//			)
//		)
//	)
//);
//return $arIBlockInstallerConfig;
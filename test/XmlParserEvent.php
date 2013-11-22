<?php
/***********************************************
 ** @product OBX:Core Bitrix Module           **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Core\Test;
use OBX\Core\Test\TestCase;
use OBX\Core\Xml\Parser as XmlParser;
use OBX\Core\Xml\Exceptions\ParserError as XmlParserError;

class XmlParserEvent extends TestCase {
	public function getCurDir() {
		return dirname(__FILE__);
	}

	public function getXmlFilePath() {
		return array(
			array(
				$this->getCurDir().'/data/yml_catalog_example.xml'
			)
		);
	}

	/**
	 * @param string $filePath
	 * @dataProvider getXmlFilePath
	 */
	public function testWrongFile($filePath) {
		$ITERATION = array();
		$Parser = new XmlParser($filePath);
		$test = $this;
		$Parser->onBeforeAdd(function($Parser, &$arFields) use ($test) {
			/** @var \OBX\Core\Xml\Parser $Parser */
			if($arFields['NAME'] == 'offers') {
				$Parser->breakReading();
				$Parser->endElement(); // shop
				$Parser->endElement(); // yml_catalog
			}
		});
		$Parser->dropTempTables();
		$Parser->createTempTables();
		$prevFilePosition = 0;
		while( !$Parser->readXML($ITERATION) ) {
			$this->assertGreaterThanOrEqual($prevFilePosition, $ITERATION['file_position']);
			$prevFilePosition = $ITERATION['file_position'];
		}
	}
}
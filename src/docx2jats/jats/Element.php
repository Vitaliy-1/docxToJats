<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Element.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief abstraction for JATS XML elements
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Par;

abstract class Element extends \DOMElement {

	private $dataObject;

	public function __construct(DataObject $dataObject) {

		$this->dataObject = $dataObject;

		// Determing element name
		$name = '';
		switch (get_class($dataObject)) {
			case "docx2jats\objectModel\body\Par":
				/* @var $dataObject \docx2jats\objectModel\body\Par */
				foreach ($dataObject->getType() as $par) {
					if ($par === Par::DOCX_PAR_LIST) {
						$name = "p";
					} elseif ($par === Par::DOCX_PAR_HEADING) {
						$name = "title";
					} elseif ($par === Par::DOCX_PAR_REGULAR) {
						$name = "p";
					}
				}
				break;
			case "docx2jats\objectModel\body\Table":
				$name = 'table-wrap';
				break;
			case "docx2jats\objectModel\body\Row":
				$name = 'tr';
				break;
			case "docx2jats\objectModel\body\Cell":
				$name = 'td';
				break;
		}

		/*
		$textString = '';
		foreach ($dataObject->getContent() as $text) {
			$textString .= $text->getContent();
		}
		*/

		if (!empty($name)) parent::__construct($name);
	}

	protected function getDataObject() {
		return $this->dataObject;
	}
}

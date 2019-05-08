<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Cell.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represents JATS XML table's cell
 */

use docx2jats\objectModel\DataObject;
use docx2jats\jats\Par as JatsPar;

class Cell extends Element {
	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);
	}

	public function setContent() {
		$dataObject = $this->getDataObject();

		$colspan = $dataObject->getColspan();
		$rowspan = $dataObject->getRowspan();
		if ($colspan > 1) {
			$this->setAttribute('colspan', $colspan);
		}

		if ($rowspan > 1) {
			$this->setAttribute('rowspan', $rowspan);
		}

		foreach ($dataObject->getContent() as $content) {
			$par = new Par($content);
			$this->appendChild($par);
			$par->setContent();
		}
	}
}

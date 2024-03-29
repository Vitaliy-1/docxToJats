<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Table.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief contains table data
 */

use docx2jats\objectModel\Document;

// TODO create a common parent class for Image and Table
class Table extends InfoBlock {

	protected $properties = array();
	protected $rows = array();
	public static $caption = array("caption");
	protected $tableId = 0;

	public function __construct(\DOMElement $domElement, Document $ownerDocument) {
		parent::__construct($domElement, $ownerDocument);
		$this->properties = $this->setProperties('w:tblPr/child::node()');
		$this->rows = $this->setContent('w:tr');
	}

	private function setContent(string $xpathExpression) {
		$content = array();

		$contentNodes = $this->getXpath()->query($xpathExpression, $this->getDomElement());
		if ($contentNodes->count() > 0) {
			foreach ($contentNodes as $contentNode) {
				$row = new Row($contentNode, $this->getOwnerDocument(), $this);
				$content[] = $row;
			}
		}

		return $content;
	}

	public function getContent() {
		return $this->rows;
	}

	/**
	 * @param int $currentTableId
	 */
	public function setTableId(int $currentTableId): void {
		$this->tableId = $currentTableId;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->tableId;
	}


}

<?php namespace docx2jats\jats;

/**
* @file src/docx2jats/jats/Table.php
*
* Copyright (c) 2018-2019 Vitalii Bezsheiko
* Distributed under the GNU GPL v3.
*
* @brief represent JATS XML image
*/

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Figure as FigureObject;

class Figure extends Element {
	/* @var $dataObject FigureObject */
	var $figureObject;

	public function __construct(DataObject $dataObject) {
		parent::__construct($dataObject);

		$this->figureObject = $dataObject;
	}

	function setContent() {

		$figureNode = $this->ownerDocument->createElement('graphic');
		$this->appendChild($figureNode);

		$pathInfo = pathinfo($this->figureObject->getLink());

		if ($this->figureObject->getType() == FigureObject::DOCX_DRAWING_PICTURE) {
			$figureNode->setAttribute("mimetype", "image");

			switch ($pathInfo['extension']) {
				case "jpg":
				case "jpeg":
					$figureNode->setAttribute("mime-subtype", "jpeg");
					break;
				case "png":
					$figureNode->setAttribute("mime-subtype", "png");
					break;
			}

		} // TODO convert charts

		$figureNode->setAttribute("xlink:href", $pathInfo['basename']);
	}
}

<?php namespace docx2jats\jats;

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\body\Par;

abstract class Element extends \DOMElement {
	
	public function __construct(DataObject $dataObject)
	{
		// Determing element name
		$name = '';
		switch (get_class($dataObject)) {
			case "docx2jats\objectModel\body\Par":
				/* @var $dataObject \docx2jats\objectModel\body\Par */
				foreach ($dataObject->getType() as $par) {
					if ($par === Par::DOCX_PAR_LIST) {
						$name = "list-item";
					} elseif ($par === Par::DOCX_PAR_HEADING) {
						$name = "title";
					} elseif ($par === Par::DOCX_PAR_REGULAR) {
						$name = "p";
					}
				}
		}
		
		if (!empty($name)) parent::__construct($name);
	}
}

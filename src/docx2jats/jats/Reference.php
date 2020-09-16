<?php namespace docx2jats\jats;

/**
 * @file src/docx2jats/jats/Row.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represent JATS XML reference
 */

class Reference extends \DOMElement {
	public function __construct() {
		parent::__construct('ref');
	}

	public function setContent(\docx2jats\objectModel\body\Reference $reference) {
		$this->setAttribute('id', $reference->getId());
		if (!$reference->hasStructure()) {
			$mixedCitationEl = $this->ownerDocument->createElement('mixed-citation');
			$textContent = $this->ownerDocument->createTextNode($reference->getRawReference());
			$mixedCitationEl->appendChild($textContent);
			$this->appendChild($mixedCitationEl);
		}
	}
}

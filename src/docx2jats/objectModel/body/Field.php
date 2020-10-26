<?php namespace docx2jats\objectModel\body;

/**
 * @file src/docx2jats/objectModel/body/Field.php
 *
 * Copyright (c) 2018-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represents special fields inside paragraphs that span across runs
 */

use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document;

class Field extends DataObject {

	const DOCX_FIELD_CSL = 1;
	/** @var $type int DOCX_FIELD... const */
	private $type = 0;
	private $isZoteroCSL = false;
	private $refIds = array();
	private $plainCit;

	private $content = array();
	private $rawRuns = array();
	/** @var array contains instructions to be processed as strings, e.g., CSL citations as a JSON string */
	private $instructions = array();

	public function __construct(\DOMElement $domElement, Document $ownerDocument, Par $parent) {
		parent::__construct($domElement, $ownerDocument, $parent);
		$this->rawRuns[] = $domElement;
	}

	/**
	 * @return mixed
	 */
	public function getContent() {
		return $this->content;
	}

	static function complexFieldStarts(\DOMElement $domElement) {
		$fieldNode = Document::$xpath->query('w:fldChar', $domElement)[0];
		if ($fieldNode && $fieldNode->hasAttribute('w:fldCharType') && $fieldNode->getAttribute('w:fldCharType') === 'begin') {
			return true;
		}
		return false;
	}

	static function complexFieldLast(\DOMElement $domElement) {
		$fieldNode = Document::$xpath->query('w:fldChar', $domElement)[0];
		if ($fieldNode && $fieldNode->hasAttribute('w:fldCharType') && $fieldNode->getAttribute('w:fldCharType') === 'end') {
			return true;
		}
		return false;
	}

	public function addContent(\DOMElement $domElement) {
		$this->rawRuns[] = $domElement;
		if (self::complexFieldLast($domElement)) {
			$this->processRuns();
		}
	}

	/**
	 * @brief retrieve instructions and text for the field
	 */
	private function processRuns() {
		$resultText = false;
		foreach ($this->rawRuns as $run) {
			$fieldNode = Document::$xpath->query('w:fldChar', $run)[0];
			if ($fieldNode && $fieldNode->hasAttribute('w:fldCharType') && $fieldNode->getAttribute('w:fldCharType') === 'separate') {
				$resultText = true;
			}

			if (!$resultText) {
				$instructionNode = Document::$xpath->query('w:instrText', $run)[0];
				if ($instructionNode) {
					$instructionString = $instructionNode->nodeValue;
					$this->instructions[] = $instructionString;
					if (strpos($instructionString, 'CSL_CITATION') !== false) {
						$this->type = self::DOCX_FIELD_CSL;
						$rawCSL = $this->extractRawCSL($instructionString);
						$references = Reference::findRefsCSL($rawCSL);
						$this->plainCit = Reference::findPlainCit($rawCSL);
						foreach ($references as $reference) {
							$reference->isZoteroCSL = $this->isZoteroCSL();
							if (!$ref = Reference::cslExists($reference, $this->getOwnerDocument())) {
								$this->getOwnerDocument()->addReference($reference);
								$this->refIds[] = $reference->getId();
							} else {
								$this->refIds[] = $ref->getId();
							}
						}
					}
				}
			} else {
				$this->content[] = new Text($run);
			}
		}
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	private function extractRawCSL(string $instruction) {
		$instruction = trim($instruction);
		$pos = strpos($instruction, '{');
		$instructionsRawPart = substr($instruction, 0, $pos);
		if (strpos($instructionsRawPart, 'ZOTERO_ITEM') !== false) {
			$this->isZoteroCSL = true;
		}

		$rawCSL = substr($instruction, $pos);
		return $rawCSL;
	}

	public function getPlainCit() {
		return $this->plainCit;
	}

	public function getRefIds() {
		return $this->refIds;
	}

	/**
	 * @return bool
	 */
	public function isZoteroCSL(): bool {
		return $this->isZoteroCSL;
	}
}

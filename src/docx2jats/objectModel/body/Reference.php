<?php namespace docx2jats\objectModel\body;

use docx2jats\objectModel\Document;/**
 * @file src/docx2jats/objectModel/body/Field.php
 * Copyright (c) 2018-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 * @brief represents special fields inside paragraphs that span across runs
 */

class Reference {

	private $rawReference;
	private $id;
	private $cslId;
	private $csl;
	public $isZoteroCSL = false;
	private $hasStructure = false;

	public function __construct(string $rawReference) {
		$this->rawReference = $rawReference;

		if ($csl = json_decode($rawReference)) {
			$this->csl = $csl;
			$this->hasStructure = true;
		}
	}

	/**
	 * @return int
	 */
	public function getCslId(): string
	{
		return $this->cslId;
	}

	/**
	 * @param string $instruction
	 * @return array [instructions command, CSL as a string]
	 */
	public static function extractRawCSL(string $instruction): array {
		$instruction = trim($instruction);
		$pos = strpos($instruction, '{');
		$instructionsRawPart = substr($instruction, 0, $pos);
		$rawCSL = substr($instruction, $pos);
		return array($instructionsRawPart, $rawCSL);
	}

	public static function findRefsCSL(string $rawCSL) : array {
		$citations = [];
		$json = json_decode($rawCSL);
		if (is_null($json)) return $citations;
		$items = $json->{'citationItems'};
		if (!$items) return $citations;

		foreach ($items as $item) {
			$reference = new Reference(json_encode($item, JSON_UNESCAPED_UNICODE));
			$reference->cslId = $item->{'id'};
			$citations[] = $reference;
		}

		return $citations;
	}

	public static function findPlainCit(string $rawCSL): ?string {
		$plainCit = null;
		$json = json_decode($rawCSL);
		if (is_null($json) || !$json->{'properties'}) return $plainCit;

		$props = null;
		if (property_exists($json, 'properties')) {
			$props = $json->{'properties'};
		}

		// Zotero
		if ($props && property_exists($props, 'plainCitation')) {
			return $props->{'plainCitation'};
		}

		if ($props && property_exists($props, 'formattedCitation')) {
			return $props->{'formattedCitation'};
		}

		// Mendeley
		if (property_exists($json, 'mendeley')) {
			$mendeley = $json->{'mendeley'};
			if ($props && property_exists($mendeley, 'formattedCitation')) {
				return $mendeley->{'formattedCitation'};
			}

			if (property_exists($mendeley, 'plainTextFormattedCitation')) {
				return $mendeley->{'plainTextFormattedCitation'};
			}

			if (property_exists($mendeley, 'previouslyFormattedCitation')) {
				return $mendeley->{'previouslyFormattedCitation'};
			}
		}

		return $plainCit;
	}

	/**
	 * @param int $id csl ID
	 * @param Document $document
	 * @return Reference|null returns reference if csl id exists or null if doesn't
	 */
	public static function cslExists(Reference $refToCompare, Document $document) : ?Reference {

		$refToCompareId = $refToCompare->getCslId();
		foreach ($document->getReferences() as $reference) {
			// Just compare by ID, it's enough for Zotero
			if ($reference->getCslId() == $refToCompareId && $refToCompare->isZoteroCSL) {
				return $reference;
				// Compare raw references for Mendeley
			} elseif (Reference::isCslMendeleySimilar($refToCompare, $reference)) {
				return $reference;
			}
		}

		return null;
	}

	public function setId(int $id) {
		$this->id = $id;
	}

	public function getId() : ?int {
		return $this->id;
	}

	public function getCSL() : ?\stdClass {
		return $this->csl;
	}

	public function hasStructure() : bool {
		return $this->hasStructure;
	}

	public function getRawReference() : string {
		return $this->rawReference;
	}

	static function isCslMendeleySimilar(Reference $referenceToCompare, Reference $reference) : bool {
		$ref = $reference->getCSL()->{'itemData'};
		$refToCompare = $referenceToCompare->getCSL()->{'itemData'};

		if (property_exists($refToCompare, 'id')) {
			unset($refToCompare->{'id'});
		}

		if (property_exists($ref , 'id')) {
			unset($ref->{'id'});
		}

		if (strcmp(json_encode($refToCompare, JSON_UNESCAPED_UNICODE), json_encode($ref, JSON_UNESCAPED_UNICODE)) === 0) {
			return true;
		}

		return false;
	}
}

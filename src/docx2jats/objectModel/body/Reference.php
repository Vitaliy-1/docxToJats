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
	private $cslId = 0;

	public function __construct(string $rawReference) {
		$this->rawReference = $rawReference;
	}

	/**
	 * @return int
	 */
	public function getCslId(): int
	{
		return $this->cslId;
	}

	public static function findRefsCSL(string $rawCSL) : array {
		$citations = [];
		$json = json_decode($rawCSL);
		if (is_null($json)) return $citations;
		$items = $json->{'citationItems'};
		if (!$items) return $citations;

		foreach ($items as $item) {
			$reference = new Reference(json_encode($item));
			$reference->cslId = $item->{'id'};
			$citations[] = $reference;
		}

		return $citations;
	}

	public static function findPlainCit(string $rawCSL): ?string {
		$plainCit = null;
		$json = json_decode($rawCSL);
		if (is_null($json) || !$json->{'properties'}) return $plainCit;
		$props = $json->{'properties'};

		if ($plainCit = $props->{'plainCitation'}) {
			return $plainCit;
		}

		if ($plainCit = $props->{'formattedCitation'}) {
			return $plainCit;
		}

		return $plainCit;
	}

	/**
	 * @param int $id csl ID
	 * @param Document $document
	 * @return Reference|null returns reference if csl id exists or null if doesn't
	 */
	public static function cslIdExists(int $id, Document $document) : ?Reference {
		foreach ($document->getReferences() as $reference) {
			if ($reference->getCslId() == $id) {
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
}

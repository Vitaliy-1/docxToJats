<?php namespace docx2jats\objectModel;

/**
 * @file src/docx2jats/objectModel/DataObject.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief abstraction for Document element
 */

use docx2jats\objectModel\Document;
use docx2jats\objectModel\body\Text;
use docx2jats\objectModel\body\Par;

abstract class DataObject {

	private $domElement;
	private $xpath;

	/* @var $flatSectionId int */
	private $flatSectionId;

	/* @var $dimensionalSectionId array */
	private $dimensionalSectionId = array();

	public function __construct(\DOMElement $domElement)   {
		$this->domElement = $domElement;
		$this->xpath = Document::$xpath;
	}

	protected function getXpath(): \DOMXPath {
		return $this->xpath;
	}

	protected function setProperties(string $xpathExpression): array {
		$styleNodes = $this->getXpath()->evaluate($xpathExpression, $this->domElement);
		$properties = $this->extractPropertyRecursion($styleNodes);

		return $properties;
	}

	/**
	 * @return \DOMElement
	 */
	protected function getDomElement(): \DOMElement {
		return $this->domElement;
	}

	/**
	 * @param \DOMNodeList $domNodeList
	 * @return bool
	 */
	protected function isOnlyChildNode(\DOMNodeList $domNodeList): bool {
		if ($domNodeList->count() === 1) {
			return true;
		}
		return false;
	}

	/**
	 * @param $styleNodes
	 * @return array
	 */
	private function extractPropertyRecursion($styleNodes): array
	{
		$properties = array();
		foreach ($styleNodes as $styleNode) {
			if ($styleNode->hasAttributes()) {
				foreach ($styleNode->attributes as $attr) {
					$properties[$styleNode->nodeName][$attr->nodeName] = $attr->nodeValue;
				}
			} elseif  ($styleNode->hasChildNodes()) {
				$children = $this->getXpath()->query('child::node()', $styleNode);
				$rPr = $this->extractPropertyRecursion($children);
				$properties[$styleNode->nodeName] = $rPr;
			}
		}
		return $properties;
	}

	/**
	 * @return array
	 */
	protected function setParagraphs(): array {
		$content = array();

		$parNodes = $this->getXpath()->query('w:p', $this->getDomElement());
		foreach ($parNodes as $parNode) {
			$par = new Par($parNode);
			$content[] = $par;
		}

		return $content;
	}

	/**
	 * @param $flatSectionId
	 */
	public function setFlatSectionId($flatSectionId): void {
		$this->flatSectionId = intval($flatSectionId);
	}

	/**
	 * @return int
	 */
	public function getFlatSectionId(): int {
		return $this->flatSectionId;
	}

	/**
	 * @param array $dimensionalSectionId
	 */
	public function setDimensionalSectionId(array $dimensionalSectionId): void {
		$this->dimensionalSectionId = array_filter($dimensionalSectionId);
	}

	/**
	 * @return array
	 */
	public function getDimensionalSectionId(): array {
		return $this->dimensionalSectionId;
	}
}

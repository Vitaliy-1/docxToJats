<?php namespace docx2jats;

/**
 * @file src/docx2jats/DOCXArchive.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief represents DOCX archive; provides unpacking
 */

use docx2jats\objectModel\Document;

class DOCXArchive extends \ZipArchive {

	/* @var $ooxmlDocument \DOMDocument contains the main content of the document */
	private $ooxmlDocument;

	/* @var $document Document the document object model */
	private $document;

	public function __construct(string $filepath) {
		if ($this->open($filepath)) {
			$this->ooxmlDocument = $this->transformToXml("word/document.xml");
			$relationships = $this->transformToXml("word/_rels/document.xml.rels");
			$styles = $this->transformToXml("word/styles.xml");
			$this->close();

			// construct as an array
			$params = array();

			$params["ooxmlDocument"] = $this->ooxmlDocument;

			if ($relationships) {
				$params["relationships"] = $relationships;
			}

			if ($styles) {
				$params["styles"] = $styles;
			}

			$document = new Document($params);

			$this->document = $document;
		}
	}

	public function getDocumentOoxml(): \DOMDocument {
		return $this->ooxmlDocument;
	}

	public function getDocument(): Document {
		return $this->document;
	}

	private function transformToXml(string $path): ?\DOMDocument {
		$document = $this->locateName($path);
		if (!$document) return null;
		$data = $this->getFromIndex($document);
		$xml = new \DOMDocument();
		$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
		return $xml;
	}

}

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

	/* @var $filePath string */
	private $filePath;

	/* @var $ooxmlDocument \DOMDocument contains the main content of the document */
	private $ooxmlDocument;

	/* @var $document Document the document object model */
	private $document;

	/* @var $mediaFiles array of strings, paths to media files inside the archive */
	private $mediaFiles = array();

	public function __construct(string $filepath) {
		$this->filePath = $filepath;

		if ($this->open($filepath)) {
			$this->ooxmlDocument = $this->transformToXml("word/document.xml");
			$relationships = $this->transformToXml("word/_rels/document.xml.rels");
			$styles = $this->transformToXml("word/styles.xml");
			$this->mediaFiles = $this->extractMediaFiles();
			$numbering = $this->transformToXml("word/numbering.xml");
			$this->close();

			// construct as an array
			$params = array();

			$params["ooxmlDocument"] = $this->ooxmlDocument;

			if ($relationships) $params["relationships"] = $relationships;

			if ($styles) $params["styles"] = $styles;

			if ($numbering) $params["numbering"] = $numbering;

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
		$index = $this->locateName($path);
		if (!$index) return null;
		$data = $this->getFromIndex($index);
		$xml = new \DOMDocument();
		$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
		return $xml;
	}

	private function extractMediaFiles() {
		$paths = array();
		for ($i = 0; $i < $this->numFiles; $i++) {
			$filePath = $this->getNameIndex($i);

			if (!strpos($filePath, "media/")) continue;

			$paths[] = $filePath;
		}

		return $paths;
	}

	/**
	 * @param string $path
	 * @return string|null file extracted from DOCX archive
	 */

	public function getFile(string $path): ?string {
		if ($this->open($this->filePath)) {
			$index = $this->locateName("word/" . $path);
			if (!$index) return null;
			$data = $this->getFromIndex($index);
			$this->close();
			return $data;
		}

		return null;
	}

	/**
	 * @param $outputDir string, should include trailing slash
	 * @brief writes media files to the specified dir; preserves original filename and extension
	 */
	public function getMediaFiles(string $outputDir): void {

		if (empty($this->mediaFiles)) return;

		if ($this->open($this->filePath)) {
			foreach ($this->mediaFiles as $mediaFile) {
				$index = $this->locateName($mediaFile);
				$data = $this->getFromIndex($index);
				file_put_contents($outputDir . pathinfo($mediaFile)['basename'], $data);
			}
			$this->close();
		}
	}

	/**
	 * @return array
	 */
	public function getMediaFilesContent(): array {
		$filesContent = array();

		if (empty($this->mediaFiles)) return $filesContent;

		if ($this->open($this->filePath)) {
			foreach ($this->mediaFiles as $mediaFile) {
				$index = $this->locateName($mediaFile);
				$data = $this->getFromIndex($index);
				$filesContent[$mediaFile] = $data;
			}
			$this->close();
		}

		return $filesContent;
	}
}

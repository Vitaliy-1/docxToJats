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

	public const CONTENT_TYPES_PATH = '[Content_Types].xml';
	public const CONTENT_TYPE_DOCUMENT_MAIN = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml';
	public const CONTENT_TYPE_STYLES = 'application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml';
	public const CONTENT_TYPE_SETTINGS = 'application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml';
	public const CONTENT_TYPE_NUMBERING = 'application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml';
	public const CONTENT_TYPE_CUSTOM_PROP = 'application/vnd.openxmlformats-officedocument.custom-properties+xml';
	public const CONTENT_TYPE_RELATIONSHIPS = 'application/vnd.openxmlformats-package.relationships+xml';

	/* @var $contentType \DOMDocument */
	private $contentType;

	static $contentTypeXpath;

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
			$this->contentType = $this->transformToXml(self::CONTENT_TYPES_PATH);
			self::$contentTypeXpath = new \DOMXPath($this->contentType);

			// Set the Main Document Part
			$ooxmlDocumentPath = $this->getRealFileDocumentPath('word/document.xml', self::CONTENT_TYPE_DOCUMENT_MAIN);
			$this->ooxmlDocument = $this->transformToXml($ooxmlDocumentPath);

			// Relationships of the Main Document Part
			$partRelationshipsPath = $this->getRealFileDocumentPath('word/_rels/document.xml.rels', self::CONTENT_TYPE_RELATIONSHIPS, $ooxmlDocumentPath);
			$partRelationships = $this->transformToXml($partRelationshipsPath);

			// Style names used in the document, styles should be checked recursively, see docx2jats\objectModel\Document::getBuiltinStyle
			$stylePath = $this->getRealFileDocumentPath('word/styles.xml', self::CONTENT_TYPE_STYLES);
			$styles = $this->transformToXml($stylePath);

			// Media files, e.g., images
			$this->mediaFiles = $this->extractMediaFiles();

			// Description of all numbered content, e.g., lists
			$numberingPath = $this->getRealFileDocumentPath('word/numbering.xml', self::CONTENT_TYPE_NUMBERING);
			$numbering = $this->transformToXml($numberingPath);

			// Custom Document properties, this is used by Mendeley plugin export from LibreOffice Writer
			$docPropsCustom = $this->getRealFileDocumentPath('docProps/custom.xml', self::CONTENT_TYPE_CUSTOM_PROP);
			$docPropsCustom = $this->transformToXml($docPropsCustom);
			$this->close();

			// construct as an array
			$params = array();

			$params["ooxmlDocument"] = $this->ooxmlDocument;

			if ($partRelationships) $params["partRelationships"] = $partRelationships;
			if ($styles) $params["styles"] = $styles;
			if ($numbering) $params["numbering"] = $numbering;
			if ($docPropsCustom) $params["docPropsCustom"] = $docPropsCustom;

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
		if ($index === false) return null;
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

	private function getRealFileDocumentPath(string $defaultPath, string $contentType = null, string $parentPath = null): string {
		$path = null;
		if (!is_null($contentType)) {
			foreach ($this->contentType->getElementsByTagName('Override') as $override) {
				if ($override->hasAttribute('PartName') &&
					$override->hasAttribute('ContentType') &&
					$override->getAttribute('ContentType') == $contentType) {
					if ($contentType !== self::CONTENT_TYPE_RELATIONSHIPS) {
						$path = $override->getAttribute('PartName');
						break;
					} else {
						// Find the file associated with relationships, compare by filename
						$partName = $override->getAttribute('PartName');
						if (strpos(pathinfo($partName)['basename'], pathinfo($parentPath)['basename']) !== false) {
							$path = $partName;
							break;
						}
					}
				}
			}

			// MS Word may not specify the path to the relationships files trying to guess based on the parent path
			if ($contentType === self::CONTENT_TYPE_RELATIONSHIPS && is_null($path)) {
				$path = 'word/_rels/' . pathinfo($parentPath)['basename'] . '.rels';
			}
		}

		if (is_null($path)) {
			$path = $defaultPath;
		}

		$path = ltrim($path, '/');

		try {
			$this->findDocumentByPath($path);
		} catch (\Exception $e) {
			if ($defaultPath === self::CONTENT_TYPE_DOCUMENT_MAIN) {
				trigger_error($e->getMessage(), E_USER_ERROR);
			} else {
				trigger_error($e->getMessage(), E_USER_NOTICE);
			}
		}

		return $path;
	}

	/**
	 * @param string $path
	 * @return \DOMDocument
	 * @throws \Exception if the document inside the archive isn't found
	 */
	private function findDocumentByPath(string $path): void {
		$domDocument = $this->transformToXml($path);
		if (!$domDocument) {
			throw new \Exception('Cannot find document inside the archive by the path ' . $path);
		}
	}
}

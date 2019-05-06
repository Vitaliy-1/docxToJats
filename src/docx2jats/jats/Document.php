<?php namespace docx2jats\jats;

use docx2jats\DOCXArchive;
use docx2jats\jats\Par as JatsPar;
use docx2jats\objectModel\body\Par;
use docx2jats\jats\Table as JatsTable;
use docx2jats\objectModel\body\Table;
use docx2jats\objectModel\DataObject;
use docx2jats\objectModel\Document as DOCXDocument;

class Document extends \DOMDocument {
	/* @var $docxArchive \docx2jats\DOCXArchive */
	private $docxArchive;

	/* @var $article \DOMElement */
	var $article;

	/* @var $front \DOMElement */
	var $front;

	/* @var $body \DOMElement */
	var $body;

	/* @var $back \DOMElement */
	var $back;

	/* @var $sections array of DOMElements */
	var $sections = array();

	/* @var $lists array of DOMElements; contains all article's lists, key -> unique list ID, corresponds to ID in numbering.xml */
	var $lists = array();

	public function __construct(DOCXArchive $docxArchive) {
		parent::__construct('1.0', 'utf-8');
		$this->docxArchive = $docxArchive;
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;

		// Doctype
		$impl = new \DOMImplementation();
		$this->appendChild($impl->createDocumentType("article", "-//NLM//DTD JATS (Z39.96) Journal Archiving DTD v1.0 20120330//EN", "https://jats.nlm.nih.gov/archiving/1.1/JATS-archivearticle1.dtd"));

		$this->setBasicStructure();
		$this->extractContent();
	}

	public function getJatsFile() {
		$this->save("test_jats.xml");
	}

	private function setBasicStructure() {
		$this->article = $this->createElement('article');
		$this->article->setAttributeNS(
			"http://www.w3.org/2000/xmlns/",
			"xmlns:xlink",
			"http://www.w3.org/1999/xlink"
		);
		$this->article->setAttributeNS(
			"http://www.w3.org/2000/xmlns/",
			"xmlns:ali",
			"http://www.niso.org/schemas/ali/1.0/ali.xsd"
		);

		$this->appendChild($this->article);

		$this->front = $this->createElement('front');
		$this->article->appendChild($this->front);

		$this->body = $this->createElement('body');
		$this->article->appendChild($this->body);

		$this->back = $this->createElement('back');
		$this->article->appendChild($this->back);
	}

	private function extractContent() {
		$document = $this->docxArchive->getDocument();
		if (!empty($document->getContent())) {

			$latestSectionId = array();
			$latestSections = array();

			$subList = array(); // temporary container for sublists
			$listItem = null; // temporary container for previous list item
			$listCounter = 0; // temporary container for current list ID
			foreach ($document->getContent() as $key => $content) {
				$contentId = 'sec-' . implode('_', $content->getDimensionalSectionId());

				// Appending section, must correspond section nested level; TODO optimize with recursion
				if ($content->getDimensionalSectionId() !== $latestSectionId) {
					$sectionNode = $this->createElement("sec");
					$sectionNode->setAttribute('id', $contentId);
					$this->sections[$contentId] = $sectionNode;
					if (count($content->getDimensionalSectionId()) === 1) {
						$this->body->appendChild($sectionNode);
						$latestSections[0] = $sectionNode;
					} elseif (count($content->getDimensionalSectionId()) === 2) {
						$latestSections[0]->appendChild($sectionNode);
						$latestSections[1] = $sectionNode;
					} elseif (count($content->getDimensionalSectionId()) === 3) {
						$latestSections[1]->appendChild($sectionNode);
						$latestSections[2] = $sectionNode;
					} elseif (count($content->getDimensionalSectionId()) === 4) {
						$latestSections[2]->appendChild($sectionNode);
						$latestSections[3] = $sectionNode;
					} elseif (count($content->getDimensionalSectionId()) === 5) {
						$latestSections[3]->appendChild($sectionNode);
					}

					$latestSectionId = $content->getDimensionalSectionId();
				}

				// If there aren't any sections, append content to the body
				if (empty($this->sections)) {
					$sectionsOrBody = array($this->body);
				} else {
					$sectionsOrBody = $this->sections;
				}

				switch (get_class($content)) {

					case "docx2jats\objectModel\body\Par":
						/* @var $content Par */
						$jatsPar = new JatsPar($content);

						foreach ($sectionsOrBody as $section) {
							if ($contentId === $section->getAttribute('id') || $section->nodeName === "body") {
								if (!in_array(Par::DOCX_PAR_LIST, $content->getType())) {
									$section->appendChild($jatsPar);
									$jatsPar->setContent();
								} elseif (!in_array(Par::DOCX_PAR_HEADING, $content->getType())) {
									$itemId = $content->getNumberingItemProp()[Par::DOCX_LIST_ITEM_ID];
									$hasSublist = $content->getNumberingItemProp()[Par::DOCX_LIST_HAS_SUBLIST];

									// Creating and appending new list
									if ($listCounter !== $content->getNumberingId()) {
										$newList = $this->createElement('list');
										$this->lists[$content->getNumberingId()] = $newList;
									} else {
										$section->appendChild($this->lists[$listCounter]);
									}

									// appends nested lists and list items based on their level
									if (count($itemId) === $content->getNumberingLevel()+1) {
										$listItem = $this->createElement('list-item');
										$listItem->appendChild($jatsPar);
										$jatsPar->setContent();

										if ($content->getNumberingLevel() === 0) {

											$this->lists[$content->getNumberingId()]->appendChild($listItem);
										} else {
											$subList[$content->getNumberingLevel()-1]->appendChild($listItem);
										}

										if ($hasSublist) {
											$subList[$content->getNumberingLevel()] = $this->createElement('list');
											$listItem->appendChild($subList[$content->getNumberingLevel()]);
										}
									}

									// Refreshing list-item ID number
									$listCounter = $content->getNumberingId();
								}
							}
						}
						break;
					case "docx2jats\objectModel\body\Table":
						foreach ($sectionsOrBody as $section) {
							if ($contentId === $section->getAttribute('id') || $section->nodeName === "body") {
								$table = new JatsTable($content);
								$section->appendChild($table);
								$table->setContent();

							}
						}
						break;
				}
			}
		}
	}
}

<?php namespace docx2jats\jats;

use docx2jats\DOCXArchive;
use docx2jats\jats\Par as JatsPar;
use docx2jats\objectModel\body\Par;
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
	
	public function __construct(DOCXArchive $docxArchive) {
		parent::__construct('1.0', 'utf-8');
		$this->docxArchive = $docxArchive;
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		
		$this->setBasicStructure();
		$this->setSections();
		$this->extractContent();
	}
	
	public function getJatsFile() {
		$this->save("test_jats.xml");
	}
	
	private function setBasicStructure() {
		$this->article = $this->createElement('article');
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
			foreach ($document->getContent() as $content) {
				
				// Appending section, must correspond section nested level; TODO optimize with recursion
				if ($content->getDimensionalSectionId() !== $latestSectionId) {
					$sectionNode = $this->createElement("sec");
					$sectionNode->setAttribute('id', implode('.', $content->getDimensionalSectionId()));
					$this->sections[] = $sectionNode;
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
				
				switch (get_class($content)) {
					
					case "docx2jats\objectModel\body\Par":
						$jatsPar = new JatsPar($content);
						
						foreach ($this->sections as $section) {
							if (implode('.', $content->getDimensionalSectionId()) === $section->getAttribute('id')) {
								$section->appendChild($jatsPar);
							}
						}
						
				}
			}
		}
	}
}

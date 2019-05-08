<?php

/**
 * @file example.php
 *
 * Copyright (c) 2018-2019 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief example of parsing DOCX with the converter
 */

require_once (__DIR__ . "/vendor/autoload.php");

use docx2jats\DOCXArchive;
use docx2jats\jats\Document;

// Parsing DOCX
$docxArchive = new DOCXArchive("samples/input/example.docx");

$contents = $docxArchive->getDocument()->getContent();

foreach ($contents as $content) {
	//echo "\n";
	if (get_class($content) === "docx2jats\objectModel\body\Par") {
		foreach ($content->getContent() as $textObject) {
			//echo $textObject->getString();
		}
		//echo $content->getNumberingId();
	} elseif (get_class($content) === "docx2jats\objectModel\body\Table") {
		foreach ($content->getContent() as $textObject) {
			//echo $textObject->getString();
		}
	}
}

// Creating JATS XML
$jatsXML = new Document($docxArchive);
$jatsXML->getJatsFile("samples/output/test_jats.xml");

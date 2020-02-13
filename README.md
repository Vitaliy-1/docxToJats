# Description 
DocxToJats is a PHP library that converts DOCX archives that comply OOXML standards into JATS XML (Journal Article Tag Suite) format. It's tested with DOCX produced by LibreOffice, MS Word, and Google Docs.
## Requirements
* The only requirement is PHP 7.2 or higher. CLI version if running from a command line
## Usage
1. `git clone https://github.com/Vitaliy-1/docxToJats.git`
2. `cd docxToJats`
3. `php docxtojats.php [/path/to/input/file.docx or /path/to/input/dir/] [/path/to/output/file.xml or /path/to/output/dir]`. E.g., to process a single file: `php docxtojats.php /mydir/file.docx /mydir/converted/file.xml` - if output filename is pointed, attached files, like figures, will be moved into the same folder; to process multiple files in a folder by relative path: `samples/input/ samples/output/`.
## Additional info
* The list of supported elements: https://github.com/Vitaliy-1/docxConverter#what-article-elements-are-supported. 
* How to achieve the best results: https://github.com/Vitaliy-1/docxConverter#how-to-achieve-best-results 

DocxToJats is used as a submodule to the DOCX Converter Plugin, written for Open Journal Systems. Unfortunately DOCX archive doesn't contain much metadata and JATS `front` elements remain not populated, thus, the best way would be to integrate docxToJats with editorial manager from where article's metadata can be retrieved. DOCX Converter Plugin is such an example.    

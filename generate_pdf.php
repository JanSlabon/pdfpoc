<?php

use setasign\SetaFpdf\SetaFpdf;

require_once __DIR__ . '/vendor/autoload.php';

class BoardPacket
{
    private const INDENT_WIDTH = 10;
    private const GREY_COLOR = [169, 169, 169]; // #a9a9a9
    private const BLACK_COLOR = [0, 0, 0]; // #000000

    private $tableOfContents = [];

    private $documents = [];

    private $boardPacket;

    private $currentStyle = [
        'font' => null,
        'style' => null,
        'size' => null,
        'color' => null
    ];

    public function __invoke()
    {
        $this->tableOfContents = [
            0 => ['title' => 'Agenda', 'children' => [1, 6, 9, 10, 13, 14, 15, 16]],
            1 => ['title' => 'Section I', 'children' => [2]],
            2 => ['title' => 'Nested Section I.1', 'children' => [3, 4, 5]],
            3 => ['title' => 'Nested Section I.1.a', 'children' => []],
            4 => ['title' => 'Nested Section I.1.b', 'children' => []],
            5 => ['title' => 'Nested Section I.1.c', 'children' => []],
            6 => ['title' => 'Nested Section I.2', 'children' => [7, 8]],
            7 => ['title' => 'Nested Section I.2.a', 'children' => []],
            8 => ['title' => 'Nested Section I.2.b', 'children' => []],
            9 => ['title' => 'Nested Section I.3', 'children' => []],
            10 => ['title' => 'Nested Section I.4', 'children' => [11, 12]],
            11 => ['title' => 'Nested Section I.4.a', 'children' => []],
            12 => ['title' => 'Nested Section I.4.b', 'children' => []],
            13 => ['title' => 'Section II', 'children' => []],
            14 => ['title' => 'Section III', 'children' => []],
            15 => ['title' => 'Section IV', 'children' => []],
            16 => ['title' => 'Section V', 'children' => []],
        ];

        $this->documents = [
            1 => [
                0 => ['title' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf', 'id' => 5, 'filename' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf'],
            ],
            2 => [
                0 => ['title' => '6LLkKP8HRcu10JpDqvrN-44479652.pdf', 'id' => 6, 'filename' => '6LLkKP8HRcu10JpDqvrN-44479652.pdf'],
                1 => ['title' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf', 'id' => 3, 'filename' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf'],
            ],
            4 => [
                0 => ['title' => '7O41edsOSmOFhCX2fQ2g-44605776.pdf', 'id' => 7, 'filename' => '7O41edsOSmOFhCX2fQ2g-44605776.pdf'],
            ],
        ];

        $boardPacketFileName = 'Board_Packet.pdf';
        $this->boardPacket = new SetaFpdf();

        $this->boardPacket->AddPage();

        $style = ['font' => 'Arial', 'style' => '', 'size' => 8, 'color' => self::GREY_COLOR];
        $this->writeLine('Carnegie Technologies', $style, null, 10);

        $style = ['size' => 16, 'color' => self::BLACK_COLOR];
        $this->writeLine('Agenda for Meeting', $style);

        $this->boardPacket->Ln(14);

        $eventDateTimeString = '6/25/2019 12:00 pm - 6/25/2019 1:00 pm';
        $style = ['size' => 12];
        $this->writeLine($eventDateTimeString, $style);

        $this->boardPacket->Ln(13);

        // Datetime of the last time Agenda object was updated
        $this->boardPacket->SetFontSize(12);
        $this->boardPacket->SetTextColor(169, 169, 169); // #a9a9a9
        $this->boardPacket->Write(12, 'Last Revision: ' . date('Y-m-d'));
        $this->boardPacket->Ln();

        // Additional spacer between revision and agenda items
        $this->boardPacket->Ln();

        // Reset color back to black
        $this->boardPacket->SetTextColor(0, 0, 0);

        $itemNum = 0;
        foreach ($this->tableOfContents[0]['children'] as $id) {
            $itemNum++;
            $this->processItem($this->tableOfContents[$id], $id, $itemNum, 0);
        }

        // Create a SetaPDF document instance of the FPDF result
        $document = SetaPDF_Core_Document::load(new SetaPDF_Core_Reader_String($this->boardPacket->Output('', 'S')));

        // Create a file writer and attach it to the document instance
        $writer = new SetaPDF_Core_Writer_File(__DIR__ . '/' . $boardPacketFileName);
        $document->setWriter($writer);

        $outlines = $document->getCatalog()->getOutlines();

        // Setup the Agenda outline item
        $item = SetaPDF_Core_Document_OutlinesItem::create($document, 'Agenda');

        // Hard coded to page 1, if we ever have cover page, will need to change the number or make it dynamic
        $destination = SetaPDF_Core_Document_Destination::createByPageNo($document, 1);
        $item->setDestination($destination);
        $outlines->appendChild($item);

        // Create a merger instance on this document. This is used to merge the attached files
        $merger = new SetaPDF_Merger($document);

        // Loop through the main root agenda items and create the table of contents
        /** @var int $child */
        foreach ($this->tableOfContents[0]['children'] as $child) {
            $this->buildTableOfContents($child, $outlines, $document, $merger);
        }

        // merge them together
        try {
            $merger->merge();
        } catch (Throwable $e) {
            error_log('Unable to merge the files together, failing.');
            error_log($e->getMessage());

            die();
        }

        // get the resulting document instance
        $document = $merger->getDocument();

        $document->getCatalog()->setPageMode(SetaPDF_Core_Document_PageMode::USE_OUTLINES);
        $document->setWriter(new SetaPDF_Core_Writer_File(__DIR__ . '/' . $boardPacketFileName));

        // Save and finish!
        $document->save()->finish();
    }

    /**
     * Builds the table of contents outline
     *
     * @param  int  $index
     * @param  \SetaPDF_Core_Document_OutlinesItem|\SetaPDF_Core_Document_Catalog_Outlines  $outlineItem
     * @param  \SetaPDF_Core_Document  $document
     * @param  \SetaPDF_Merger  $merger
     */
    private function buildTableOfContents($index, $outlineItem, SetaPDF_Core_Document $document, $merger): void
    {
        $item = SetaPDF_Core_Document_OutlinesItem::create($document, $this->tableOfContents[$index]['title']);
        $outlineItem->appendChild($item);

            // If the agenda item has documents attached to it, display them in the table of contents
        if (isset($this->documents[$index])) {
            /** @var array $attachedDoc */
            foreach ($this->documents[$index] as $attachedDoc) {
                $merger->addFile([
                    'filename' => __DIR__ . '/files/' . $attachedDoc['filename'],
                    'nameConfig' => [SetaPDF_Merger::DESTINATION_NAME => 'doc-' . $index]
                ]);

                $subItem = SetaPDF_Core_Document_OutlinesItem::create($document, $attachedDoc['title']);
                $subItem->setAction(new SetaPDF_Core_Document_Action_GoTo('doc-' . $index));
                $item->appendChild($subItem);

                $subDocument = $merger->getDocumentByFilename(__DIR__ . '/files/' . $attachedDoc['filename']);
                $subItem->appendChildCopy($subDocument->getCatalog()->getOutlines(), $document);
            }
        }

        /** @var int $child */
        foreach ($this->tableOfContents[$index]['children'] as $child) {
            $this->buildTableOfContents($child, $item, $document, $merger);
        }
    }

    private function processItem(array $item, $id, $itemNum, $level): void
    {
        // Agenda Section style
        $style = ['font' => 'Arial', 'style' => 'B', 'size' => 10, 'color' => self::BLACK_COLOR];
        $indent = null;
        $newLineHeight = 8;

        if ($level > 0) {
            $indent = ['width' => $level * self::INDENT_WIDTH];
            $style = ['font' => 'Arial', 'style' => '', 'size' => 8, 'color' => self::BLACK_COLOR];
            $newLineHeight = 5;
        }

        $text = $this->formatItemBullet($itemNum, $level) . '. ' . $item['title'];
        $this->writeLine($text, $style, $indent, $newLineHeight);

        if (array_key_exists($id, $this->documents)) {
            $this->processItemDocuments($this->documents[$id], $level);
        }

        if (count($item['children'])) {
            $childItemNum = 0;
            $level++;
            foreach ($item['children'] as $cid ) {
                $this->processItem($this->tableOfContents[$cid], $cid, ++$childItemNum, $level);
            }
        }
    }

    private function processItemDocuments(array $documents, int $level): void
    {
        $firstFile = true;

        foreach ($documents as $document) {
            $fileName = $document['title'];
            $style = ['font' => 'Arial', 'size' => 8, 'style' => '', 'link' => '#doc-' . $document['id']];

            if ($firstFile) {
                $indent = ['width' => $level * self::INDENT_WIDTH + 5];
                $text = 'Attachments:   ' . $fileName;
            } else {
                //Add a new line space between last attachment
                $this->boardPacket->Ln(5);

                $indent = ['width' => $level * self::INDENT_WIDTH + 27];
                $text = $fileName;
            }

            $this->writeLine($text, $style, $indent);

            $firstFile = false;
        }

        $this->boardPacket->Ln(8);
    }

    private function writeLine(string $text, ?array $style = null, ?array $indent = null, ?int $newlineSize = null): void
    {
        if (is_array($indent)) {
            try {
                $this->boardPacket->Cell($indent['width']);
            } catch (Throwable $e) {
                error_log($e->getMessage());
            }
        }

        // Some part of the style is changing, update $this->currentStyle to reflect the change
        if (is_array($style)) {
            foreach ($style as $key => $value) {
                $this->currentStyle[$key] = $value;
            }
        }
        $this->boardPacket->SetFont($this->currentStyle['font'], $this->currentStyle['style'], $this->currentStyle['size']);
        $this->boardPacket->SetTextColor(...$this->currentStyle['color']);

        $link = $style['link'] ?? '';

        try {
            $this->boardPacket->Write($this->currentStyle['size'], $text, $link);
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }

        // If a new line size was passed in, then issue the new line command with the passed in size
        if ($newlineSize) {
            $this->boardPacket->Ln($newlineSize);
        }
    }

    private function formatItemBullet(int $itemNum, int $level)
    {
        $result = '';

        switch ($level) {
            case 0:
                $result = $this->convertNumberToRomanNumeral($itemNum);
                break;

            case 1:
                $result = $itemNum;
                break;

            case 2:
                $result = $this->convertNumberToLetter($itemNum);
                break;
        }

        return $result;
    }

    private function convertNumberToRomanNumeral(int $number): string
    {
        $result = '';

        // Create a lookup array that contains all of the Roman numerals.
        $lookup = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];

        foreach ($lookup as $roman => $value) {
            // Determine the number of matches.
            $matches = intdiv($number, $value);

            // Add the same number of characters to the string.
            $result .= str_repeat($roman, $matches);

            // Set the integer to be the remainder of the integer and the value.
            $number %= $value;
        }

        // The Roman numeral should be built, return it.
        return $result;
    }

    private function convertNumberToLetter(int $number): string
    {
        $return = '';

        do {
            // If the number is greater than 26(z) then concat an 'a' to the string
            // Then subtract 26 from the number and check again to see if number is still bigger than 26.
            if ($number > 26) {
                $continue = true;
                $number -= 26;
                $return .= 'a';
            } else {
                // number is less than or equal to 26, convert it to corresponding letter by adding
                // the number to a 96(`), this is so that 1 = a, 2 = b, 3 = c, etc.
                $return .= chr($number + 96);
                $continue = false;
            }
        } while ($continue);

        return $return;
    }
}

$boardPacket = new BoardPacket();
$boardPacket();

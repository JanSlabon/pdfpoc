<?php

use setasign\SetaFpdf\SetaFpdf;

require_once __DIR__ . '/vendor/autoload.php';

class BoardPacket
{
    private $tableOfContents = [];

    private $documents = [];

    public function __invoke()
    {
        $this->tableOfContents = [
            0 => ['title' => 'Agenda', 'children' => [0 => 257, 1 => 258, 2 => 259]],
            257 => ['title' => '1st', 'children' => []],
            258 => ['title' => 'My new agenda', 'children' => []],
            259 => ['title' => "let's see how this works", 'children' => [0 => 260]],
            260 => ['title' => 'Indent', 'children' => [0 => 261]],
            261 => ['title' => 'Another indent', 'children' => []],
        ];

        $this->documents = [
            257 => [
                0 => ['title' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf', 'filename' => '3xiX27gQcKdHqhhGXKqO-44616797.pdf'],
            ],
            260 => [
                0 => ['title' => '6LLkKP8HRcu10JpDqvrN-44479652.pdf', 'filename' => '6LLkKP8HRcu10JpDqvrN-44479652.pdf'],
                1 => ['title' => '7O41edsOSmOFhCX2fQ2g-44605776.pdf', 'filename' => '7O41edsOSmOFhCX2fQ2g-44605776.pdf'],
                2 => ['title' => 'TpVWxkKbTCyt2OLLtHxV-44588801.pdf', 'filename' => 'TpVWxkKbTCyt2OLLtHxV-44588801.pdf'],
            ],
        ];

        $boardPacketFileName = 'Board_Packet.pdf';
        $boardPacket = new SetaFpdf();

        $boardPacket->AddPage();

        $boardPacket->SetFont('Arial', '', 22);
        $boardPacket->Write(22, 'Agenda for Meeting');

        $boardPacket->Ln();

        // Datetime of the last time Agenda object was updated
        $boardPacket->SetFontSize(12);
        $boardPacket->SetTextColor(169, 169, 169); // #a9a9a9
        $boardPacket->Write(12, 'Last Revision: ' . date('Y-m-d'));
        $boardPacket->Ln();

        // Additional spacer between revision and agenda items
        $boardPacket->Ln();

        // Reset color back to black
        $boardPacket->SetTextColor(0, 0, 0);

        // Create a SetaPDF document instance of the FPDF result
        $document = SetaPDF_Core_Document::load(new SetaPDF_Core_Reader_String($boardPacket->Output('', 'S')));

        // Create a file writer and attach it to the document instance
        $writer = new SetaPDF_Core_Writer_File(__DIR__ . '/' . $boardPacketFileName);
        $document->setWriter($writer);

        $outlines = $document->getCatalog()->getOutlines();

        // Create a merger instance on this document. This is used to merge the attached files
        $merger = new SetaPDF_Merger($document);

        $this->buildTableOfContents(0, $outlines, $document, $merger); // Start with the main Agenda

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

    private function buildTableOfContents($index, $outlineItem, $document, $merger)
    {
        $item = SetaPDF_Core_Document_OutlinesItem::create($document, $this->tableOfContents[$index]['title']);
        $outlineItem->appendChild($item);

        // If the agenda item has documents attached to it, display them in the table of contents
        if (isset($this->documents[$index])) {
            /** @var array $attachedDoc */
            foreach ($this->documents[$index] as $attachedDoc) {
                $merger->addFile([
                    'filename' => __DIR__ . '/files/' . $attachedDoc['filename'],
                    'outlinesConfig' => [
                        SetaPDF_Merger::OUTLINES_TITLE => $attachedDoc['title'],
                        SetaPDF_Merger::OUTLINES_PARENT => $item,
                        SetaPDF_Merger::OUTLINES_COPY => SetaPDF_Merger::COPY_OUTLINES_AS_CHILDS,
                    ],
                ]);
            }
        }

        /** @var int $child */
        foreach ($this->tableOfContents[$index]['children'] as $child) {
            $this->buildTableOfContents($child, $item, $document, $merger);
        }
    }
}

$boardPacket = new BoardPacket();
$boardPacket();

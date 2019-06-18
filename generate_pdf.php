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
                0 => ['title' => 'U3.2 1st Quarterly Progress Checker2.pdf', 'id' => 23],
                1 => ['title' => 'CT_DesktopWallpaper-1.jpg', 'id' => 1],
                2 => ['title' => '1c41e85330ee8032a95049d6bc6b58e8.jpg 11-55-40-729.pdf', 'id' => 28]
            ]
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
        $this->buildTableOfContents(0, $outlines, $document); // Start with the main Agenda

        $document->getCatalog()->setPageMode(SetaPDF_Core_Document_PageMode::USE_OUTLINES);
        $document->setWriter(new SetaPDF_Core_Writer_File(__DIR__ . '/' . $boardPacketFileName));

        // Save and finish!
        $document->save()->finish();
    }

    private function buildTableOfContents($index, $outlineItem, $document)
    {
        $item = SetaPDF_Core_Document_OutlinesItem::create($document, $this->tableOfContents[$index]['title']);
        $outlineItem->appendChild($item);

        // If the agenda item has documents attached to it, display them in the table of contents
        if (isset($this->documents[$index])) {
            /** @var array $attachedDoc */
            foreach ($this->documents[$index] as $attachedDoc) {
                $fileItem = SetaPDF_Core_Document_OutlinesItem::create($document, $attachedDoc['title']);
                $outlineItem->appendChild($fileItem);
            }
        }

        /** @var int $child */
        foreach ($this->tableOfContents[$index]['children'] as $child) {
            $this->buildTableOfContents($child, $item, $document);
        }
    }
}

$boardPacket = new BoardPacket();
$boardPacket();

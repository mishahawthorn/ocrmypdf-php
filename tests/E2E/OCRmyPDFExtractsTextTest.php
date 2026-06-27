<?php

namespace mishahawthorn\OCRmyPDF\Tests\E2E;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\NoWritePermissionsException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use mishahawthorn\OCRmyPDF\OCRmyPDFException;
use mishahawthorn\OCRmyPDF\Process;
use mishahawthorn\OCRmyPDF\UnsuccessfulCommandException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNull;

#[CoversClass(OCRmyPDF::class)]
#[CoversClass(Command::class)]
#[CoversClass(Process::class)]
class OCRmyPDFExtractsTextTest extends TestCase
{
    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testExtractTextToTempSidecar(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->language('eng')
            ->extractText();

        $outputPath = $instance->run();

        assertFileExists($outputPath);
        assertIsString($instance->getText());
        assertNotEmpty($instance->getText());
    }

    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testGetTextIsNullWhenNotRequested(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->deskew()
            ->rotatePages()
            ->optimize(1);
        $instance->run();

        assertNull($instance->getText());
    }
}

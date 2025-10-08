<?php

namespace mishahawthorn\OCRmyPDF\Tests\E2E;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\NoWritePermissionsException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use mishahawthorn\OCRmyPDF\OCRmyPDFException;
use mishahawthorn\OCRmyPDF\Process;
use mishahawthorn\OCRmyPDF\Tests\Helpers;
use mishahawthorn\OCRmyPDF\UnsuccessfulCommandException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertFileIsReadable;
use function PHPUnit\Framework\assertFileIsWritable;

#[CoversClass(OCRmyPDF::class)]
#[CoversClass(Command::class)]
#[CoversClass(Process::class)]
class OCRmyPDFGeneratesOutputFileTest extends TestCase
{
    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_NoParameters(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $instance = new OCRmyPDF($inputFile);
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc2_NoParameters(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc2.pdf";
        $instance = new OCRmyPDF($inputFile);
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc3_NoParameters(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc3.pdf";
        $instance = new OCRmyPDF($inputFile);
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_img1_NoParameters(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_img1.png";
        $instance = new OCRmyPDF($inputFile);
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_SetOutputPDFManually(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $instance = new OCRmyPDF($inputFile);
        $instance->setOutputPDFPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename((string)tempnam(sys_get_temp_dir(), 'ocr_')) . ".pdf");
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }
}

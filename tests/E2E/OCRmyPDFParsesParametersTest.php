<?php

namespace mishahawthorn\OCRmyPDF\Tests\E2E;

use InvalidArgumentException;
use mishahawthorn\OCRmyPDF\Command;
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
class OCRmyPDFParsesParametersTest extends TestCase
{
    /**
     * @throws OCRmyPDFException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_SetTitleParam(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $outputPath = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            basename((string)tempnam(sys_get_temp_dir(), 'ocr_')) .
            ".pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->setOutputPDFPath($outputPath)
            ->setParam('--title', "Lorem Ipsum");

        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc4_SetArrayParamValue(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc4.pdf";
        $outputPath = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            basename((string)tempnam(sys_get_temp_dir(), 'ocr_')) .
            ".pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->setOutputPDFPath($outputPath)
            ->setParam('--pages', ['1-2', '4']);

        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }

    /**
     * @throws OCRmyPDFException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_SetInvalidParam(): void
    {
        $this->expectException(UnsuccessfulCommandException::class);

        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $outputPath = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            basename((string)tempnam(sys_get_temp_dir(), 'ocr_')) .
            ".pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->setOutputPDFPath($outputPath)
            ->setParam('--this-is-not-a-valid-param');

        $instance->run();
    }

    /**
     * @throws OCRmyPDFException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_SetMalformedParam(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $outputPath = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            basename((string)tempnam(sys_get_temp_dir(), 'ocr_')) .
            ".pdf";

        $instance = OCRmyPDF::make($inputFile)
            ->setOutputPDFPath($outputPath)
            ->setParam('this-is-not-a-valid-param-at-all');

        $instance->run();
    }
}

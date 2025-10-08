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
class OCRmyPDFAcceptsStdinInputTest extends TestCase
{
    /**
     * @throws OCRmyPDFException
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testProcess_en_US_doc1_NoParameters(): void
    {
        $inputFile = __DIR__ . DIRECTORY_SEPARATOR . "examples" . DIRECTORY_SEPARATOR . "en_US_doc1.pdf";
        $instance = new OCRmyPDF();
        $instance->setInputData((string)file_get_contents($inputFile), (int)filesize($inputFile));
        $outputPath = $instance->run();
        assertFileExists($outputPath);
        assertFileIsReadable($outputPath);
        assertFileIsWritable($outputPath);
        Helpers::echoOutputPathWithTestContext($outputPath);
    }
}
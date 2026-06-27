<?php

namespace mishahawthorn\OCRmyPDF\Tests\Unit;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\FileNotFoundException;
use mishahawthorn\OCRmyPDF\NoWritePermissionsException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use mishahawthorn\OCRmyPDF\OCRmyPDFNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

#[CoversClass(OCRmyPDF::class)]
#[CoversClass(Command::class)]
class OCRmyPDFTest extends TestCase
{
    public function testConvenienceSettersPopulateParameters(): void
    {
        $instance = OCRmyPDF::make('in.pdf')
            ->language('eng', 'deu')
            ->deskew()
            ->rotatePages()
            ->clean()
            ->removeBackground()
            ->forceOcr()
            ->skipText()
            ->redoOcr()
            ->optimize(2);

        $params = $instance->command->parameters;
        assertEquals('eng+deu', $params['-l']);
        assertTrue($params['--deskew']);
        assertTrue($params['--rotate-pages']);
        assertTrue($params['--clean']);
        assertTrue($params['--remove-background']);
        assertTrue($params['--force-ocr']);
        assertTrue($params['--skip-text']);
        assertTrue($params['--redo-ocr']);
        assertEquals('2', $params['--optimize']);
    }

    public function testToggleParamRemovesFlagWhenDisabled(): void
    {
        $instance = OCRmyPDF::make('in.pdf')->deskew();
        assertArrayHasKey('--deskew', $instance->command->parameters);

        $instance->deskew(false);
        assertArrayNotHasKey('--deskew', $instance->command->parameters);
    }

    public function testOptimizeRejectsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OCRmyPDF::make('in.pdf')->optimize(9);
    }

    public function testSetThreadLimitAndTempDir(): void
    {
        $instance = OCRmyPDF::make('in.pdf')
            ->setThreadLimit(3)
            ->setTempDir('/tmp/custom');

        assertEquals(3, $instance->command->threadLimit);
        assertEquals('/tmp/custom', $instance->command->tempDir);
    }

    public function testExtractTextEnablesSidecar(): void
    {
        $instance = OCRmyPDF::make('in.pdf')->extractText('/tmp/out.txt');
        assertTrue($instance->command->useSidecar);
        assertEquals('/tmp/out.txt', $instance->command->sidecarPath);
    }

    public function testSetInputDataDerivesSize(): void
    {
        $instance = OCRmyPDF::make()->setInputData('hello');
        assertEquals(5, $instance->command->inputDataSize);
    }

    public function testFluentSettersReturnSelf(): void
    {
        $instance = OCRmyPDF::make('in.pdf');
        assertSame($instance, $instance->setTimeout(10.0));
        assertSame($instance, $instance->setLogger(new NullLogger()));
    }

    public function testVersionReturnsNonEmptyString(): void
    {
        assertNotEmpty(OCRmyPDF::make()->version());
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function testCheckWritePermissions(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            Assert::markTestSkipped('OCRmyPDFTest::testCheckWritePermissions unimplemented on Windows-based platforms, skipping.');
        }
        $this->expectException(NoWritePermissionsException::class);
        OCRmyPDF::checkWritePermissions("/dev/null");
    }

    public function testCheckFilePath(): void
    {
        $this->expectException(FileNotFoundException::class);
        OCRmyPDF::checkFilePath(substr(md5((string)rand()), 0, 20));
    }

    /**
     * @throws OCRmyPDFNotFoundException
     */
    public function testSetExecutable(): void
    {
        $instance = new OCRmyPDF();
        assertInstanceOf(OCRmyPDF::class, $instance->setExecutable("ocrmypdf"));
    }
}

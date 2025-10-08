<?php

namespace mishahawthorn\OCRmyPDF\Tests\Unit;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\FileNotFoundException;
use mishahawthorn\OCRmyPDF\NoWritePermissionsException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use mishahawthorn\OCRmyPDF\OCRmyPDFNotFoundException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertInstanceOf;

#[CoversClass(OCRmyPDF::class)]
#[CoversClass(Command::class)]
class OCRmyPDFTest extends TestCase
{
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

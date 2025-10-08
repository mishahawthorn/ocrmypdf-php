<?php

namespace mishahawthorn\OCRmyPDF\Tests\Unit;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;

#[CoversClass(OCRmyPDF::class)]
#[CoversClass(Command::class)]
class CommandTest extends TestCase
{
    public function testGetOCRmyPDFVersion(): void
    {
        $version = (new Command())->getOCRmyPDFVersion();
        assertNotEmpty($version);
    }

    public function testGetTempDirDefaultTempDirectory(): void
    {
        $command = new Command();
        assertEquals(sys_get_temp_dir(), $command->getTempDir());
    }

    public function testGetTempDirCustomTempDirectory(): void
    {
        $customTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . rand(100000, 999999);
        $command = new Command(null, null, $customTempDir);
        assertEquals($customTempDir, $command->getTempDir());
    }
}

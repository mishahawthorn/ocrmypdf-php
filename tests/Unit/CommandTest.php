<?php

namespace mishahawthorn\OCRmyPDF\Tests\Unit;

use mishahawthorn\OCRmyPDF\Command;
use mishahawthorn\OCRmyPDF\NoWritePermissionsException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use mishahawthorn\OCRmyPDF\UnsuccessfulCommandException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertTrue;

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

    /**
     * @throws NoWritePermissionsException
     */
    public function testToArrayBuildsArgumentVector(): void
    {
        $command = new Command('input.pdf', 'output.pdf', null, 4, [
            '-l' => 'eng+deu',
            '--remove-background' => true,
            '--pages' => ['1-2', '4'],
        ]);

        $args = $command->toArray();

        assertEquals('ocrmypdf', $args[0]);
        assertContains('--jobs=4', $args);
        assertContains('-l=eng+deu', $args);
        assertContains('--remove-background', $args);
        assertContains('--pages=1-2,4', $args);
        // Input then output occupy the final two positions, unquoted/unescaped.
        assertEquals('input.pdf', $args[count($args) - 2]);
        assertEquals('output.pdf', $args[count($args) - 1]);
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function testToArrayAddsSidecar(): void
    {
        $command = new Command('input.pdf', 'output.pdf');
        $command->useSidecar = true;
        $command->sidecarPath = 'out.txt';

        assertContains('--sidecar=out.txt', $command->toArray());
    }

    /**
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testCheckCommandExecutionSucceedsOnZeroExitCode(): void
    {
        $command = new Command('input.pdf', 'output.pdf');
        assertTrue(Command::checkCommandExecution($command, '', 'some warning', 0));
    }

    /**
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     */
    public function testCheckCommandExecutionThrowsOnNonZeroExitCode(): void
    {
        $this->expectException(UnsuccessfulCommandException::class);
        $command = new Command('input.pdf', 'output.pdf');
        Command::checkCommandExecution($command, '', 'PriorOcrFoundError', 6);
    }
}

<?php declare(strict_types=1);

namespace mishahawthorn\OCRmyPDF\Tests;
class Helpers
{
    /**
     * Outputs the provided path along with the name of the calling test method,
     * if identifiable from the backtrace.
     */
    public static function echoOutputPathWithTestContext(string $outputPath): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callingTestMethod = $backtrace[1]['function'] ?? 'unknown_method';
        echo "Output from $callingTestMethod: $outputPath" . PHP_EOL;
    }

}
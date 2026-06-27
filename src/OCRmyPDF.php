<?php

namespace mishahawthorn\OCRmyPDF;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class OCRmyPDF
{
    public Command $command;

    /**
     * Maximum runtime in seconds before the OCRmyPDF process is terminated.
     */
    private ?float $timeout = null;

    /**
     * Optional PSR-3 logger for the generated command and its stderr output.
     */
    private ?LoggerInterface $logger = null;

    /**
     * Plaintext captured from the sidecar file during the last run.
     */
    private ?string $text = null;

    /**
     * OCRmyPDF constructor.
     * @param string|null $inputFile
     * @param Command|null $command
     */
    public function __construct(?string $inputFile = null, ?Command $command = null)
    {
        $this->command = $command ?? new Command();
        $this->setInputFile("$inputFile");
    }

    public static function make(?string $inputFile = null, ?Command $command = null): self
    {
        return new OCRmyPDF($inputFile, $command);
    }

    /**
     * @param string $filePath
     * @return bool
     * @throws NoWritePermissionsException
     */
    public static function checkWritePermissions(string $filePath): bool
    {
        $directory = dirname($filePath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new NoWritePermissionsException("Error: Could not create directory $directory");
        }
        $writableDirectory = is_writable($directory);
        $writableFile = true;
        if (file_exists($filePath)) $writableFile = is_writable($filePath);
        if ($writableFile && $writableDirectory) return true;

        $msg = [];
        $msg[] = "Error: No permission to write to $filePath";
        $msg[] = "Make sure you have the right outputFile and permissions "
            . "to write to the directory";
        $msg[] = '';
        $msg = join(PHP_EOL, $msg);

        throw new NoWritePermissionsException($msg);
    }

    /**
     * @param string $executablePath
     * @throws OCRmyPDFNotFoundException
     */
    public static function checkOCRmyPDFPresence(string $executablePath): void
    {
        if (file_exists($executablePath)) return;

        $cmd = stripos(PHP_OS, 'win') === 0
            ? 'where.exe ' . Command::escape($executablePath) . ' > NUL 2>&1'
            : 'type ' . Command::escape($executablePath) . ' > /dev/null 2>&1';
        system($cmd, $exitCode);

        if ($exitCode === 0) return;

        $currentPath = getenv('PATH');
        $msg = [];
        $msg[] = "Error: The command \"$executablePath\" was not found.";
        $msg[] = '';
        $msg[] = 'Make sure you have OCRmyPDF and required dependencies installed on your system:';
        $msg[] = 'https://github.com/jbarlow83/OCRmyPDF';
        $msg[] = '';
        $msg[] = "The current \$PATH is $currentPath";
        $msg = join(PHP_EOL, $msg);

        throw new OCRmyPDFNotFoundException($msg);
    }

    /**
     * @param string $filePath
     * @throws FileNotFoundException
     */
    public static function checkFilePath(string $filePath): void
    {
        if (file_exists($filePath)) return;

        $currentDir = __DIR__;
        $msg = [];
        $msg[] = "Error: The input file \"$filePath\" was not found or is inaccessible.";
        $msg[] = '';
        $msg[] = "The current __DIR__ is $currentDir";
        $msg = join(PHP_EOL, $msg);

        throw new FileNotFoundException($msg);
    }

    /**
     * @return string
     * @throws NoWritePermissionsException
     * @throws UnsuccessfulCommandException
     * @throws ProcessTimeoutException
     * @throws OCRmyPDFException
     */
    public function run(): string
    {
        $this->text = null;

        try {
            self::checkOCRmyPDFPresence($this->command->executable);
            if ($this->command->useFileAsInput) {
                self::checkFilePath(
                    $this->command->inputFilePath
                    ?? throw new InvalidArgumentException("Input file path is not set")
                );
            }

            $args = $this->command->toArray();
            $this->logger?->debug('Executing OCRmyPDF: ' . (string)$this->command);

            $process = new Process($args, $this->timeout);

            if (!$this->command->useFileAsInput) {
                $process->write(
                    $this->command->inputData ?? throw new InvalidArgumentException("Input data not set"),
                    $this->command->inputDataSize ?? throw new InvalidArgumentException("Input data size not set")
                );
                $process->closeStdin();
            }
            $output = $process->wait();

            if ($output["err"] !== '') {
                $this->logger?->debug('OCRmyPDF stderr: ' . $output["err"]);
            }

            if ($output["timedOut"]) {
                throw new ProcessTimeoutException(
                    "Error: OCRmyPDF did not finish within {$this->timeout} seconds and was terminated."
                );
            }

            Command::checkCommandExecution(
                $this->command,
                $output["out"],
                $output["err"],
                $output["exitCode"]
            );
        } catch (OCRmyPDFException $e) {
            if (isset($process)) {
                $process->closeStreams();
                try {
                    $process->closeHandle();
                } catch (InvalidArgumentException) {
                    // Handle already closed; nothing to clean up.
                }
            }
            if ($this->command->useFileAsOutput) $this->cleanTempFiles();
            $this->cleanSidecar();
            $this->logger?->error('OCRmyPDF failed: ' . $e->getMessage());
            throw $e;
        }

        $process->closeStreams()->closeHandle();

        if ($this->command->useSidecar) {
            $sidecarPath = $this->command->getSidecarPath();
            $contents = file_exists($sidecarPath) ? file_get_contents($sidecarPath) : false;
            $this->text = $contents === false ? null : $contents;
            $this->cleanSidecar();
        }

        if (!$this->command->useFileAsOutput) {
            return $output["out"];
        } else {
            return $this->command->getOutputPDFPath();
        }
    }

    /**
     * @param string $inputFile
     * @return $this
     */
    public function setInputFile(string $inputFile): OCRmyPDF
    {
        $this->command->useFileAsInput = true;
        $this->command->inputFilePath = $inputFile;
        return $this;
    }

    /**
     * @param string $inputData
     * @param int|null $inputDataSize Byte length of $inputData; derived via
     *   strlen() when omitted.
     * @return $this
     */
    public function setInputData(string $inputData, ?int $inputDataSize = null): OCRmyPDF
    {
        $this->command->useFileAsInput = false;
        $this->command->inputData = $inputData;
        $this->command->inputDataSize = $inputDataSize ?? strlen($inputData);
        return $this;
    }

    /**
     * Delete the output file only when it was a temp file we generated, so a
     * caller-supplied output path is never removed on failure.
     *
     * @throws NoWritePermissionsException
     */
    private function cleanTempFiles(): void
    {
        if ($this->command->generatedOutputPath
            && file_exists($this->command->getOutputPDFPath())
        ) {
            unlink($this->command->getOutputPDFPath());
        }
    }

    /**
     * Remove a sidecar file only when we generated it.
     *
     * @throws NoWritePermissionsException
     */
    private function cleanSidecar(): void
    {
        if ($this->command->useSidecar
            && $this->command->generatedSidecarPath
            && file_exists($this->command->getSidecarPath())
        ) {
            unlink($this->command->getSidecarPath());
        }
    }

    /**
     * @param string $executablePath
     * @return $this
     * @throws OCRmyPDFNotFoundException
     */
    public function setExecutable(string $executablePath): OCRmyPDF
    {
        self::checkOCRmyPDFPresence($executablePath);
        $this->command->executable = $executablePath;
        return $this;
    }

    /**
     * @throws NoWritePermissionsException
     */
    public function setOutputPDFPath(string|null $outputPDFPath): self
    {
        if ($outputPDFPath === null) {
            $this->command->useFileAsOutput = false;
        } else {
            $this->command->useFileAsOutput = true;
            if (self::checkWritePermissions($outputPDFPath)) {
                $this->command->outputPDFPath = $outputPDFPath;
                $this->command->generatedOutputPath = false;
            }
        }
        return $this;
    }

    /**
     * @param string|string[]|null $value
     */
    public function setParam(string $param, null|string|array $value = null): self
    {
        if (!str_starts_with($param, '-') && !str_starts_with($param, '--')) {
            throw new InvalidArgumentException("Parameter $param must start with a - or --");
        }

        $this->command->parameters[$param] = $value ?? true;
        return $this;
    }

    /**
     * Enable or disable a boolean flag, removing it entirely when disabled.
     */
    private function toggleParam(string $param, bool $enabled): self
    {
        if ($enabled) {
            return $this->setParam($param);
        }
        unset($this->command->parameters[$param]);
        return $this;
    }

    /**
     * Set the OCR language(s). Multiple languages are joined with '+', as
     * Tesseract expects (e.g. language('eng', 'deu') => -l eng+deu).
     */
    public function language(string ...$languages): self
    {
        if ($languages === []) {
            throw new InvalidArgumentException("At least one language must be provided");
        }
        return $this->setParam('-l', implode('+', $languages));
    }

    /**
     * Correct pages that are slightly rotated (skewed).
     */
    public function deskew(bool $enabled = true): self
    {
        return $this->toggleParam('--deskew', $enabled);
    }

    /**
     * Automatically rotate pages based on detected text orientation.
     */
    public function rotatePages(bool $enabled = true): self
    {
        return $this->toggleParam('--rotate-pages', $enabled);
    }

    /**
     * Clean the document before OCR, without altering the final output.
     */
    public function clean(bool $enabled = true): self
    {
        return $this->toggleParam('--clean', $enabled);
    }

    /**
     * Remove the solid background of scanned documents.
     */
    public function removeBackground(bool $enabled = true): self
    {
        return $this->toggleParam('--remove-background', $enabled);
    }

    /**
     * Set the optimization level (0 = none, 3 = maximum).
     */
    public function optimize(int $level): self
    {
        if ($level < 0 || $level > 3) {
            throw new InvalidArgumentException("Optimization level must be between 0 and 3, got $level");
        }
        return $this->setParam('--optimize', (string)$level);
    }

    /**
     * Rasterize and OCR every page, even pages that already contain text.
     */
    public function forceOcr(bool $enabled = true): self
    {
        return $this->toggleParam('--force-ocr', $enabled);
    }

    /**
     * Skip pages that already contain text rather than failing.
     */
    public function skipText(bool $enabled = true): self
    {
        return $this->toggleParam('--skip-text', $enabled);
    }

    /**
     * Remove existing OCR text and redo OCR while preserving the page content.
     */
    public function redoOcr(bool $enabled = true): self
    {
        return $this->toggleParam('--redo-ocr', $enabled);
    }

    /**
     * Limit the number of worker threads OCRmyPDF may use.
     */
    public function setThreadLimit(?int $threadLimit): self
    {
        $this->command->threadLimit = $threadLimit;
        return $this;
    }

    /**
     * Set the directory used for temporary files.
     */
    public function setTempDir(?string $tempDir): self
    {
        $this->command->tempDir = $tempDir;
        return $this;
    }

    /**
     * Write the recognized plaintext to a sidecar file and make it available
     * via getText() after run(). Pass a path to keep the file, or null to use a
     * temporary file that is read and then discarded.
     *
     * @throws NoWritePermissionsException When a supplied path is not writable.
     */
    public function extractText(?string $sidecarPath = null): self
    {
        $this->command->useSidecar = true;
        if ($sidecarPath !== null) {
            self::checkWritePermissions($sidecarPath);
            $this->command->sidecarPath = $sidecarPath;
            $this->command->generatedSidecarPath = false;
        }
        return $this;
    }

    /**
     * Return the recognized plaintext captured during the last run(), or null
     * if text extraction was not enabled.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Set a maximum runtime in seconds. The process is terminated and a
     * ProcessTimeoutException is thrown if it is exceeded. Pass null for no
     * limit.
     */
    public function setTimeout(?float $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Attach a PSR-3 logger that receives the generated command, stderr output
     * and any failures.
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Return the version string reported by the OCRmyPDF executable.
     *
     * @throws UnsuccessfulCommandException When the executable cannot be launched.
     */
    public function version(): string
    {
        return $this->command->getOCRmyPDFVersion();
    }
}

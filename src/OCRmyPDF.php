<?php

namespace mishahawthorn\OCRmyPDF;

use InvalidArgumentException;

class OCRmyPDF
{
    public Command $command;

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

    static function make(?string $inputFile = null, ?Command $command = null): self
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
        if (!is_dir(dirname($filePath))) mkdir(dirname($filePath));
        $writableDirectory = is_writable(dirname($filePath));
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
     * @throws OCRmyPDFException
     */
    public function run(): string
    {
        try {
            self::checkOCRmyPDFPresence($this->command->executable);
            if ($this->command->useFileAsInput) {
                self::checkFilePath(
                    $this->command->inputFilePath
                    ?? throw new InvalidArgumentException("Input file path is not set")
                );
            }

            $process = new Process((string)$this->command);

            if (!$this->command->useFileAsInput) {
                $process->write(
                    $this->command->inputData ?? throw new InvalidArgumentException("Input data not set"),
                    $this->command->inputDataSize ?? throw new InvalidArgumentException("Input data size not set")
                );
                $process->closeStdin();
            }
            $output = $process->wait();

            Command::checkCommandExecution($this->command, $output["out"], $output["err"]);
        } catch (OCRmyPDFException $e) {
            if ($this->command->useFileAsOutput) $this->cleanTempFiles();
            throw $e;
        }

        $process->closeStreams()->closeHandle();

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
     * @param int $inputDataSize
     * @return $this
     */
    public function setInputData(string $inputData, int $inputDataSize): OCRmyPDF
    {
        $this->command->useFileAsInput = false;
        $this->command->inputData = $inputData;
        $this->command->inputDataSize = $inputDataSize;
        return $this;
    }

    /**
     * @return void
     * @throws NoWritePermissionsException
     */
    private function cleanTempFiles(): void
    {
        if (file_exists($this->command->getOutputPDFPath())) {
            unlink($this->command->getOutputPDFPath());
        }
        if (file_exists($this->command->getOutputPDFPath())) {
            unlink($this->command->getOutputPDFPath());
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
}
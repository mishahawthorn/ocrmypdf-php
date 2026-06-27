# ocrmypdf-php

A simple PHP wrapper for OCRmyPDF

[![Latest Stable Version](http://poser.pugx.org/mishahawthorn/ocrmypdf/v)](https://packagist.org/packages/mishahawthorn/ocrmypdf)
[![Total Downloads](http://poser.pugx.org/mishahawthorn/ocrmypdf/downloads)](https://packagist.org/packages/mishahawthorn/ocrmypdf)
[![License](http://poser.pugx.org/mishahawthorn/ocrmypdf/license)](https://packagist.org/packages/mishahawthorn/ocrmypdf)
[![PHP Version Require](http://poser.pugx.org/mishahawthorn/ocrmypdf/require/php)](https://packagist.org/packages/mishahawthorn/ocrmypdf)
[![codecov](https://codecov.io/gh/mishahawthorn/ocrmypdf-php/graph/badge.svg?token=F3CU9T8LJU)](https://codecov.io/gh/mishahawthorn/ocrmypdf-php)

## Installation

Via [Composer][]:

    $ composer require mishahawthorn/ocrmypdf

**This library depends on [OCRmyPDF][].** Please see the GitHub repository for instructions on how to install OCRmyPDF
on your platform.

## Usage

### Basic example

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

//Return file path of outputted, OCRed PDF
echo OCRmyPDF::make('document.pdf')->run();

//Return file contents of outputted, OCRed PDF
echo OCRmyPDF::make('scannedImage.png')->setOutputPDFPath(null)->run();
```

## API

### setParam

Define invocation parameters for `ocrmypdf`. See `ocrmypdf --help` for a list of available parameters.

> [!IMPORTANT]
> Parameters configured via `setParam` will override any other parameters or configurations set otherwise.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

//Passing a single parameter with a value
OCRmyPDF::make('document_zh-CN.pdf')
    ->setParam('-l', 'chi_sim')
    ->run();

//Passing a single parameter without a value
OCRmyPDF::make('document_withBackground.pdf')
    ->setParam('--remove-background')
    ->run();

//Passing multiple parameters
OCRmyPDF::make('document_withoutAttribution.pdf')
    ->setParam('--title', 'Lorem Ipsum')
    ->setParam('--keywords', 'Lorem,Ipsum,dolor,sit,amet')
    ->run();
```

### setInputData

Pass image/PDF data loaded in memory into `ocrmypdf` directly via stdin.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

//Using Imagick
$data = $img->getImageBlob();
$size = $img->getImageLength();

//Using GD
ob_start();
imagepng($img, null, 0);
$size = ob_get_length();
$data = ob_get_clean();

OCRmyPDF::make()
    ->setInputData($data, $size)
    ->run();
```

### setOutputPDFPath

Specify a writable path where `ocrmypdf` should generate output PDF.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;
OCRmyPDF::make('document.pdf')
    ->setOutputPDFPath('/outputDir/ocr_document.pdf')
    ->run();
```

### setExecutable

Define a custom location of the `ocrmypdf` executable, if by any reason it is not present in the `$PATH`.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;
OCRmyPDF::make('document.pdf')
    ->setExecutable('/path/to/ocrmypdf')
    ->run();
```

### extractText / getText

Write the recognized plaintext to a sidecar file and read it back after `run()`. Call `extractText()` with no
argument to use a temporary file that is read and discarded, or pass a path to keep the text file.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

$ocr = OCRmyPDF::make('document.pdf')
    ->language('eng')
    ->extractText();

$pdfPath = $ocr->run();
$text = $ocr->getText(); //Recognized plaintext
```

### Convenience setters

Thin, type-safe wrappers over the most common `ocrmypdf` parameters. Each is equivalent to the matching
`setParam` call.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

OCRmyPDF::make('document.pdf')
    ->language('eng', 'deu') // -l eng+deu
    ->deskew()               // --deskew
    ->rotatePages()          // --rotate-pages
    ->clean()                // --clean
    ->removeBackground()     // --remove-background
    ->optimize(3)            // --optimize 3 (0-3)
    ->skipText()             // --skip-text  (or ->forceOcr() / ->redoOcr())
    ->setThreadLimit(4)      // --jobs 4
    ->setTempDir('/var/tmp')
    ->run();
```

Boolean setters accept `false` to remove the flag, e.g. `->deskew(false)`.

### setTimeout

Terminate the `ocrmypdf` process if it runs longer than the given number of seconds. A
`ProcessTimeoutException` is thrown when the limit is exceeded.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

OCRmyPDF::make('document.pdf')
    ->setTimeout(120) //seconds
    ->run();
```

### setLogger

Attach any [PSR-3][] logger to receive the generated command, stderr output and any failures.

```php
use mishahawthorn\OCRmyPDF\OCRmyPDF;

OCRmyPDF::make('document.pdf')
    ->setLogger($psr3Logger)
    ->run();
```

## License

ocrmypdf-php is released under the [MIT License][].

## Credits

Development of ocrmypdf-php is based on the [tesseract-ocr-for-php][] PHP wrapper library for `tesseract`
developed by [thiagoalessio][] and associated contributors.

[Composer]: http://getcomposer.org/

[OCRmyPDF]: https://github.com/jbarlow83/OCRmyPDF

[PSR-3]: https://www.php-fig.org/psr/psr-3/

[MIT License]: https://github.com/mishahawthorn/ocrmypdf-php/blob/main/LICENSE

[tesseract-ocr-for-php]: https://github.com/thiagoalessio/tesseract-ocr-for-php

[thiagoalessio]: https://github.com/thiagoalessio

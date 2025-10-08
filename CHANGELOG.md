# Changelog

All notable changes to `ocrmypdf-php` will be documented in this file

## v1.0.0 - 2025-10-08

> [!IMPORTANT]
Breaking change to namespacing, see more below.

### What's Changed

* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/mishahawthorn/ocrmypdf-php/pull/12
* Bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/mishahawthorn/ocrmypdf-php/pull/13
* Update namespacing and docs to account for username change by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/14

### Migration Guide: `mishagp/ocrmypdf` → `mishahawthorn/ocrmypdf`

The package name has been updated from `mishagp/ocrmypdf` to `mishahawthorn/ocrmypdf` to reflect an updated GitHub username.

#### For Users

##### Step 1: Update your `composer.json`

Replace the old package name with the new one in your project's `composer.json`:

```json
{
  "require": {
    "mishahawthorn/ocrmypdf": "^1.0"
  }
}

```
##### Step 2: Update your dependencies

Run the following command to update your dependencies:

```shell
composer update mishahawthorn/ocrmypdf

```
Alternatively, you can remove the old package and require the new one:

```shell
composer remove mishagp/ocrmypdf
composer require mishahawthorn/ocrmypdf

```
##### Step 3: Update namespace imports (if applicable)

If you were using the old package with a `mishagp` namespace, update your imports to use `mishahawthorn`:

```php
// Old
use mishagp\OCRmyPDF\OCRmyPDF;

// New
use mishahawthorn\OCRmyPDF\OCRmyPDF;

```
**Note:** If the namespace was already `mishahawthorn` in previous versions, no code changes are required.

##### Step 4: Clear caches

Clear your Composer cache to ensure you're pulling from the correct repository:

```shell
composer clear-cache

```
#### Compatibility

This is a **non-breaking change** if the namespace was already set to `mishahawthorn`. Only the package name has changed; all functionality remains the same.

#### Support

If you encounter any issues during migration, please report issues at [https://github.com/mishahawthorn/ocrmypdf-php/issues](https://github.com/mishahawthorn/ocrmypdf-php/issues)

**Full Changelog**: https://github.com/mishahawthorn/ocrmypdf-php/compare/v0.4.0...v1.0.0

## v0.4.0 - 2024-12-15

### What's Changed

* Bump codecov/codecov-action from 3 to 4 by @dependabot in https://github.com/mishahawthorn/ocrmypdf-php/pull/8
* Bump codecov/codecov-action from 4 to 5 by @dependabot in https://github.com/mishahawthorn/ocrmypdf-php/pull/10
* Update phpstan/phpstan requirement from ^1.10 to ^1.10 || ^2.0 by @dependabot in https://github.com/mishahawthorn/ocrmypdf-php/pull/9
* Update PHPUnit, increase PHPStan scrutiny, improve tests by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/11

**Full Changelog**: https://github.com/mishahawthorn/ocrmypdf-php/compare/v0.3.0...v0.4.0

## v0.3.0 - 2023-12-19

### What's Changed

* Implement flexible parameters and add static constructor by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/7

**Full Changelog**: https://github.com/mishahawthorn/ocrmypdf-php/compare/v0.2.1...v0.3.0

## v0.2.1 - 2023-12-19

### What's Changed

* Fix: Better error detection and handling by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/6

**Full Changelog**: https://github.com/mishahawthorn/ocrmypdf-php/compare/v0.2.0...v0.2.1

## v0.2.0 - 2023-12-19

### What's Changed

* Implement CI via GitHub Actions by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/1
* Bump actions/checkout from 3 to 4 by @dependabot in https://github.com/mishahawthorn/ocrmypdf-php/pull/4
* Add PHPStan static analysis by @mishahawthorn in https://github.com/mishahawthorn/ocrmypdf-php/pull/5

**Full Changelog**: https://github.com/mishahawthorn/ocrmypdf-php/compare/v0.1.0...v0.2.0

## 1.0.0 - 2021-05-30

- Initial release! 🎉

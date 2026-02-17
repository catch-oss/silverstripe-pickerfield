# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Upgraded to Silverstripe 6 compatibility
- Updated PHP requirement to ^8.5
- Migrated test suite to PHPUnit 11
- Renamed `code/` directory to `src/` for PSR-4 standard
- Updated SS5 namespaces to SS6 equivalents:
  - `SilverStripe\ORM\ArrayList` to `SilverStripe\Model\List\ArrayList`
  - `SilverStripe\ORM\SS_List` to `SilverStripe\Model\List\SS_List`
  - `SilverStripe\ORM\PaginatedList` to `SilverStripe\Model\List\PaginatedList`
  - `SilverStripe\View\ArrayData` to `SilverStripe\Model\ArrayData`
  - `SilverStripe\ORM\ValidationException` to `SilverStripe\Core\Validation\ValidationException`
- Updated `symbiote/silverstripe-gridfieldextensions` dependency to ^5.0

### Added
- Comprehensive test suite (43 tests, 75 assertions, ~70% coverage)
- MIGRATION-PLAN.md documenting all changes
- PHPUnit 11 configuration with SS framework bootstrap

### Fixed
- Wrong Controller import in PickerFieldEditHandler (was GraphQL\Controller, now Control\Controller)
- Missing use statements for ValidationException and ManyManyList in PickerFieldEditHandler
- Undefined $controller variable in PickerFieldEditHandler catch block
- PHP 8.5 compatibility (implicit nullable params, return types, typed properties)

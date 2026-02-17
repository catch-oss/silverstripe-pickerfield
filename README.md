# Silverstripe PickerField

<!-- PROJECT SHIELDS -->
[![SonarCloud](https://github.com/catch-oss/silverstripe-pickerfield/actions/workflows/sonar.yml/badge.svg)](https://github.com/catch-oss/silverstripe-pickerfield/actions/workflows/sonar.yml)
[![Test](https://github.com/catch-oss/silverstripe-pickerfield/actions/workflows/test.yml/badge.svg)](https://github.com/catch-oss/silverstripe-pickerfield/actions/workflows/test.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=bugs)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=code_smells)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=coverage)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Duplicated Lines Density](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=duplicated_lines_density)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=ncloc)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=reliability_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=security_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=sqale_index)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=sqale_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-silverstripe-pickerfield&metric=vulnerabilities)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-silverstripe-pickerfield)

A flexible picker field for Silverstripe CMS that provides GridField-based record selection with search, add existing, and inline editing capabilities. Supports has_one and many_many relationships.

## Compatibility

| Version | Silverstripe | PHP |
|---------|-------------|-----|
| release/6 | ^6.0 | ^8.5 |
| release/5 | ^5.1 | ~8.4 |

## Installation

```bash
composer require thewebmen/silverstripe-pickerfield
```

## Usage

### Many-Many Picker

```php
use TheWebmen\PickerField\Controllers\PickerField;

$field = PickerField::create('Tags', 'Tags', $this->Tags());
```

### Has-One Picker

```php
use TheWebmen\PickerField\Controllers\HasOnePickerField;

$field = HasOnePickerField::create($this, 'RelatedID', 'Related Item', $this->Related());
```

## License

See [LICENSE](LICENSE) file.

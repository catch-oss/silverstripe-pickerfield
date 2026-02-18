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

## Overview

PickerField provides a GridField-based UI for managing `has_one` and `many_many` relationships in Silverstripe CMS. It replaces the default relationship management fields with a searchable, paginated picker that lets content editors link existing records or create new ones inline.

### Problem it solves

Silverstripe's built-in `GridField` is designed for managing `has_many` relationships where the parent owns the child records. It doesn't handle `has_one` or `many_many` relationships well out of the box:

- **has_one**: There's no standard GridField for selecting a single related record. Developers typically fall back to `DropdownField`, which doesn't scale when there are hundreds of options.
- **many_many**: The default GridField config allows creating new records but not searching for and linking existing ones.

PickerField solves both by providing a search-and-select interface backed by GridField components, with proper relationship handling for both `has_one` (sets the foreign key) and `many_many` (manages the join table).

### Features

- Search-and-select UI for linking existing records via a popup search form
- Support for `has_one` and `many_many` relationships with correct write semantics
- Optional inline record creation (add new records without leaving the page)
- Optional inline editing (edit related records in a detail form)
- Customisable search filters and excludes to narrow the available records
- Custom search lists to override the default candidate pool
- Drag-and-drop sorting via `many_many_extraFields` sort column
- Unlink (not delete) semantics: removing a record from the picker only breaks the relationship, it does not delete the record from the database
- Fluent API with method chaining for configuration

## Compatibility

| Version | Silverstripe | PHP |
|---------|-------------|-----|
| release/6 | ^6.0 | ^8.5 |
| release/5 | ^5.1 | ~8.4 |

## Requirements

- `silverstripe/framework` ^6.0
- `symbiote/silverstripe-gridfieldextensions` ^5.0

## Installation

```bash
composer require thewebmen/silverstripe-pickerfield
```

## Usage

All examples assume you are inside a `getCMSFields()` method on a DataObject.

### Many-Many Picker

Select from existing records to attach to a `many_many` relationship:

```php
use TheWebmen\PickerField\Controllers\PickerField;

// Basic usage - search and link existing Tag records
$field = PickerField::create('Tags', 'Tags', $this->Tags());
```

### Has-One Picker

Select a single related record for a `has_one` relationship:

```php
use TheWebmen\PickerField\Controllers\HasOnePickerField;

// The first argument is $this (the parent DataObject)
// The second argument is the has_one field name with 'ID' suffix
$field = HasOnePickerField::create($this, 'AuthorID', 'Author', $this->Author());
```

### Enable Inline Creation

Allow editors to create new records directly from the picker:

```php
$field = PickerField::create('Tags', 'Tags', $this->Tags());
$field->enableCreate();

// With a custom button label
$field->enableCreate('Add New Tag');
```

### Enable Inline Editing

Allow editors to edit related records in a detail form:

```php
$field = PickerField::create('Tags', 'Tags', $this->Tags());
$field->enableEdit();

// Enable both create and edit
$field->enableCreate()->enableEdit();
```

### Custom Search Button Title

Override the default "Select ClassName(s)" button label:

```php
$field = PickerField::create('Tags', 'Tags', $this->Tags());
$field->setSelectTitle('Choose Tags');
```

### Filter Search Results

Restrict which records appear in the search popup:

```php
$field = PickerField::create('Tags', 'Tags', $this->Tags());

// Only show published tags
$field->setSearchFilters(['IsPublished' => true]);

// Exclude archived tags
$field->setSearchExcludes(['Status' => 'Archived']);
```

### Custom Search List

Replace the default search list entirely (useful when you need complex queries):

```php
$field = PickerField::create('Tags', 'Tags', $this->Tags());

// Only allow picking from a specific subset
$allowedTags = Tag::get()->filter(['Category' => 'Featured']);
$field->setSearchList($allowedTags);
```

### Drag-and-Drop Sorting

Enable sorting when you have a `many_many_extraFields` sort column:

```php
// DataObject config:
// private static array $many_many_extraFields = [
//     'Tags' => ['Sort' => 'Int'],
// ];

$field = PickerField::create('Tags', 'Tags', $this->Tags(), null, 'Sort');
```

### Full Example

```php
public function getCMSFields()
{
    $fields = parent::getCMSFields();

    // Many-many tag picker with all features
    $tagField = PickerField::create('Tags', 'Tags', $this->Tags(), null, 'Sort');
    $tagField->enableCreate('Add New Tag')
        ->enableEdit()
        ->setSearchFilters(['IsPublished' => true])
        ->setSearchExcludes(['Status' => 'Archived']);
    $fields->addFieldToTab('Root.Tags', $tagField);

    // Has-one author picker
    $authorField = HasOnePickerField::create(
        $this, 'AuthorID', 'Author', $this->Author(), 'Select an Author'
    );
    $fields->addFieldToTab('Root.Main', $authorField);

    return $fields;
}
```

## How It Works

PickerField extends `GridField` with a custom configuration that includes:

| Component | Purpose |
|-----------|---------|
| `PickerFieldAddExistingSearchButton` | Renders the "Select..." button that opens a search popup |
| `PickerFieldAddExistingSearchHandler` | Handles the search popup, filtering, and record selection |
| `PickerFieldDeleteAction` | Unlinks records from the relationship (without deleting them) |
| `PickerFieldEditHandler` | Handles inline create/edit with proper relationship assignment |

When a record is selected or created, the handler automatically writes the relationship:
- **has_one**: Sets the foreign key ID on the parent object
- **many_many**: Adds the record to the join table, including any extra fields

## License

BSD-3-Clause. See [LICENSE](LICENSE) file.

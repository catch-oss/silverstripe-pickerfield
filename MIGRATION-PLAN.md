# Migration Plan: silverstripe-pickerfield

## Summary

- **Package**: thewebmen/silverstripe-pickerfield
- **Type**: B (Silverstripe module)
- **Tier**: 2
- **Risk Level**: Medium
- **Estimated Scope**: 6 PHP files, 6 classes, 1 SS template, 1 YAML config

## Change Inventory

### Namespace Renames Required

| Old Namespace | New Namespace | Files Affected |
|---|---|---|
| `SilverStripe\View\ArrayData` | `SilverStripe\Model\ArrayData` | `PickerFieldAddExistingSearchButton.php` |
| `SilverStripe\ORM\ArrayList` | `SilverStripe\Model\List\ArrayList` | `PickerFieldDeleteAction.php` |

### Composer Dependency Changes

| Package | Current Version | Target Version |
|---|---|---|
| `php` | (not declared) | `^8.5` |
| `silverstripe/framework` | `~5` | `^6.0` |
| `symbiote/silverstripe-gridfieldextensions` | `^4.0` | `^5.0` (SS6 compat) |
| `phpunit/phpunit` (new, require-dev) | - | `^11.0` |
| `silverstripe/recipe-cms` (new, require-dev) | - | `^6.0` |

### API Changes Required

| Pattern | Migration | Files Affected |
|---|---|---|
| Wrong Controller import: `SilverStripe\GraphQL\Controller` used for `Controller::curr()` | Change to `SilverStripe\Control\Controller` | `PickerFieldEditHandler.php` |
| Missing `use` for `ValidationException` in catch block | Add `use SilverStripe\Core\Validation\ValidationException` (SS6 namespace) | `PickerFieldEditHandler.php` |
| Missing `use` for `ManyManyList` | Add `use SilverStripe\ORM\ManyManyList` | `PickerFieldEditHandler.php` |
| Undefined `$controller` in catch block | Fix to use `$this->getToplevelController()` or `Controller::curr()` | `PickerFieldEditHandler.php` |

### PHP 8.5 Compatibility Fixes

| Issue | Fix | Files Affected |
|---|---|---|
| Implicit nullable `SS_List $dataList = null` | `?SS_List $dataList = null` | `PickerField.php` |
| Implicit nullable `$currentHasOne = null` | `?DataObject $currentHasOne = null` | `HasOnePickerField.php` |
| Implicit nullable `$linkExistingTitle = null` | `?string $linkExistingTitle = null` | `HasOnePickerField.php` |
| Implicit nullable `$searchContext = null` | `?SearchContext $searchContext = null` or remove (unused) | `HasOnePickerField.php` |
| Missing return type declarations | Add `: void`, `: static`, `: self`, `: array`, etc. to all methods | All 6 files |
| `array()` syntax | Modernize to `[]` | All files using `array()` |

### PHPUnit Migration

No existing tests — tests must be written from scratch using PHPUnit 11 + SapphireTest.

### Config Changes

| File | Change Required |
|---|---|
| `_config/config.yml` | Currently empty (just `Name: 'pickerfield'`). No changes needed unless extensions are added. |
| `.gitignore` | Add recipe-plugin generated files: `app/`, `public/`, `.htaccess`, `index.php`, `web.config`, `vendor/`, `.phpunit.cache` |

### Structural Changes

| Change | Details |
|---|---|
| Move `code/` → `src/` | PSR-4 standard. Update `composer.json` autoload path. |
| Update autoload namespace | `"TheWebmen\\PickerField\\": "src/"` (already correct, just path changes) |

## Risk Assessment

| Area | Risk | Notes |
|---|---|---|
| Namespace renames | Low | Only 2 SS namespace changes (ArrayData, ArrayList) |
| API changes | Medium | `PickerFieldEditHandler.php` has multiple bugs: wrong Controller import, missing use statements, undefined `$controller` variable. These are pre-existing bugs that need fixing during migration. |
| PHP 8.5 compat | Low | Implicit nullable params, missing return types. Straightforward fixes. |
| Test migration | Medium | No existing tests — need to write tests from scratch to reach 80% coverage. 6 classes with GridField integration require SapphireTest with database. |
| Config changes | Low | Minimal YAML config, no _config.php. |
| Dependency compat | Medium | `symbiote/silverstripe-gridfieldextensions` must have SS6-compatible release. Verify ^5.0 exists. |

## Migration Steps (Ordered)

### Phase 1: composer.json
- [ ] Add `"php": "^8.5"` to require
- [ ] Update `silverstripe/framework` from `~5` to `^6.0`
- [ ] Update `symbiote/silverstripe-gridfieldextensions` from `^4.0` to `^5.0`
- [ ] Add require-dev: `phpunit/phpunit: ^11.0`, `silverstripe/recipe-cms: ^6.0`
- [ ] Add `autoload-dev` with classmap for `app/src/Page.php`, `app/src/PageController.php`
- [ ] Add allow-plugins: `composer/installers`, `silverstripe/vendor-plugin`, `silverstripe/recipe-plugin`
- [ ] Move autoload PSR-4 path from `src/` to `src/` (already correct after code/ rename)
- [ ] Run `composer validate`

### Phase 2: Directory Rename
- [ ] Move `code/` → `src/`
- [ ] Update `composer.json` autoload path if needed

### Phase 3: Namespace Renames
- [ ] `SilverStripe\View\ArrayData` → `SilverStripe\Model\ArrayData` (1 file)
- [ ] `SilverStripe\ORM\ArrayList` → `SilverStripe\Model\List\ArrayList` (1 file)

### Phase 4: API Changes
- [ ] Fix `PickerFieldEditHandler.php`: change `SilverStripe\GraphQL\Controller` to `SilverStripe\Control\Controller`
- [ ] Fix `PickerFieldEditHandler.php`: add `use SilverStripe\Core\Validation\ValidationException`
- [ ] Fix `PickerFieldEditHandler.php`: add `use SilverStripe\ORM\ManyManyList`
- [ ] Fix `PickerFieldEditHandler.php`: replace undefined `$controller` with proper reference

### Phase 5: PHP 8.5 Compatibility
- [ ] Fix implicit nullable parameters in `PickerField.php` and `HasOnePickerField.php`
- [ ] Add return type declarations to all methods across all 6 files
- [ ] Remove unused `$searchContext` parameter in `HasOnePickerField.php` constructor (or type it)
- [ ] Modernize `array()` to `[]` syntax

### Phase 6: Config Updates
- [ ] Update `.gitignore` with vendor/, recipe-plugin generated files, .phpunit.cache
- [ ] No _config.php or YAML config changes needed

### Phase 7: Test Suite (Silverstripe Best Practices)
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev (provides Page/PageController)
- [ ] Add `silverstripe/recipe-plugin: true` to allow-plugins
- [ ] Create `phpunit.xml.dist` with bootstrap `vendor/silverstripe/framework/tests/bootstrap.php`
- [ ] Add recipe-generated files to `.gitignore`: `app/`, `public/`, `.htaccess`, `index.php`, `web.config`
- [ ] Create test classes extending `SapphireTest`:
  - `tests/PickerFieldTest.php` — test GridField construction, component configuration, search filter/exclude/list setters
  - `tests/HasOnePickerFieldTest.php` — test has_one specific behavior, model class resolution
  - `tests/PickerFieldDeleteActionTest.php` — test has_one unlink vs many_many delete behavior
  - `tests/PickerFieldAddExistingSearchHandlerTest.php` — test search filtering, has_one add behavior
  - `tests/PickerFieldEditHandlerTest.php` — test doSave for has_one and many_many, class change detection
- [ ] Use `$usesDatabase = true` for tests that write DataObjects
- [ ] Create YAML fixtures for test DataObjects
- [ ] Achieve 80% line coverage target

## Dependencies

- **Depends on**: None (Tier 2 — no internal catch-oss dependencies)
- **Blocks**: No higher-tier repos depend directly on silverstripe-pickerfield
- **External**: `symbiote/silverstripe-gridfieldextensions` must have SS6-compatible release

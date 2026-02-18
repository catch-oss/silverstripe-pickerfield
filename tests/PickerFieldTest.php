<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\ORM\DataList;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use TheWebmen\PickerField\Controllers\PickerField;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchButton;
use TheWebmen\PickerField\Controllers\PickerFieldDeleteAction;
use TheWebmen\PickerField\Controllers\PickerFieldEditHandler;

class PickerFieldTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testConstructorCreatesGridFieldWithDefaultComponents(): void
    {
        // Given a parent object with a many_many Tags relationship
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');

        // When we create a PickerField for that relationship
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // Then the GridFieldConfig should contain all default components
        $config = $field->getConfig();
        $this->assertNotNull($config->getComponentByType(GridFieldButtonRow::class));
        $this->assertNotNull($config->getComponentByType(GridFieldToolbarHeader::class));
        $this->assertNotNull($config->getComponentByType(GridFieldDataColumns::class));
        $this->assertNotNull($config->getComponentByType(GridFieldTitleHeader::class));
        $this->assertNotNull($config->getComponentByType(GridFieldPaginator::class));
        $this->assertNotNull($config->getComponentByType(PickerFieldAddExistingSearchButton::class));
        $this->assertNotNull($config->getComponentByType(PickerFieldDeleteAction::class));
    }

    public function testConstructorSetsDefaultTitle(): void
    {
        // Given a PickerField created without a custom link title
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we check the search button title
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);

        // Then it should auto-generate a plural title from the data class name
        $this->assertSame('Select TestTagObject(s)', $button->getTitle());
    }

    public function testConstructorUsesCustomTitle(): void
    {
        // Given a PickerField created with a custom link title
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');

        // When we pass a custom title as the 4th argument
        $field = PickerField::create('Tags', 'Tags', $parent->Tags(), 'Choose Tags');

        // Then the search button should use that custom title
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Choose Tags', $button->getTitle());
    }

    public function testIsHaveOneReturnsFalseForPickerField(): void
    {
        // Given a standard PickerField (not HasOnePickerField)
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we check isHaveOne()
        // Then it should return false (this is a many_many picker)
        $this->assertFalse($field->isHaveOne());
    }

    public function testSetAndGetSearchFilters(): void
    {
        // Given a PickerField with no filters set
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we set search filters
        $filters = ['Title:PartialMatch' => 'Tag'];
        $result = $field->setSearchFilters($filters);

        // Then the filters should be retrievable, and the method should return $this for chaining
        $this->assertSame($field, $result, 'setSearchFilters returns $this for chaining');
        $this->assertSame($filters, $field->getSearchFilters());
    }

    public function testSetAndGetSearchExcludes(): void
    {
        // Given a PickerField with no excludes set
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we set search excludes
        $excludes = ['Title' => 'Hidden'];
        $result = $field->setSearchExcludes($excludes);

        // Then the excludes should be retrievable, and the method should return $this for chaining
        $this->assertSame($field, $result, 'setSearchExcludes returns $this for chaining');
        $this->assertSame($excludes, $field->getSearchExcludes());
    }

    public function testSetAndGetSearchList(): void
    {
        // Given a PickerField with no custom search list
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we set a custom search list
        $list = DataList::create(TestTagObject::class);
        $result = $field->setSearchList($list);

        // Then the search list should be retrievable, and the method should return $this for chaining
        $this->assertSame($field, $result, 'setSearchList returns $this for chaining');
        $this->assertNotNull($field->getSearchList());
    }

    public function testSearchFiltersDefaultToNull(): void
    {
        // Given a freshly created PickerField with no configuration
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we check the default filter/exclude/list values
        // Then they should all be null
        $this->assertNull($field->getSearchFilters());
        $this->assertNull($field->getSearchExcludes());
        $this->assertNull($field->getSearchList());
    }

    public function testEnableCreateAddsComponents(): void
    {
        // Given a PickerField without create functionality
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we enable inline record creation
        $result = $field->enableCreate();

        // Then GridFieldAddNewButton and GridFieldDetailForm should be added
        $this->assertSame($field, $result, 'enableCreate returns $this for chaining');
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldAddNewButton::class));
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldDetailForm::class));
    }

    public function testEnableCreateWithCustomTitle(): void
    {
        // Given a PickerField
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we enable create with a custom button title
        $field->enableCreate('Add New Tag');

        // Then the add new button should be present
        $button = $field->getConfig()->getComponentByType(GridFieldAddNewButton::class);
        $this->assertNotNull($button);
    }

    public function testEnableEditAddsComponents(): void
    {
        // Given a PickerField without edit functionality
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we enable inline editing
        $result = $field->enableEdit();

        // Then GridFieldEditButton and GridFieldDetailForm should be added
        $this->assertSame($field, $result, 'enableEdit returns $this for chaining');
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldEditButton::class));
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldDetailForm::class));
    }

    public function testEnableCreateAndEditShareDetailForm(): void
    {
        // Given a PickerField with both create and edit enabled
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we enable both create and edit
        $field->enableCreate();
        $field->enableEdit();

        // Then only one GridFieldDetailForm should exist (shared between create and edit)
        $detailForms = [];
        foreach ($field->getConfig()->getComponents() as $component) {
            if ($component instanceof GridFieldDetailForm) {
                $detailForms[] = $component;
            }
        }
        $this->assertCount(1, $detailForms, 'Only one DetailForm should exist after enabling both create and edit');
    }

    public function testSetSelectTitle(): void
    {
        // Given a PickerField with the default search button title
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we override the select button title
        $result = $field->setSelectTitle('Pick Items');

        // Then the button title should be updated, and the method should return $this
        $this->assertSame($field, $result, 'setSelectTitle returns $this for chaining');
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Pick Items', $button->getTitle());
    }

    public function testConstructorWithSortFieldAddsOrderableRows(): void
    {
        // Given a parent object with a many_many relationship that has extra fields
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');

        // When we create a PickerField with a sortField argument
        $field = PickerField::create('Tags', 'Tags', $parent->Tags(), null, 'Sort');

        // Then GridFieldOrderableRows should be added to enable drag-and-drop sorting
        $this->assertNotNull(
            $field->getConfig()->getComponentByType(GridFieldOrderableRows::class),
            'GridFieldOrderableRows should be added when sortField is provided'
        );
    }

    public function testConstructorWithoutSortFieldHasNoOrderableRows(): void
    {
        // Given a parent object with a many_many relationship
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');

        // When we create a PickerField without a sortField argument
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // Then GridFieldOrderableRows should not be present
        $this->assertNull(
            $field->getConfig()->getComponentByType(GridFieldOrderableRows::class),
            'GridFieldOrderableRows should not be present without sortField'
        );
    }

    public function testDetailFormUsesPickerFieldEditHandler(): void
    {
        // Given a PickerField with inline creation enabled
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->enableCreate();

        // When we inspect the GridFieldDetailForm component
        $detailForm = $field->getConfig()->getComponentByType(GridFieldDetailForm::class);

        // Then it should use PickerFieldEditHandler to handle saves (which assigns relationships)
        $this->assertSame(PickerFieldEditHandler::class, $detailForm->getItemRequestClass());
    }
}

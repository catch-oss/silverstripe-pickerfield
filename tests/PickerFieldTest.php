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
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

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
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Select TestTagObject(s)', $button->getTitle());
    }

    public function testConstructorUsesCustomTitle(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags(), 'Choose Tags');

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Choose Tags', $button->getTitle());
    }

    public function testIsHaveOneReturnsFalseForPickerField(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $this->assertFalse($field->isHaveOne());
    }

    public function testSetAndGetSearchFilters(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $filters = ['Title:PartialMatch' => 'Tag'];
        $result = $field->setSearchFilters($filters);

        $this->assertSame($field, $result, 'setSearchFilters returns $this for chaining');
        $this->assertSame($filters, $field->getSearchFilters());
    }

    public function testSetAndGetSearchExcludes(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $excludes = ['Title' => 'Hidden'];
        $result = $field->setSearchExcludes($excludes);

        $this->assertSame($field, $result, 'setSearchExcludes returns $this for chaining');
        $this->assertSame($excludes, $field->getSearchExcludes());
    }

    public function testSetAndGetSearchList(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $list = DataList::create(TestTagObject::class);
        $result = $field->setSearchList($list);

        $this->assertSame($field, $result, 'setSearchList returns $this for chaining');
        $this->assertNotNull($field->getSearchList());
    }

    public function testSearchFiltersDefaultToNull(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $this->assertNull($field->getSearchFilters());
        $this->assertNull($field->getSearchExcludes());
        $this->assertNull($field->getSearchList());
    }

    public function testEnableCreateAddsComponents(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $result = $field->enableCreate();

        $this->assertSame($field, $result, 'enableCreate returns $this for chaining');
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldAddNewButton::class));
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldDetailForm::class));
    }

    public function testEnableCreateWithCustomTitle(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $field->enableCreate('Add New Tag');

        $button = $field->getConfig()->getComponentByType(GridFieldAddNewButton::class);
        $this->assertNotNull($button);
    }

    public function testEnableEditAddsComponents(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $result = $field->enableEdit();

        $this->assertSame($field, $result, 'enableEdit returns $this for chaining');
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldEditButton::class));
        $this->assertNotNull($field->getConfig()->getComponentByType(GridFieldDetailForm::class));
    }

    public function testEnableCreateAndEditShareDetailForm(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $field->enableCreate();
        $field->enableEdit();

        // Should only have one GridFieldDetailForm component
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
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $result = $field->setSelectTitle('Pick Items');

        $this->assertSame($field, $result, 'setSelectTitle returns $this for chaining');
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Pick Items', $button->getTitle());
    }

    public function testDetailFormUsesPickerFieldEditHandler(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $field->enableCreate();

        $detailForm = $field->getConfig()->getComponentByType(GridFieldDetailForm::class);
        $this->assertSame(PickerFieldEditHandler::class, $detailForm->getItemRequestClass());
    }
}

<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use TheWebmen\PickerField\Controllers\HasOnePickerField;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchButton;

class HasOnePickerFieldTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testIsHaveOneReturnsTrue(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $this->assertTrue($field->isHaveOne());
    }

    public function testConstructorRemovesPaginator(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $this->assertNull($field->getConfig()->getComponentByType(GridFieldPaginator::class));
    }

    public function testConstructorResolvesModelClass(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $this->assertSame(TestRelatedObject::class, $field->getModelClass());
    }

    public function testConstructorSetsChildObject(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $this->assertSame($parent->ID, $field->childObject->ID);
    }

    public function testDefaultTitleUsesSingular(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Select a TestRelatedObject', $button->getTitle());
    }

    public function testCustomTitleOverridesDefault(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related, 'Choose Related Item');

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Choose Related Item', $button->getTitle());
    }

    public function testListContainsCurrentHasOneRecord(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        $list = $field->getList();
        $this->assertCount(1, $list);
        $this->assertSame($related->ID, $list->first()->ID);
    }
}

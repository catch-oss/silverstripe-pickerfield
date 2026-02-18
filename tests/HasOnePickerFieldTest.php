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
        // Given a HasOnePickerField for a has_one relationship
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // When we check isHaveOne()
        // Then it should return true (distinguishing it from the many_many PickerField)
        $this->assertTrue($field->isHaveOne());
    }

    public function testConstructorRemovesPaginator(): void
    {
        // Given a HasOnePickerField (which can only hold one record)
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we create the field
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // Then the paginator should be removed (pagination is meaningless for a single record)
        $this->assertNull($field->getConfig()->getComponentByType(GridFieldPaginator::class));
    }

    public function testConstructorResolvesModelClass(): void
    {
        // Given a parent object with a has_one relationship to TestRelatedObject
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we create a HasOnePickerField using the 'RelatedID' field name
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // Then the model class should be resolved from the relationship definition
        $this->assertSame(TestRelatedObject::class, $field->getModelClass());
    }

    public function testConstructorSetsChildObject(): void
    {
        // Given a parent DataObject that owns the has_one relationship
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we create a HasOnePickerField
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // Then the childObject should reference the parent (used for writing the FK on save)
        $this->assertSame($parent->ID, $field->childObject->ID);
    }

    public function testDefaultTitleUsesSingular(): void
    {
        // Given a HasOnePickerField created without a custom title
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // When we check the search button title
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);

        // Then it should use "Select a ..." (singular) instead of "Select ...(s)" (plural)
        $this->assertSame('Select a TestRelatedObject', $button->getTitle());
    }

    public function testCustomTitleOverridesDefault(): void
    {
        // Given a HasOnePickerField created with a custom link title
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we pass a custom title as the 5th argument
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related, 'Choose Related Item');

        // Then the search button should use that custom title
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $this->assertSame('Choose Related Item', $button->getTitle());
    }

    public function testConstructorHandlesNullCurrentHasOne(): void
    {
        // Given a parent object with no existing has_one record set
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');

        // When we create a HasOnePickerField with null for currentHasOne
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', null);

        // Then the field should be created successfully with an empty list
        $this->assertCount(0, $field->getList());
        $this->assertSame(TestRelatedObject::class, $field->getModelClass());
    }

    public function testListContainsCurrentHasOneRecord(): void
    {
        // Given a parent with an existing has_one relationship to related1
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we create a HasOnePickerField with the current related object
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // Then the grid's data list should contain exactly that one record
        $list = $field->getList();
        $this->assertCount(1, $list);
        $this->assertSame($related->ID, $list->first()->ID);
    }
}

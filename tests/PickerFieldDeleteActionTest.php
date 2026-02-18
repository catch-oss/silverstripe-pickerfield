<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\SapphireTest;
use TheWebmen\PickerField\Controllers\HasOnePickerField;
use TheWebmen\PickerField\Controllers\PickerField;
use TheWebmen\PickerField\Controllers\PickerFieldDeleteAction;

class PickerFieldDeleteActionTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testHandleActionUnlinksHasOneRelationship(): void
    {
        // Given a parent with a has_one relationship to related1
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');
        $this->assertSame($related->ID, $parent->RelatedID);

        // When we trigger the unlink action on the has_one picker field
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
        $action = new PickerFieldDeleteAction();
        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        // Then the parent's foreign key should be set to 0 (unlinked)
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertSame(0, $parent->RelatedID, 'Has one ID should be set to 0 after delete action');
    }

    public function testHandleActionSetsEmptyListForHasOne(): void
    {
        // Given a has_one picker field displaying the current related record
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);

        // When we unlink the record
        $action = new PickerFieldDeleteAction();
        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        // Then the grid field's list should be empty (no record selected)
        $this->assertCount(0, $field->getList());
    }

    public function testDeleteActionDoesNotDeleteHasOneRecord(): void
    {
        // Given a has_one relationship between parent and related
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we unlink the record via the delete action
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
        $action = new PickerFieldDeleteAction();
        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        // Then the related object should still exist in the database (unlink, not delete)
        $this->assertNotNull(
            TestRelatedObject::get()->byID($related->ID),
            'Related object should not be deleted, only unlinked'
        );
    }

    public function testHandleActionUnlinksManyManyRelationship(): void
    {
        // Given parent1 with two tags in a many_many relationship
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');
        $this->assertCount(2, $parent->Tags());

        // When we unlink tag1 via the delete action
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $action = new PickerFieldDeleteAction();
        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $tag1->ID], []);

        // Then only one tag should remain in the relationship, and the tag record itself should still exist
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertCount(1, $parent->Tags());
        $this->assertNotNull(
            TestTagObject::get()->byID($tag1->ID),
            'Tag object should not be deleted, only unlinked from many_many'
        );
    }
}

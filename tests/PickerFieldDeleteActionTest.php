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
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // Verify initial relationship
        $this->assertSame($related->ID, $parent->RelatedID);

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
        $action = new PickerFieldDeleteAction();

        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        // Reload parent from DB
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertSame(0, $parent->RelatedID, 'Has one ID should be set to 0 after delete action');
    }

    public function testHandleActionSetsEmptyListForHasOne(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
        $action = new PickerFieldDeleteAction();

        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        $this->assertCount(0, $field->getList());
    }

    public function testDeleteActionDoesNotDeleteHasOneRecord(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
        $action = new PickerFieldDeleteAction();

        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $related->ID], []);

        // The related object should still exist in the database
        $this->assertNotNull(
            TestRelatedObject::get()->byID($related->ID),
            'Related object should not be deleted, only unlinked'
        );
    }

    public function testHandleActionUnlinksManyManyRelationship(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');

        // Verify initial state
        $this->assertCount(2, $parent->Tags());

        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $action = new PickerFieldDeleteAction();

        $action->handleAction($field, 'unlinkrelation', ['RecordID' => $tag1->ID], []);

        // Reload and check
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertCount(1, $parent->Tags());

        // Tag should still exist in DB (only unlinked from relationship, not deleted)
        $this->assertNotNull(
            TestTagObject::get()->byID($tag1->ID),
            'Tag object should not be deleted, only unlinked from many_many'
        );
    }
}

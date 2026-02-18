<?php

namespace TheWebmen\PickerField\Tests;

use ReflectionMethod;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\PaginatedList;
use TheWebmen\PickerField\Controllers\HasOnePickerField;
use TheWebmen\PickerField\Controllers\PickerField;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchButton;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchHandler;

class PickerFieldAddExistingSearchHandlerTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testGetSearchListReturnsCustomListWhenSet(): void
    {
        // Given a PickerField with a custom search list filtered to only "Tag One"
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $customList = TestTagObject::get()->filter(['Title' => 'Tag One']);
        $field->setSearchList($customList);

        // When we call getSearchList() on the handler (protected, via reflection)
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $method = new ReflectionMethod($handler, 'getSearchList');
        $searchList = $method->invoke($handler);

        // Then only the filtered records should be returned
        $this->assertCount(1, $searchList);
    }

    public function testGetSearchListDefaultsToAllRecordsOfDataClass(): void
    {
        // Given a PickerField with no custom search list set
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we call getSearchList() on the handler
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $method = new ReflectionMethod($handler, 'getSearchList');
        $searchList = $method->invoke($handler);

        // Then it should default to all records of the list's data class
        $this->assertSame(TestTagObject::get()->count(), $searchList->count());
    }

    public function testApplySearchFiltersFiltersResults(): void
    {
        // Given a PickerField with a partial-match search filter for "One"
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchFilters(['Title:PartialMatch' => 'One']);

        // When we apply search filters to the full tag list
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        // Then only "Tag One" should remain
        $this->assertCount(1, $filtered);
        $this->assertSame('Tag One', $filtered->first()->Title);
    }

    public function testApplySearchExcludesExcludesResults(): void
    {
        // Given a PickerField with a search exclude for "Tag Three"
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchExcludes(['Title' => 'Tag Three']);

        // When we apply search filters (which includes excludes) to the full tag list
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        // Then "Tag Three" should be excluded, leaving 2 results
        $this->assertCount(2, $filtered);
        $titles = $filtered->column('Title');
        $this->assertNotContains('Tag Three', $titles);
    }

    public function testApplySearchFiltersReturnsUnfilteredWhenNoFiltersSet(): void
    {
        // Given a PickerField with no filters or excludes configured
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we apply search filters to the full tag list
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        // Then all records should be returned unchanged
        $this->assertSame($list->count(), $filtered->count());
    }

    public function testAddForHasOneWritesRelationship(): void
    {
        // Given parent2 with an existing has_one to related2, and a separate related1 record
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // When we add related1 via the search handler's add() method (simulating a POST from the popup)
        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $parent->Related());
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $request = new HTTPRequest('POST', 'add', [], ['id' => $related->ID]);
        $handler->add($request);

        // Then the parent's RelatedID should now point to related1
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertSame($related->ID, $parent->RelatedID);
    }

    public function testItemsReturnsPaginatedList(): void
    {
        // Given a PickerField with a many_many list
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // When we call Items() on the handler
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $items = $handler->Items();

        // Then the result should be a PaginatedList (ready for display in the search popup)
        $this->assertInstanceOf(PaginatedList::class, $items);
    }

    public function testItemsExcludesAlreadyLinkedRecords(): void
    {
        // Given parent1 which already has tag1 and tag2 linked via many_many
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');
        $tag2 = $this->objFromFixture(TestTagObject::class, 'tag2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');

        // When we call Items() to get available records for the search popup
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $items = $handler->Items();
        $itemIds = $items->column('ID');

        // Then already-linked records (tag1, tag2) should be excluded, leaving only tag3
        $this->assertNotContains($tag1->ID, $itemIds);
        $this->assertNotContains($tag2->ID, $itemIds);
        $this->assertContains($tag3->ID, $itemIds);
    }

    public function testItemsAppliesSearchFilters(): void
    {
        // Given parent1 (has tag1 and tag2) with a search filter for "Tag Three" only
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchFilters(['Title' => 'Tag Three']);

        // When we call Items()
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $items = $handler->Items();

        // Then only Tag Three should appear (tag1/tag2 excluded by subtract, filter narrows to Tag Three)
        $this->assertCount(1, $items);
    }

    public function testAddForManyManyAddsRecord(): void
    {
        // Given parent2 with no tags linked
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');
        $this->assertCount(0, $parent->Tags());

        // When we add tag3 via the search handler (simulating the parent's add() which delegates to parent class)
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);
        $request = new HTTPRequest('POST', 'add', [], ['id' => $tag3->ID]);
        $handler->add($request);

        // Then the tag should be linked to the parent via many_many
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertCount(1, $parent->Tags());
        $this->assertSame($tag3->ID, $parent->Tags()->first()->ID);
    }
}

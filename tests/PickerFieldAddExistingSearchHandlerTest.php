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
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $customList = TestTagObject::get()->filter(['Title' => 'Tag One']);
        $field->setSearchList($customList);

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $method = new ReflectionMethod($handler, 'getSearchList');
        $searchList = $method->invoke($handler);
        $this->assertCount(1, $searchList);
    }

    public function testGetSearchListDefaultsToAllRecordsOfDataClass(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $method = new ReflectionMethod($handler, 'getSearchList');
        $searchList = $method->invoke($handler);
        // Should return all TestTagObject records
        $this->assertSame(TestTagObject::get()->count(), $searchList->count());
    }

    public function testApplySearchFiltersFiltersResults(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchFilters(['Title:PartialMatch' => 'One']);

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        $this->assertCount(1, $filtered);
        $this->assertSame('Tag One', $filtered->first()->Title);
    }

    public function testApplySearchExcludesExcludesResults(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchExcludes(['Title' => 'Tag Three']);

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        $this->assertCount(2, $filtered);
        $titles = $filtered->column('Title');
        $this->assertNotContains('Tag Three', $titles);
    }

    public function testApplySearchFiltersReturnsUnfilteredWhenNoFiltersSet(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $list = TestTagObject::get();
        $filtered = $handler->applySearchFilters($list);

        $this->assertSame($list->count(), $filtered->count());
    }

    public function testAddForHasOneWritesRelationship(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        $field = HasOnePickerField::create($parent, 'RelatedID', 'Related', $parent->Related());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $request = new HTTPRequest('POST', 'add', [], ['id' => $related->ID]);

        $handler->add($request);

        // Reload parent and verify relationship was set
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertSame($related->ID, $parent->RelatedID);
    }

    public function testItemsReturnsPaginatedList(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $items = $handler->Items();

        $this->assertInstanceOf(PaginatedList::class, $items);
    }

    public function testItemsExcludesAlreadyLinkedRecords(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $items = $handler->Items();

        // parent1 has tag1 and tag2, so Items should only show tag3
        $itemIds = $items->column('ID');
        $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');
        $tag2 = $this->objFromFixture(TestTagObject::class, 'tag2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');

        $this->assertNotContains($tag1->ID, $itemIds);
        $this->assertNotContains($tag2->ID, $itemIds);
        $this->assertContains($tag3->ID, $itemIds);
    }

    public function testItemsAppliesSearchFilters(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $field->setSearchFilters(['Title' => 'Tag Three']);

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $items = $handler->Items();

        // Only Tag Three should appear (already excluded: tag1, tag2)
        $this->assertCount(1, $items);
    }

    public function testAddForManyManyAddsRecord(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');

        // parent2 has no tags initially
        $this->assertCount(0, $parent->Tags());

        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $handler = new PickerFieldAddExistingSearchHandler($field, $button);

        $request = new HTTPRequest('POST', 'add', [], ['id' => $tag3->ID]);

        $handler->add($request);

        // Reload parent and verify tag was added
        $parent = TestDataObject::get()->byID($parent->ID);
        $this->assertCount(1, $parent->Tags());
        $this->assertSame($tag3->ID, $parent->Tags()->first()->ID);
    }

}

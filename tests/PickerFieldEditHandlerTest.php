<?php

namespace TheWebmen\PickerField\Tests;

use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use TheWebmen\PickerField\Controllers\PickerFieldEditHandler;

class PickerFieldEditHandlerTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testGetExtraSavedDataReturnsNullForNonManyMany(): void
    {
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);

        $record = $this->objFromFixture(TestDataObject::class, 'parent1');
        $list = DataList::create(TestRelatedObject::class);

        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $list);

        $this->assertNull($result);
    }

    public function testGetExtraSavedDataReturnsManyManyExtraFields(): void
    {
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);

        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $manyManyList = $parent->Tags();

        $record = $this->objFromFixture(TestTagObject::class, 'tag1');

        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $manyManyList);

        // ManyManyList without extra fields should return empty array
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetExtraSavedDataReturnsNullForArrayList(): void
    {
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);

        $record = $this->objFromFixture(TestDataObject::class, 'parent1');
        $list = ArrayList::create();

        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $list);

        $this->assertNull($result);
    }
}

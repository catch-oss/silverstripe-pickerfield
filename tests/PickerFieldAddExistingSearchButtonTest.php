<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataList;
use TheWebmen\PickerField\Controllers\PickerField;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchButton;
use TheWebmen\PickerField\Controllers\PickerFieldAddExistingSearchHandler;

class PickerFieldAddExistingSearchButtonTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestRelatedObject::class,
        TestTagObject::class,
    ];

    public function testSetAndGetSearchFilters(): void
    {
        $button = new PickerFieldAddExistingSearchButton();

        $this->assertNull($button->getSearchFilters());

        $filters = ['Title:PartialMatch' => 'Test'];
        $button->setSearchFilters($filters);

        $this->assertSame($filters, $button->getSearchFilters());
    }

    public function testSetAndGetSearchExcludes(): void
    {
        $button = new PickerFieldAddExistingSearchButton();

        $this->assertNull($button->getSearchExcludes());

        $excludes = ['Title' => 'Hidden'];
        $button->setSearchExcludes($excludes);

        $this->assertSame($excludes, $button->getSearchExcludes());
    }

    public function testSetAndGetSearchList(): void
    {
        $button = new PickerFieldAddExistingSearchButton();

        $this->assertNull($button->getSearchList());

        $list = DataList::create(TestTagObject::class);
        $result = $button->setSearchList($list);

        $this->assertNotNull($button->getSearchList());
        $this->assertSame($button, $result, 'setSearchList returns $this for chaining');
    }

    public function testHandleSearchReturnsPickerFieldHandler(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
        $result = $button->handleSearch($field, null);

        $this->assertInstanceOf(PickerFieldAddExistingSearchHandler::class, $result);
    }

    public function testGetHTMLFragmentsReturnsArray(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        // GridField needs a form to call Link()
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $form = Form::create($controller, 'TestForm', FieldList::create($field), FieldList::create());

            $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
            $fragments = $button->getHTMLFragments($field);

            $this->assertIsArray($fragments);
            $this->assertArrayHasKey('buttons-before-left', $fragments);
        } finally {
            $controller->popCurrent();
        }
    }
}

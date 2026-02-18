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
        // Given a search button with no filters configured
        $button = new PickerFieldAddExistingSearchButton();
        $this->assertNull($button->getSearchFilters());

        // When we set search filters
        $filters = ['Title:PartialMatch' => 'Test'];
        $button->setSearchFilters($filters);

        // Then the filters should be retrievable
        $this->assertSame($filters, $button->getSearchFilters());
    }

    public function testSetAndGetSearchExcludes(): void
    {
        // Given a search button with no excludes configured
        $button = new PickerFieldAddExistingSearchButton();
        $this->assertNull($button->getSearchExcludes());

        // When we set search excludes
        $excludes = ['Title' => 'Hidden'];
        $button->setSearchExcludes($excludes);

        // Then the excludes should be retrievable
        $this->assertSame($excludes, $button->getSearchExcludes());
    }

    public function testSetAndGetSearchList(): void
    {
        // Given a search button with no custom search list
        $button = new PickerFieldAddExistingSearchButton();
        $this->assertNull($button->getSearchList());

        // When we set a custom search list
        $list = DataList::create(TestTagObject::class);
        $result = $button->setSearchList($list);

        // Then the search list should be retrievable, and the method should return $this for chaining
        $this->assertNotNull($button->getSearchList());
        $this->assertSame($button, $result, 'setSearchList returns $this for chaining');
    }

    public function testHandleSearchReturnsPickerFieldHandler(): void
    {
        // Given a PickerField with its search button component
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());
        $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);

        // When the search button handles a search request
        $result = $button->handleSearch($field, null);

        // Then it should return our custom PickerFieldAddExistingSearchHandler (not the parent's)
        $this->assertInstanceOf(PickerFieldAddExistingSearchHandler::class, $result);
    }

    public function testGetHTMLFragmentsReturnsArray(): void
    {
        // Given a PickerField inside a Form (required for Link() generation)
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $field = PickerField::create('Tags', 'Tags', $parent->Tags());

        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $form = Form::create($controller, 'TestForm', FieldList::create($field), FieldList::create());

            // When we render the search button's HTML fragments
            $button = $field->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);
            $fragments = $button->getHTMLFragments($field);

            // Then it should return an array with the button rendered in the 'buttons-before-left' fragment
            $this->assertIsArray($fragments);
            $this->assertArrayHasKey('buttons-before-left', $fragments);
        } finally {
            $controller->popCurrent();
        }
    }
}

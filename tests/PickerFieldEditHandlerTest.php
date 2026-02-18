<?php

namespace TheWebmen\PickerField\Tests;

use ReflectionMethod;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use TheWebmen\PickerField\Controllers\HasOnePickerField;
use TheWebmen\PickerField\Controllers\PickerField;
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
        // Given a handler and a plain DataList (not a ManyManyList)
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);
        $record = $this->objFromFixture(TestDataObject::class, 'parent1');
        $list = DataList::create(TestRelatedObject::class);

        // When we call getExtraSavedData (protected, via reflection)
        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $list);

        // Then it should return null (extra fields only apply to ManyManyList)
        $this->assertNull($result);
    }

    public function testGetExtraSavedDataReturnsManyManyExtraFields(): void
    {
        // Given a ManyManyList with extra fields (Sort) and a record without extra data saved
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $manyManyList = $parent->Tags();
        $record = $this->objFromFixture(TestTagObject::class, 'tag1');

        // When we call getExtraSavedData
        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $manyManyList);

        // Then it should return an array (not null), but empty since no extra data was saved into the record
        $this->assertIsArray($result);
    }

    public function testGetExtraSavedDataReturnsExtraFieldValues(): void
    {
        // Given a ManyManyList with extra fields, and a record with ManyMany[Sort] data saved by a form
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);
        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $manyManyList = $parent->Tags();
        $record = $this->objFromFixture(TestTagObject::class, 'tag1');
        $record->setField('ManyMany[Sort]', 5);

        // When we call getExtraSavedData
        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $manyManyList);

        // Then it should return the extra field values extracted from the record
        $this->assertIsArray($result);
        $this->assertArrayHasKey('Sort', $result);
        $this->assertSame(5, $result['Sort']);
    }

    public function testGetExtraSavedDataReturnsNullForArrayList(): void
    {
        // Given a handler and an ArrayList (not a ManyManyList)
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);
        $record = $this->objFromFixture(TestDataObject::class, 'parent1');
        $list = ArrayList::create();

        // When we call getExtraSavedData
        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $list);

        // Then it should return null
        $this->assertNull($result);
    }

    public function testDoSaveManyManyAddsRecordToList(): void
    {
        // Given parent2 with no tags, and an existing tag3 record
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');
        $this->assertCount(0, $parent->Tags());

        // When we call doSave() on a PickerFieldEditHandler wired to the many_many list
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();
            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            // Mock edit() to avoid template rendering
            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $tag3, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();
            $handler->method('edit')->willReturn('ok');

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(TextField::create('Title', 'Title')),
                FieldList::create()
            );
            $handler->doSave(['Title' => $tag3->Title], $saveForm);

            // Then the tag should be added to the parent's many_many list
            $parent = TestDataObject::get()->byID($parent->ID);
            $this->assertCount(1, $parent->Tags());
            $this->assertSame($tag3->ID, $parent->Tags()->first()->ID);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveHasOneWritesRelationship(): void
    {
        // Given parent2 with related2, and a separate related1 record
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');
        $this->assertNotSame($related->ID, $parent->RelatedID);

        // When we call doSave() on a handler wired to the has_one picker field
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $gridField = HasOnePickerField::create($parent, 'RelatedID', 'Related', $related);
            $gridField->enableCreate();
            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            // Mock edit() to avoid template rendering
            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $related, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();
            $handler->method('edit')->willReturn('ok');

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(TextField::create('Title', 'Title')),
                FieldList::create()
            );
            $handler->doSave(['Title' => $related->Title], $saveForm);

            // Then the parent's has_one foreign key should be updated to related1's ID
            $parent = TestDataObject::get()->byID($parent->ID);
            $this->assertSame($related->ID, $parent->RelatedID);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveReturnsHttpErrorWhenCannotEdit(): void
    {
        // Given a mock record that returns false for canEdit()
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();
            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            $record = $this->createMock(TestTagObject::class);
            $record->method('canEdit')->willReturn(false);

            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $record, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();

            $saveForm = Form::create($controller, 'ItemEditForm', FieldList::create(), FieldList::create());
            $saveForm->setController($controller);

            // When we call doSave()
            // Then it should throw a 403 HTTP exception
            $this->expectException(\SilverStripe\Control\HTTPResponse_Exception::class);
            $handler->doSave([], $saveForm);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveHandlesValidationException(): void
    {
        // Given a mock record that throws a ValidationException on write()
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();
            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            $validationResult = ValidationResult::create();
            $validationResult->addError('Test validation failure');

            $record = $this->createMock(TestTagObject::class);
            $record->method('canEdit')->willReturn(true);
            $record->method('write')->willThrowException(new ValidationException($validationResult));

            // Mock both edit() and getToplevelController() to control the response flow
            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $record, $controller, 'TestForm'])
                ->onlyMethods(['edit', 'getToplevelController'])
                ->getMock();
            $handler->method('getToplevelController')->willReturn($controller);

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(TextField::create('Title', 'Title')),
                FieldList::create()
            );

            // When we call doSave() with a record that fails validation
            $result = $handler->doSave(['Title' => 'Test'], $saveForm);

            // Then the catch block should handle it and return an HTTPResponse via PjaxResponseNegotiator
            $this->assertInstanceOf(HTTPResponse::class, $result);
        } finally {
            $controller->popCurrent();
        }
    }
}

<?php

namespace TheWebmen\PickerField\Tests;

use ReflectionMethod;
use ReflectionProperty;
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

        // ManyManyList returns an array (not null), but empty when no extra data was saved into record
        $this->assertIsArray($result);
    }

    public function testGetExtraSavedDataReturnsExtraFieldValues(): void
    {
        $handler = $this->createPartialMock(PickerFieldEditHandler::class, []);

        $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
        $manyManyList = $parent->Tags();

        $record = $this->objFromFixture(TestTagObject::class, 'tag1');
        // Simulate what Form::saveInto() does for many_many extra fields
        $record->setField('ManyMany[Sort]', 5);

        $reflection = new ReflectionMethod($handler, 'getExtraSavedData');
        $result = $reflection->invoke($handler, $record, $manyManyList);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Sort', $result);
        $this->assertSame(5, $result['Sort']);
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

    public function testDoSaveManyManyAddsRecordToList(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $tag3 = $this->objFromFixture(TestTagObject::class, 'tag3');

        // parent2 has no tags initially
        $this->assertCount(0, $parent->Tags());

        // Set up a controller context (required for Controller::curr() in doSave)
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            // Build the GridField with the many_many list
            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();

            // Associate the GridField with a form so Link() works
            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());

            // Get the detail form component
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            // Create the handler — mock edit() to avoid template rendering
            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $tag3, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();

            $handler->method('edit')->willReturn('ok');

            // Build a form for doSave
            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(
                    TextField::create('Title', 'Title')
                ),
                FieldList::create()
            );

            $data = ['Title' => $tag3->Title];

            $handler->doSave($data, $saveForm);

            // Verify the tag was added to the many_many list
            $parent = TestDataObject::get()->byID($parent->ID);
            $this->assertCount(1, $parent->Tags());
            $this->assertSame($tag3->ID, $parent->Tags()->first()->ID);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveHasOneWritesRelationship(): void
    {
        $parent = $this->objFromFixture(TestDataObject::class, 'parent2');
        $related = $this->objFromFixture(TestRelatedObject::class, 'related1');

        // parent2 already has related2, verify initial state
        $this->assertNotSame($related->ID, $parent->RelatedID);

        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $gridField = new \TheWebmen\PickerField\Controllers\HasOnePickerField(
                $parent, 'RelatedID', 'Related', $related
            );
            $gridField->enableCreate();

            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());

            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $related, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();

            $handler->method('edit')->willReturn('ok');

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(
                    TextField::create('Title', 'Title')
                ),
                FieldList::create()
            );

            $data = ['Title' => $related->Title];

            $handler->doSave($data, $saveForm);

            // Verify the has_one was written
            $parent = TestDataObject::get()->byID($parent->ID);
            $this->assertSame($related->ID, $parent->RelatedID);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveReturnsHttpErrorWhenCannotEdit(): void
    {
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
            $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');

            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();

            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());

            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            // Create a mock record that cannot be edited
            $record = $this->createMock(TestTagObject::class);
            $record->method('canEdit')->willReturn(false);

            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $record, $controller, 'TestForm'])
                ->onlyMethods(['edit'])
                ->getMock();

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(),
                FieldList::create()
            );
            $saveForm->setController($controller);

            $this->expectException(\SilverStripe\Control\HTTPResponse_Exception::class);
            $handler->doSave([], $saveForm);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testDoSaveHandlesValidationException(): void
    {
        $controller = Controller::create();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();

        try {
            $parent = $this->objFromFixture(TestDataObject::class, 'parent1');
            $tag1 = $this->objFromFixture(TestTagObject::class, 'tag1');

            $gridField = PickerField::create('Tags', 'Tags', $parent->Tags());
            $gridField->enableCreate();

            $form = Form::create($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
            $detailForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);

            // Create a mock record that throws ValidationException on write()
            $validationResult = ValidationResult::create();
            $validationResult->addError('Test validation failure');

            $record = $this->createMock(TestTagObject::class);
            $record->method('canEdit')->willReturn(true);
            $record->method('write')->willThrowException(new ValidationException($validationResult));

            $handler = $this->getMockBuilder(PickerFieldEditHandler::class)
                ->setConstructorArgs([$gridField, $detailForm, $record, $controller, 'TestForm'])
                ->onlyMethods(['edit', 'getToplevelController'])
                ->getMock();

            $handler->method('getToplevelController')->willReturn($controller);

            $saveForm = Form::create(
                $controller,
                'ItemEditForm',
                FieldList::create(
                    TextField::create('Title', 'Title')
                ),
                FieldList::create()
            );

            $result = $handler->doSave(['Title' => 'Test'], $saveForm);

            // The catch block returns a response from PjaxResponseNegotiator
            $this->assertInstanceOf(HTTPResponse::class, $result);
        } finally {
            $controller->popCurrent();
        }
    }

}

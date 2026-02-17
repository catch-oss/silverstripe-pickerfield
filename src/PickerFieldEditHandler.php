<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ManyManyList;

/**
 * A custom grid field request handler that allows interacting with form fields when adding records.
 */
class PickerFieldEditHandler extends GridFieldDetailForm_ItemRequest
{
	public function doSave($data, $form)
	{
	    if (!$this->record->canEdit()) {
	        return $form->controller->httpError(403);
	    }

	    if (isset($data['ClassName']) && $data['ClassName'] != $this->record->ClassName) {
	        $newClassName = $data['ClassName'];
	        $this->record->setClassName($this->record->ClassName);
	        $this->record = $this->record->newClassInstance($newClassName);
	    }

	    try {
	        $form->saveInto($this->record);
	        $this->record->write();
	    } catch (ValidationException $e) {
	        $controller = $this->getToplevelController();
	        $form->sessionMessage($e->getResult()->message(), 'bad');
	        $responseNegotiator = new PjaxResponseNegotiator([
	            'CurrentForm' => function () use (&$form) {
	                return $form->forTemplate();
	            },
	            'default' => function () use (&$controller) {
	                return $controller->redirectBack();
	            }
	        ]);
	        if ($controller->getRequest()->isAjax()) {
	            $controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
	        }
	        return $responseNegotiator->respond($controller->getRequest());
	    }

	    // object has been created; assign the relationship
	    if ($this->gridField->isHaveOne()) {
    	    $childProperty = $this->gridField->getName();
    	    $this->gridField->childObject->$childProperty = $this->record->ID;
    	    $this->gridField->childObject->write();
	    } else {
	        $list = $this->gridField->getList();
	        $extraData = $this->getExtraSavedData($this->record, $list);
	        $list->add($this->record, $extraData);
	    }

	    return $this->edit(Controller::curr()->getRequest());
	}

	/**
	 * Get the list of extra data from the $record as saved into it by
	 * {@see Form::saveInto()}
	 *
	 * Handles detection of falsey values explicitly saved into the
	 * DataObject by formfields
	 *
	 * @param DataObject $record
	 * @param SS_List $list
	 * @return array|null List of data to write to the relation
	 */
	protected function getExtraSavedData($record, $list): ?array
	{
		// Skip extra data if not ManyManyList
		if (!($list instanceof ManyManyList)) {
			return null;
		}

		$data = [];
		foreach ($list->getExtraFields() as $field => $dbSpec) {
			$savedField = "ManyMany[{$field}]";
			if ($record->hasField($savedField)) {
				$data[$field] = $record->getField($savedField);
			}
		}
		return $data;
	}
}

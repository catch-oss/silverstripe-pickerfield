<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\ORM\DataObject;

class HasOnePickerField extends PickerField
{
	protected bool $isHaveOne = true;
	public DataObject $childObject;

	/**
	 * Usage [e.g. in getCMSFields]
	 *    $field = new HasOnePickerField($this, 'DamID', 'Selected Dam', $this->Dam(), 'Select a Dam');
	 *
	 * @param DataObject $childObject   - The DataObject we are manipulating with this field: typically $this via getCMSFields
	 * @param string $name              - Field Name of has_one relationship (e.g. DamID, SireID, etc.): ensure 'ID' suffix
	 * @param string $title             - GridField Title
	 * @param DataObject|null $currentHasOne - Result of the current has_one relationship method (E.g. $this->HasOneMethod())
	 * @param string|null $linkExistingTitle - AddExisting Button Title
	 */
	public function __construct(DataObject $childObject, string $name, ?string $title = null, ?DataObject $currentHasOne = null, ?string $linkExistingTitle = null)
	{
		$modelClass = $childObject->getRelationClass(str_replace('ID', '', $name));
		if (!$modelClass) {
			$modelClass = $currentHasOne->className;
		}

		$this->setModelClass($modelClass);
		$this->childObject = $childObject;

		// convert the has_one relation getter to a DataList / SS_List
		$dataList = $modelClass::get()->filter(['ID' => $currentHasOne->ID]);

		// construct the PickerField
		parent::__construct($name, $title, $dataList, $linkExistingTitle);

		// remove components non-applicable to has_one relationships
		$this->getConfig()->removeComponentsByType(GridFieldPaginator::class);
	}
}

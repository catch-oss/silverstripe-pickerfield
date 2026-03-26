<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchHandler;

class PickerFieldAddExistingSearchHandler extends GridFieldAddExistingSearchHandler
{
	private static array $allowed_actions = [
		'index',
		'add',
		'SearchForm',
	];

	public function SearchForm()
	{
		$form = parent::SearchForm();

		// The general search field 'q' is scaffolded as a HiddenField by DataObject.
		// Replace it with a visible TextField so users can search by title etc.
		$modelClass = $this->grid->getModelClass();
		$generalFieldName = DataObject::singleton($modelClass)->getGeneralSearchFieldName();
		if ($generalFieldName && $form->Fields()->dataFieldByName($generalFieldName)) {
			$form->Fields()->replaceField(
				$generalFieldName,
				TextField::create($generalFieldName, _t('GridFieldExtensions.SEARCH', 'Search'))
			);
		}

		return $form;
	}

	public function add($request)
	{
		// use native GridFieldAddExistingSearchHandler add() method when not has_one
		if (!$this->grid->isHaveOne()) {
			return parent::add($request);
		}

		if (!$id = $request->postVar('id')) {
			$this->httpError(400);
		}

		// appropriate handling of has_one relationships
		$childProperty = $this->grid->getName();
		$this->grid->childObject->$childProperty = $id;
		$this->grid->childObject->write();
	}

	public function doSearch($data, $form)
	{
		// Strip empty values to prevent filters (e.g. WithinRangeFilter for
		// LastEdited) from applying impossible constraints with default bounds.
		$data = array_filter($data, fn($v) => $v !== '' && $v !== null);

		$list = $this->context->getQuery($data, false, null, $this->getSearchList());
		$list = $this->applySearchFilters($list);
		$list = $list->subtract($this->grid->getList());
		$list = new PaginatedList($list, $this->request);

		$data = $this->customise([
			'SearchForm' => $form,
			'Items'      => $list,
		]);
		return $data->index();
	}

	public function Items()
	{
		$list = $this->getSearchList();
		$list = $this->applySearchFilters($list);
		$list = $list->subtract($this->grid->getList());
		$list = new PaginatedList($list, $this->request);

		return $list;
	}

	protected function getSearchList()
	{
		$component = $this->grid->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);

		return $component->getSearchList() ?: DataList::create($this->grid->getList()->dataClass());
	}

	public function applySearchFilters(SS_List $list): SS_List
	{
		$component = $this->grid->getConfig()->getComponentByType(PickerFieldAddExistingSearchButton::class);

		if ($filters = $component->getSearchFilters()) {
			$list = $list->filter($filters);
		}
		if ($excludes = $component->getSearchExcludes()) {
			$list = $list->exclude($excludes);
		}

		return $list;
	}
}

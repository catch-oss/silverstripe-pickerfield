<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataList;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchHandler;

class PickerFieldAddExistingSearchHandler extends GridFieldAddExistingSearchHandler
{
	private static array $allowed_actions = [
		'index',
		'add',
		'SearchForm',
	];

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

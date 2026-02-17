<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchHandler;

class PickerFieldAddExistingSearchHandler extends GridFieldAddExistingSearchHandler
{
	private static array $allowed_actions = [
		'index',
		'add',
		'SearchForm',
	];

	public function add($request): void
	{
		// use native GridFieldAddExistingSearchHandler add() method when not has_one
		if (!$this->grid->isHaveOne()) {
			parent::add($request);
			return;
		}

		if (!$id = $request->postVar('id')) {
			$this->httpError(400);
		}

		// appropriate handling of has_one relationships
		$childProperty = $this->grid->getName();
		$this->grid->childObject->$childProperty = $id;
		$this->grid->childObject->write();
	}

	public function doSearch($data, $form): mixed
	{
		$list = $this->context->getQuery($data, false, false, $this->getSearchList());
		$list = $this->applySearchFilters($list);
		$list = $list->subtract($this->grid->getList());
		$list = new PaginatedList($list, $this->request);

		$data = $this->customise([
			'SearchForm' => $form,
			'Items'      => $list,
		]);
		return $data->index();
	}

	public function Items(): PaginatedList
	{
		$list = $this->getSearchList();
		$list = $this->applySearchFilters($list);
		$list = $list->subtract($this->grid->getList());
		$list = new PaginatedList($list, $this->request);

		return $list;
	}

	public function getSearchList(): SS_List
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

<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\SS_List;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldExtensions;

class PickerFieldAddExistingSearchButton extends GridFieldAddExistingSearchButton
{
	protected ?array $searchFilters = null;
	protected ?array $searchExcludes = null;
	protected $searchList = null;

	public function handleSearch($grid, $request)
	{
		return new PickerFieldAddExistingSearchHandler($grid, $this);
	}

	public function setSearchFilters(array $filters): void
	{
		$this->searchFilters = $filters;
	}

	public function setSearchExcludes(array $excludes): void
	{
		$this->searchExcludes = $excludes;
	}

	public function setSearchList(SS_List $list)
	{
		$this->searchList = $list;
		return $this;
	}

	public function getSearchFilters(): ?array
	{
		return $this->searchFilters;
	}

	public function getSearchExcludes(): ?array
	{
		return $this->searchExcludes;
	}

	public function getSearchList()
	{
		return $this->searchList;
	}

	public function getHTMLFragments($grid)
	{
		GridFieldExtensions::include_requirements();

		$data = new ArrayData([
			'Title' => $this->getTitle(),
			'Link'  => $grid->Link('add-existing-search')
		]);

		return [
			$this->fragment => $data->renderWith('TheWebmen\\GridFieldExtensions\\GridFieldAddExistingSearchButtonOverride'),
		];
	}
}

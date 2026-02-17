<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\ORM\SS_List;
use SilverStripe\Model\ArrayData;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldExtensions;

class PickerFieldAddExistingSearchButton extends GridFieldAddExistingSearchButton
{
	protected ?array $searchFilters = null;
	protected ?array $searchExcludes = null;
	protected ?SS_List $searchList = null;

	public function handleSearch($grid, $request): PickerFieldAddExistingSearchHandler
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

	public function setSearchList(SS_List $list): void
	{
		$this->searchList = $list;
	}

	public function getSearchFilters(): ?array
	{
		return $this->searchFilters;
	}

	public function getSearchExcludes(): ?array
	{
		return $this->searchExcludes;
	}

	public function getSearchList(): ?SS_List
	{
		return $this->searchList;
	}

	public function getHTMLFragments($grid): array
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

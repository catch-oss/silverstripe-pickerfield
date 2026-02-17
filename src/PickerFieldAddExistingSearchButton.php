<?php

namespace TheWebmen\PickerField\Controllers;

use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldExtensions;

class PickerFieldAddExistingSearchButton extends GridFieldAddExistingSearchButton {
	protected $searchFilters	= null;
	protected $searchExcludes	= null;
	protected $searchList		= null;

	public function handleSearch($grid, $request) {
		return new PickerFieldAddExistingSearchHandler($grid, $this);
	}

	public function setSearchFilters($filters) {
		$this->searchFilters = $filters;
	}

	public function setSearchExcludes($excludes) {
		$this->searchExcludes = $excludes;
	}

	/**
	 * Set a custom list to be searched on
	 * @param SS_List $list
	 */
	public function setSearchList(SS_List $list) {
		$this->searchList = $list;
	}

	public function getSearchFilters() {
		return $this->searchFilters;
	}

	public function getSearchExcludes() {
		return $this->searchExcludes;
	}

	public function getSearchList() {
		return $this->searchList;
	}

    public function getHTMLFragments($grid)
    {
        GridFieldExtensions::include_requirements();

        $data = new ArrayData(array(
            'Title' => $this->getTitle(),
            'Link'  => $grid->Link('add-existing-search')
        ));

        return array(
            $this->fragment => $data->renderWith('TheWebmen\\GridFieldExtensions\\GridFieldAddExistingSearchButtonOverride'),
        );
    }

}

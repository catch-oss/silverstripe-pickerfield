<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static string $table_name = 'PickerField_TestDataObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $has_one = [
        'Related' => TestRelatedObject::class,
    ];

    private static array $many_many = [
        'Tags' => TestTagObject::class,
    ];
}

<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestRelatedObject extends DataObject implements TestOnly
{
    private static string $table_name = 'PickerField_TestRelatedObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $has_many = [
        'Parents' => TestDataObject::class,
    ];
}

<?php

namespace TheWebmen\PickerField\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestTagObject extends DataObject implements TestOnly
{
    private static string $table_name = 'PickerField_TestTagObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $belongs_many_many = [
        'Parents' => TestDataObject::class,
    ];
}

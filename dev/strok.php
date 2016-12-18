<?php
    $aEq = [
        'field1:value',
        'field1:field2',
        'table.field1:value',
        'table.field1:field2',
        'table.field1:table.field2'
    ];

    $aBetween = [
        'field1:value1..value2',
        'field1:field2..value1',
        'field1:value1..field2',
        'field1:field2..field3',
        'table.field1:value1..value2',
        'table.field1:field2..value1',
        'table.field1:value1..table.field2',
        'table.field1:table.field2..value1',
        'table.field1:value1..table.field2',
        'table.field1:table.field2..field3',
        'table.field1:field2..table.field3',
        'table.field1:table.field2..table.field3',
    ];

    $aIn = [
        'field:value1,value2,value3',
        'table.field:value1,value2,value3'
    ];

    $aGTE = [
        'field:value..',
        'table.field:value..',
        'field:2017-08-12..',
        'field:2017-08-12T01:00:00..'
    ];

    $aLTE = [
        'field:..value',
        'table.field:..value',
        'field:..2017-08-12',
        'field:..2017-08-12T01:00:00'
    ];

    $aLike = [
        'field:val*',
        'field:val_e',
        'table.field:val*',
        'table.field:val_e'
    ];

    $aNull = [
        'field:NULL',
        'table.field:NULL'
    ];

    $aNot = [
        'field:!value',
        'field:!val*',
        'field:!val_e',
        'field:!NULL',
        'field:!1,3,5'
    ];


    $aQuery = [$aEq[0], $aBetween[0], $aIn[0], $aGTE[0], $aLTE[0], $aLike[0], $aNull[0], $aNot[0]];
    $sQuery = implode(' ', $aQuery);
    $oTokens = strtok($sQuery, " \n\t");

    while ($oTokens != false) {
        echo $oTokens . "\n";
        $oTokens = strtok(" \n\t");
    }
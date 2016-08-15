<?php
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        // Development
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Installed
        require_once __DIR__ . '/../../../autoload.php';
    }

    use Enobrev\ORM\Db;
    use Enobrev\ORM\DbException;

    $oOptions = new \Commando\Command();

    $oOptions->option('h')
        ->aka('host')
        ->describedAs('The hostname or IP of the mysql server you are trying to connect to');

    $oOptions->option('u')
        ->aka('user')
        ->describedAs('The username to log into the database with');

    $oOptions->option('p')
        ->boolean()
        ->aka('password')
        ->describedAs('Prompt for a password');

    $oOptions->option('d')
        ->aka('n')
        ->aka('name')
        ->aka('database')
        ->describedAs('The name of the database you are connecting to');

    $Db    = null;
    $sPass = '';
    $sHost = $oOptions['host'];
    $sUser = $oOptions['user'];
    $sName = $oOptions['name'];
    $bPass = $oOptions['password'];
    $bConnected = false;

    while ($bConnected === false) {
        if (!$sHost) {
            $sHost = readline('Host [localhost]: ');
            if (!$sHost) {
                $sHost = 'localhost';
            }
        }

        if (!$sUser) {
            while (strlen($sUser) == 0) {
                $sUser = readline('User: ');
            }
        }

        if ($bPass) {
            while (strlen($sPass) == 0) {
                $sPass = readline('Password: ');
            }
        }

        try {
            $Db = Db::getInstance($sHost, $sUser, $sPass);
            $bConnected = true;
        } catch (DbException $e) {
            echo $e->getMessage() . "\n";
            $sHost = '';
            $sUser = '';
            $sPass = '';
        }

        if ($bConnected && !$sName) {
            $oDatabases = $Db->query("SELECT schema_name FROM information_schema.schemata;");
            $aDatabases = array();

            while ($oDatabase = $oDatabases->fetch_object()) {
                $aDatabases[] = $oDatabase->schema_name;
            }

            foreach ($aDatabases as $iIndex => $sDatabase) {
                echo str_pad($iIndex + 1, 3, ' ', STR_PAD_LEFT) . ': ' . $sDatabase . "\n";
            }

            while (strlen($sName) == 0) {
                $iSelected = (int) readline('Database: ');
                if (isset($aDatabases[$iSelected - 1])) {
                    $sName = $aDatabases[$iSelected - 1];
                }
            }
        }
    }

    $oTables = $Db->query("SELECT table_name, table_comment FROM information_schema.tables WHERE table_schema = '$sName' AND table_type = 'BASE TABLE';");
    $aTables = array();
    $aTableNames = array();
    $aReferences = array();
    $aReverseReferences = array();
    
    while ($oTable = $oTables->fetch_object()) {
        $sTable  = $oTable->table_name;
        $oFields = $Db->query("SELECT table_name, column_name, ordinal_position, is_nullable, data_type, column_key, column_type, column_default, column_comment FROM information_schema.columns WHERE table_schema = '$sName' AND table_name = '$sTable' ORDER BY ordinal_position ASC");
        $aFields = array();
        $aTableNames[] = $sTable;
        
        while ($oField = $oFields->fetch_object()) {
            $aFields[$oField->column_name] = $oField;
        }

        $aTables[$sTable] = [
            'name'    => $sTable,
            'comment' => $oTable->table_comment,
            'fields'  => $aFields
        ];

        $sSQL = "SELECT column_name, referenced_table_name, referenced_column_name FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema = '$sName' AND table_name = '$sTable' AND LENGTH(referenced_table_name) > 0;";
        $oReferences = $Db->query($sSQL);
        if ($oReferences->num_rows) {
            $aReferences[$sTable] = array();
            while($oReference = $oReferences->fetch_object()) {
                $aReferences[$sTable][$oReference->column_name] = array(
                    'table' => $oReference->referenced_table_name,
                    'field' => $oReference->referenced_column_name
                );

                if (!isset($aReverseReferences[$oReference->referenced_table_name])) {
                    $aReverseReferences[$oReference->referenced_table_name] = array();
                }

                if (!isset($aReverseReferences[$oReference->referenced_table_name])) {
                    $aReverseReferences[$oReference->referenced_table_name][$oReference->referenced_column_name] = array();
                }

                $aReverseReferences[$oReference->referenced_table_name][$oReference->referenced_column_name][] = array(
                    'table' => $sTable,
                    'field' => $oReference->column_name
                );
            }
        }
    }

    function getClassName($sTable) {
        return depluralize(str_replace(' ', '', (ucwords(str_replace('_', ' ', $sTable)))));
    }

    function getFieldTitle($sField) {
        return str_replace(' ', '', (ucwords(str_replace('_', ' ', $sField))));
    }

    function getClassNamePlural($sTable) {
        if (depluralize($sTable) == $sTable) {
            $sTable .= 's'; // force plural
        }

        return str_replace(' ', '', (ucwords(str_replace('_', ' ', $sTable))));
    }

    $aM2MTables   = [];
    $sJsonM2MFile = getcwd() . '/.sql.m2m.json';
    if (file_exists($sJsonM2MFile)) {
        echo 'Using ' . $sJsonM2MFile . "\n";

        $sJsonM2MContents = file_get_contents($sJsonM2MFile);
        $aJsonM2M         = json_decode($sJsonM2MFile, true);
        if (count($aJsonM2M)) {
            foreach(array_keys($aJsonM2M) as $sTable) {
                $aM2MTables[$sTable] = 1;
            }
        }
    } else {
        echo str_pad('0', 3, ' ', STR_PAD_LEFT) . ': ALL' . "\n";
        foreach ($aTableNames as $iIndex => $sTable) {
            echo str_pad($iIndex + 1, 3, ' ', STR_PAD_LEFT) . ': ' . $sTable . "\n";
        }

        $sM2M = trim(readline('Which Tables are M2M: '));

        if ($sM2M) {
            // http://stackoverflow.com/a/7698869/14651
            $aM2MIndices = preg_replace_callback('/(\d+)-(\d+)/', function ($m) {
                return implode(',', range($m[1], $m[2]));
            }, $sM2M);

            $aM2MIndices = array_unique(explode(',', $aM2MIndices));
            foreach ($aM2MIndices as $iIndex) {
                $aM2MTables[$aTableNames[$iIndex - 1]] = 1;
            }

            if (count($aM2MTables)) {
                file_put_contents($sJsonM2MFile, json_encode(array_keys($aM2MTables), JSON_PRETTY_PRINT));
                echo 'Created ' . $sJsonM2MFile . "\n";
            }
        }
    }

    $aFiles = array();
    $aAllData = array(
        'tables' => array()
    );

    foreach($aTables as $sTable => $aTable) {
        $aOutput = array();

        $aData = array(
            'table' => array(
                'name'                  => $sTable,
                'singular'              => depluralize($sTable),
                'title'                 => getClassName($sTable),
                'plural'                => getClassNamePlural($sTable),
                'spaced'                => str_replace('_', ' ', $sTable),
                'spaced_singular'       => depluralize(str_replace('_', ' ', $sTable)),
                'spaced_singular_title' => ucwords(depluralize(str_replace('_', ' ', $sTable))),
                'spaced_title'          => ucwords(str_replace('_', ' ', $sTable)),
                'comment'               => $aTable['comment']
            ),
            'count' => [
                'outbound'  => 0,
                'inbound'   => 0,
                'enum'      => 0,
                'primary'   => 0,
                'unique'    => 0,
                'boolean'   => 0
            ],
            'm2m'            => isset($aM2MTables[$sTable]),
            'has_owner'      => false,
            'has_date'       => false,
            'fields'         => array(),
            'types'          => array(),
            'primary'        => array(),
            'unique'         => array(),
            'date_added'     => false,
            'date_updated'   => false,
            'interfaces'     => array()
        );

        $iFieldNameLength      = 0;
        $iFieldNameShortLength = 0;
        foreach($aTable['fields'] as $sField => $oField) {
            $oTemplateField = array(
                'short'       => str_replace($aData['table']['singular'] . '_', '', $sField),
                'short_title' => str_replace(' ', '', (ucwords(str_replace($aData['table']['singular'] . '_', '', $sField)))),
                'name'        => $sField,
                'title'       => getFieldTitle($sField),
                'primary'     => false,
                'unique'      => false,
                'boolean'     => false,
                'nullable'    => strtolower($oField->is_nullable) == 'yes' ? true : false,
                'var'         => str_replace(' ', '', (ucwords(str_replace('_', ' ', $sField)))),
                'default'     => strlen($oField->column_default) ? $oField->column_default : null,
                'comment'     => $oField->column_comment
            );

            $iFieldNameLength      = max($iFieldNameLength,         strlen($oTemplateField['name']));
            $iFieldNameShortLength = max($iFieldNameShortLength,    strlen($oTemplateField['short']));

            switch(true) {
                case $oField->column_type == 'tinyint(1) unsigned':
                    $aData['count']['boolean']++;
                    $oTemplateField['boolean']  = true;
                    $oTemplateField['type']     = 'Field\\Boolean';
                    $oTemplateField['qltype']   = 'bool';
                    $oTemplateField['php_type'] = 'bool';
                    $oTemplateField['var']      = 'b' . $oTemplateField['var'];
                    break;

                case strpos($oField->data_type, 'int') !== false:
                    if ($oField->column_key == 'PRI') {
                        $oTemplateField['type'] = 'Field\\Id';
                        $oTemplateField['qltype'] = 'id';
                    } else {
                        $oTemplateField['type'] = 'Field\\Integer';
                        $oTemplateField['qltype'] = 'int';
                    }
                    $oTemplateField['php_type'] = 'int';
                    $oTemplateField['var']  = 'i' . $oTemplateField['var'];
                    break;

                case $oField->data_type == 'float':
                case $oField->data_type == 'decimal':
                    $oTemplateField['type']     = 'Field\\Decimal';
                    $oTemplateField['qltype']   = 'float';
                    $oTemplateField['php_type'] = 'float';
                    $oTemplateField['var']      = 'f' . $oTemplateField['var'];
                    break;

                case $oField->data_type == 'varchar':
                case $oField->data_type == 'text':
                case $oField->data_type == 'char':
                    if (strtolower($oField->column_type) == 'char(32)') {
                        if ($oField->is_nullable) {
                            $oTemplateField['type'] = 'Field\\UUIDNullable';
                        } else {
                            $oTemplateField['type'] = 'Field\\UUID';
                        }
                    } else if ($oField->is_nullable) {
                        $oTemplateField['type'] = 'Field\\TextNullable';
                    } else {
                        $oTemplateField['type'] = 'Field\\Text';
                    }

                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type == 'binary':
                    if ($oField->column_type == 'binary(20)') {
                        if ($oField->column_key == 'PRI') {
                            $oTemplateField['type']     = 'Field\\Hash';
                        } else {
                            $oTemplateField['type'] = 'Field\\HashNullable';
                        }
                    } else if ($oField->column_type == 'binary(16)') {
                        if ($oField->column_key == 'PRI') {
                            $oTemplateField['type'] = 'Field\\UUID';
                        } else {
                            $oTemplateField['type'] = 'Field\\UUIDNullable';
                        }
                    }

                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    break;

                case $oField->data_type == 'datetime':
                    $oTemplateField['type']     = 'Field\\DateTime';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                    switch (true) {
                        case strpos($sField, 'added') !== false:
                            $aData['date_added']   = $oTemplateField;
                            $aData['has_date']     = true;
                            $aData['interfaces'][] = 'ModifiedDateColumn';
                            break;

                        case strpos($sField, 'updated') !== false:
                            $aData['date_updated'] = $oTemplateField;
                            $aData['has_date']     = true;
                            $aData['interfaces'][] = 'ModifiedDateColumn';
                            break;
                    }

                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type == 'date':
                    $oTemplateField['type']     = 'Field\\Date';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type == 'time':
                    $oTemplateField['type']     = 'Field\\Time';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type == 'enum':
                    $oTemplateField['type']     = 'Field\\Enum';
                    $oTemplateField['qltype']   = 'enum';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    $oTemplateField['values']   = array();
                    $aData['count']['enum']++;

                    $sType = $oField->column_type;
                    $aEnumType = explode('_', $sField);
                    $sEnumType = end($aEnumType);
                    $sEnumType = strtoupper($sEnumType);
                    if (preg_match_all("/'([^']+)'/", $sType, $aMatches)) {
                        $aEnums = array();
                        foreach($aMatches[1] as $sEnum) {
                            $oTemplateField['values'][] = array(
                                'name'  => $sEnum,
                                'type'  => ucwords(str_replace('_', '', strtolower($sEnumType))),
                                'const' => $sEnumType . '_' . strtoupper(preg_replace('/[^0-9A-Za-z]/', '_', $sEnum)),
                                'const_short' => strtoupper(preg_replace('/[^0-9A-Za-z]/', '_', $sEnum))
                            );
                        }

                        $iLongest = 0;
                        foreach($oTemplateField['values'] as $aValue) {
                            $iLength = strlen($aValue['const']) + 1;
                            if ($iLength > $iLongest) {
                                $iLongest = $iLength;
                            }
                        }

                        foreach($oTemplateField['values'] as $sKey => $aValue) {
                            $oTemplateField['values'][$sKey]['const_padded'] = str_pad($aValue['const'], $iLongest, ' ', STR_PAD_RIGHT);
                        }
                    }

                    if ($oField->column_default) {
                        $oTemplateField['default'] = 'self::' . $sEnumType . '_' . strtoupper(preg_replace('/[^0-9A-Za-z]/', '_', $oField->column_default));
                    }
                    break;

                default:
                    print_r($oField);
                    exit('Danger Will Robinson');
                    break;
            }

            if (isset($aReferences[$sTable])) {
                if (count($aReferences[$sTable])) {
                    if (isset($aReferences[$sTable][$sField])) {
                        $sClass = getClassName($aReferences[$sTable][$sField]['table']);
                        $sClassPlural = getClassNamePlural($aReferences[$sTable][$sField]['table']);
                        $oTemplateField['reference'] = array(
                            'title'                 => getClassName(str_replace($aReferences[$sTable][$sField]['field'], '', $sField) . $sClass),
                            'title_plural'          => getClassNamePlural(str_replace($aReferences[$sTable][$sField]['field'], '', $sField) . $aReferences[$sTable][$sField]['table']),
                            'field'                 => $aReferences[$sTable][$sField]['field'],
                            'name'                  => $aReferences[$sTable][$sField]['table'],
                            'name_singular'         => depluralize($aReferences[$sTable][$sField]['table']),
                            'name_spaced'           => str_replace('_', ' ', $aReferences[$sTable][$sField]['table']),
                            'name_spaced_singular'  => depluralize(str_replace('_', ' ', $aReferences[$sTable][$sField]['table'])),
                            'class'                 => 'Table\\' . $sClass,
                            'class_plural'          => 'Table\\' . $sClassPlural,
                            'subclass'              => $sClass,
                            'subclass_plural'       => $sClassPlural
                        );
                        $aData['count']['outbound']++;

                        if ($sField == 'user_id') {
                            $aData['has_owner']    = true;
                            $aData['interfaces'][] = 'OwnerColumn';
                        }
                    }
                }
            }

            if (isset($aReverseReferences[$sTable])) {
                if (count($aReverseReferences[$sTable])) {
                    if (isset($aReverseReferences[$sTable][$sField])) {
                        foreach($aReverseReferences[$sTable][$sField] as $aReverseReference) {
                            $sClass = getClassNamePlural($aReverseReference['table']);
                            $sName  = str_replace($aData['table']['singular'] . '_', '', $aReverseReference['table']);
                            $oTemplateField['inbound_reference'][$sName] = array(
                                'title'                => getClassName($sClass),
                                'name'                 => $sName,
                                'table'                => $aReverseReference['table'],
                                'name_singular'        => depluralize($aReverseReference['table']),
                                'name_spaced'          => str_replace('_', ' ', str_replace($aData['table']['singular'] . '_', '', $aReverseReference['table'])),
                                'name_spaced_singular' => depluralize(str_replace('_', ' ', $aReverseReference['table'])),
                                'class'                => 'Table\\' . $sClass,
                                'subclass'             => $sClass
                            );
                            $aData['count']['inbound']++;
                        }
                    }
                }
            }

            if ($oField->column_key == 'PRI') {
                $oTemplateField['primary'] = true;
                $aData['primary'][] = $oTemplateField;
                $aData['count']['primary']++;

                if ($sField == 'user_id') {
                    $aData['has_owner']    = true;
                    $aData['interfaces'][] = 'OwnerColumn';
                }
            }

            if ($oField->column_key == 'UNI') {
                $oTemplateField['unique'] = true;
                $aData['unique'][] = $oTemplateField;
                $aData['count']['unique']++;
            }

            $aData['fields'][] = $oTemplateField;

            $sType = str_replace('Field\\', '', $oTemplateField['type']);
            if (!in_array($sType, $aData['types'])) {
                $aData['types'][] = $sType;
            }
        }

        foreach($aData['fields'] as &$aField) {
            $aField['short_pad'] = str_replace($aField['short'], '', str_pad($aField['short'], $iFieldNameShortLength, ' ', STR_PAD_RIGHT));
            $aField['name_pad']  = str_replace($aField['name'],  '', str_pad($aField['name'],  $iFieldNameLength,      ' ', STR_PAD_RIGHT));
        }

        $aData['interfaces'] = implode(', ', array_unique($aData['interfaces']));

        $aAllData['tables'][$aData['table']['name']] = $aData;
    }

    $sJsonFile = getcwd() . '/sql.json';
    file_put_contents($sJsonFile, json_encode($aAllData, JSON_PRETTY_PRINT));
    echo 'Created ' . $sJsonFile . "\n";

    function depluralize($word){
        $rules = array(
            'ss'   => false,
            'os'   => 'o',
            'ies'  => 'y',
            'xes'  => 'x',
            'oes'  => 'o',
            'ves'  => 'f',
            'ches' => 'ch',
            'uses' => 'us',
            'sses' => 'ss',
            's'    => ''
        );

        foreach(array_keys($rules) as $key){
            if(substr($word, (strlen($key) * -1)) != $key)
                continue;
            if($key === false)
                return $word;
            return substr($word, 0, strlen($word) - strlen($key)) . $rules[$key];
        }

        return $word;
    }
?>

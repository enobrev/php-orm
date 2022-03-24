#!/usr/bin/env php
<?php
    $sAutoloadFile = current(
        array_filter([
            __DIR__ . '/../../../autoload.php',
            __DIR__ . '/../../autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/vendor/autoload.php'
        ], 'file_exists')
    );

    if (!$sAutoloadFile) {
        fwrite(STDERR, 'Could Not Find Composer Dependencies' . PHP_EOL);
        die(1);
    }

    /** @noinspection PhpIncludeInspection */
    require $sAutoloadFile;

    use Garden\Cli\Cli;

    use Enobrev\ORM\Db;
    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\Log;

    use function Enobrev\depluralize;
    use function Enobrev\pluralize;

    Log::setService('enobrev-php-orm');
    Log::setPurpose('SQL_TO_JSON');

    $oCLI = new Cli();
    $oCLI->description('Generate SQL.json file from existing database')
         ->opt('host:h',     'The hostname or IP of the mysql server you are trying to connect to')
         ->opt('user:u',     'The username to log into the database with')
         ->opt('password:p', 'The password to log into the database with')
         ->opt('database:d', 'The name of the database you are connecting to')
         ->opt('settings:s', 'Settings File');

    $oArgs = $oCLI->parse($argv, true);

    $Db             = null;
    $sPass          = '';
    $sHost          = $oArgs->getOpt('host');
    $sUser          = $oArgs->getOpt('user');
    $sName          = $oArgs->getOpt('database');
    $sPass          = $oArgs->getOpt('password');
    $sSettingsFile  = $oArgs->getOpt('settings');
    $bConnected     = false;

    while ($bConnected === false) {
        if (!$sHost) {
            $sHost = readline('Host [localhost]: ');
            if (!$sHost) {
                $sHost = 'localhost';
            }
        }

        if (!$sUser) {
            while (strlen($sUser) === 0) {
                $sUser = readline('User: ');
            }
        }

        if (!$sPass) {
            while (strlen($sPass) === 0) {
                $sPass = readline('Password: ');
            }
        }

        try {
            $Db = Db::getInstance(Db::defaultMySQLPDO($sHost, $sUser, $sPass));
            $bConnected = true;
        } catch (DbException $e) {
            echo $e->getMessage() . "\n";
            $sHost = '';
            $sUser = '';
            $sPass = '';
        }

        if ($bConnected && !$sName) {
            $oDatabases = $Db->query('SELECT schema_name FROM information_schema.schemata;');
            $aDatabases = $oDatabases->fetchAll(PDO::FETCH_COLUMN, 0);

            foreach ($aDatabases as $iIndex => $sDatabase) {
                echo str_pad($iIndex + 1, 3, ' ', STR_PAD_LEFT) . ': ' . $sDatabase . "\n";
            }

            while (strlen($sName) === 0) {
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

    while ($oTable = $oTables->fetchObject()) {
        $sTable  = $oTable->table_name;
        $oFields = $Db->query("SELECT table_name, column_name, ordinal_position, is_nullable, data_type, character_maximum_length, numeric_precision, datetime_precision, column_key, column_type, column_default, column_comment, extra FROM information_schema.columns WHERE table_schema = '$sName' AND table_name = '$sTable' ORDER BY ordinal_position ASC");
        $aFields = array();
        $aTableNames[] = $sTable;

        while ($oField = $oFields->fetchObject()) {
            $aFields[$oField->column_name] = $oField;
        }

        $aTables[$sTable] = [
            'name'    => $sTable,
            'comment' => $oTable->table_comment,
            'fields'  => $aFields
        ];

        $sSQL = "SELECT column_name, referenced_table_name, referenced_column_name FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema = '$sName' AND table_name = '$sTable' AND LENGTH(referenced_table_name) > 0;";
        $oReferences = $Db->query($sSQL);
        while($oReference = $oReferences->fetchObject()) {
            if (!isset($aReferences[$sTable])) {
                $aReferences[$sTable] = [];
            }

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

    function depluralizeTableName($sTable) {
        $aTableName = explode('_', $sTable);

        if (count($aTableName) === 1) {
            return depluralize($sTable);
        }

        $sNameEnd     = array_pop($aTableName);
        $aTableName[] = depluralize($sNameEnd);

        return implode('_', $aTableName);
    }

    function getClassName($sTable) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', depluralizeTableName($sTable))));
    }

    function getFieldTitle($sField) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $sField)));
    }

    function getClassNamePlural($sTable) {
        return pluralize(str_replace(' ', '', ucwords(str_replace('_', ' ', depluralizeTableName($sTable)))));
    }

    $aM2MTables           = [];
    $aIgnoreTables        = [];
    $aOverrides           = [];
    $bSettingsFileExisted = false;
    $aSettings = [
        'ignore'    => [],
        'override'  => [],
        'm2m'       => []
    ];
    if ($sSettingsFile && file_exists($sSettingsFile)) {
        echo 'Using ' . $sSettingsFile . "\n";

        $bSettingsFileExisted = true;
        $sSettingsContents = file_get_contents($sSettingsFile);
        $aSettings         = array_merge($aSettings, json_decode($sSettingsContents, true));

        if (!isset($aSettings['ignore'])) {
            $aSettings['ignore'] = [];
        }

        if (!isset($aSettings['override'])) {
            $aSettings['override'] = [];
        }

        if (!isset($aSettings['m2m'])) {
            $aSettings['m2m'] = [];
        }
    }

    if (count($aSettings['m2m'])) {
        foreach($aSettings['m2m'] as $sTable) {
            $aM2MTables[$sTable] = 1;
        }
    }

    if (count($aSettings['ignore'])) {
        foreach($aSettings['ignore'] as $sTable) {
            $aIgnoreTables[$sTable] = 1;
        }
    }

    if (count($aSettings['override'])) {
        foreach($aSettings['override'] as $sTable => $aFields) {
            $aOverrides[$sTable] = $aFields;
        }
    }

    if (!$bSettingsFileExisted) {
        echo str_pad('0', 3, ' ', STR_PAD_LEFT) . ': ALL' . "\n";
        foreach ($aTableNames as $iIndex => $sTable) {
            echo str_pad($iIndex + 1, 3, ' ', STR_PAD_LEFT) . ': ' . $sTable . "\n";
        }

        $sM2M = trim(readline('Which Tables are M2M: '));

        if ($sM2M) {
            // http://stackoverflow.com/a/7698869/14651
            $aM2MIndices = preg_replace_callback('/(\d+)-(\d+)/', static function ($m) {
                return implode(',', range($m[1], $m[2]));
            }, $sM2M);

            $aM2MIndices = array_unique(explode(',', $aM2MIndices));
            foreach ($aM2MIndices as $iIndex) {
                $aM2MTables[$aTableNames[$iIndex - 1]] = 1;
            }

            if (count($aM2MTables)) {
                $aSettings['m2m'] = array_keys($aM2MTables);
                file_put_contents($sSettingsFile, json_encode($aSettings, JSON_PRETTY_PRINT));
                echo 'Created ' . $sSettingsFile . "\n";
            }
        }
    }

    $aFiles = array();
    $aAllData = array(
        'tables' => array()
    );

    foreach($aTables as $sTable => $aTable) {
        if (isset($aIgnoreTables[$sTable])) {
            echo "Ignoring $sTable\n";
            continue;
        }

        $aOutput = array();

        $sClassName       = 'Table\\' . getClassName($sTable);
        $sClassNamePlural = 'Table\\' . getClassNamePlural($sTable);
        $sNamePlural      = getClassNamePlural($sTable);

        if ($sClassNamePlural === $sClassName) {
            // TODO: In my experience this only happens with 'beta'.  Probably happens with any "uncountable" from the pluralizer
            $sClassNamePlural .= 's';
            $sNamePlural      .= 's';
        }

        $aData = array(
            'table' => array(
                'name'                  => $sTable,
                'singular'              => depluralizeTableName($sTable),
                'title'                 => getClassName($sTable),
                'plural'                => $sNamePlural,
                'spaced'                => str_replace('_', ' ', $sTable),
                'spaced_singular'       => str_replace('_', ' ', depluralizeTableName($sTable)),
                'spaced_singular_title' => ucwords(str_replace('_', ' ', depluralizeTableName($sTable))),
                'spaced_title'          => ucwords(str_replace('_', ' ', $sTable)),
                'comment'               => $aTable['comment'],
                'class'                 => $sClassName,
                'class_plural'          => $sClassNamePlural,
            ),
            'count' => [
                'outbound'  => 0,
                'inbound'   => 0,
                'enum'      => 0,
                'primary'   => 0,
                'unique'    => 0,
                'boolean'   => 0,
                'skipped'   => 0
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
            if (in_array($oField->column_type, ['point', 'geometry', 'linestring', 'polygon', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection'])) {
                $aData['count']['skipped']++;
                continue;
            }

            $sShort = str_replace($aData['table']['singular'] . '_', '', $sField);
            $oTemplateField = array(
                'short'             => $sShort,
                'short_title'       => str_replace(' ', '', ucwords(str_replace('_', ' ', $sShort))),
                'name'              => $sField,
                'title'             => getFieldTitle($sField),
                'plural'            => pluralize(getFieldTitle($sField)),
                'primary'           => false,
                'unique'            => false,
                'boolean'           => false,
                'generated'         => stripos($oField->extra, 'generated') !== false,
                'auto_increment'    => strtolower($oField->extra) === 'auto_increment',
                'nullable'          => strtolower($oField->is_nullable) === 'yes',
                'var'               => getFieldTitle($sField),
                'var_array'         => 'a' . pluralize(getFieldTitle($sField)),
                'default'           => $oField->column_default ?? null,
                'comment'           => $oField->column_comment,
                'mysql_type'        => $oField->data_type
            );

            if (property_exists($oField, 'character_maximum_length') && $oField->character_maximum_length) {
                $oTemplateField['max_length'] = $oField->character_maximum_length;
            }

            if (property_exists($oField, 'numeric_precision') && $oField->numeric_precision) {
                $oTemplateField['numeric_precision'] = $oField->numeric_precision;
            }

            if (property_exists($oField, 'datetime_precision') && $oField->datetime_precision) {
                $oTemplateField['datetime_precision'] = $oField->datetime_precision;
            }

            $iFieldNameLength      = max($iFieldNameLength,         strlen($oTemplateField['name']));
            $iFieldNameShortLength = max($iFieldNameShortLength,    strlen($oTemplateField['short']));

            switch(true) {
                case $oField->column_type === 'tinyint(1) unsigned':
                    $aData['count']['boolean']++;
                    $oTemplateField['boolean']  = true;
                    $oTemplateField['type']     = 'Field\\Boolean';
                    $oTemplateField['qltype']   = 'bool';
                    $oTemplateField['php_type'] = 'bool';
                    $oTemplateField['var']      = 'b' . $oTemplateField['var'];
                    break;

                case strpos($oField->data_type, 'int') !== false:
                case strpos($oField->data_type, 'year') !== false:
                case $oField->data_type === 'timestamp' && (!$oField->datetime_precision || $oField->datetime_precision === 0):
                    if ($oField->column_key === 'PRI' && $oField->extra === 'auto_increment') {
                        $oTemplateField['type'] = 'Field\\Id';
                        $oTemplateField['qltype'] = 'id';
                    } else {
                        $oTemplateField['type'] = 'Field\\Integer';
                        $oTemplateField['qltype'] = 'int';
                    }

                    $oTemplateField['php_type'] = 'int';
                    $oTemplateField['var']  = 'i' . $oTemplateField['var'];
                    break;

                case $oField->data_type === 'float':
                case $oField->data_type === 'decimal':
                case $oField->data_type === 'double':
                case $oField->data_type === 'timestamp' && $oField->datetime_precision > 0:
                    $oTemplateField['type']     = 'Field\\Decimal';
                    $oTemplateField['qltype']   = 'float';
                    $oTemplateField['php_type'] = 'float';
                    $oTemplateField['var']      = 'f' . $oTemplateField['var'];
                    break;

                case $oField->data_type === 'varchar':
                case $oField->data_type === 'text':
                case $oField->data_type === 'mediumtext':
                case $oField->data_type === 'longtext':
                case $oField->data_type === 'char':
                    if (strtolower($oField->column_type) === 'char(32)') {
                        if ($oTemplateField['nullable']) {
                            $oTemplateField['type'] = 'Field\\UUIDNullable';
                        } else {
                            $oTemplateField['type'] = 'Field\\UUID';
                        }
                    } else if ($oTemplateField['nullable']) {
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

                case $oField->data_type === 'json':
                    $oTemplateField['type']     = 'Field\\JSONText';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type === 'binary':
                    if ($oField->column_type === 'binary(20)') {
                        if ($oField->column_key === 'PRI') {
                            $oTemplateField['type']     = 'Field\\Hash';
                        } else {
                            $oTemplateField['type'] = 'Field\\HashNullable';
                        }
                    } else if ($oField->column_type === 'binary(16)') {
                        if ($oField->column_key === 'PRI') {
                            $oTemplateField['type'] = 'Field\\UUID';
                        } else {
                            $oTemplateField['type'] = 'Field\\UUIDNullable';
                        }
                    }

                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    break;

                case $oField->data_type === 'datetime':
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

                case $oField->data_type === 'date':
                    $oTemplateField['type']     = 'Field\\Date';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type === 'time':
                    $oTemplateField['type']     = 'Field\\Time';
                    $oTemplateField['qltype']   = 'string';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                    if ($oField->column_default) {
                        $oTemplateField['default'] = '"' . $oField->column_default . '"';
                    }
                    break;

                case $oField->data_type === 'enum':
                    $oTemplateField['type']     = 'Field\\Enum';
                    $oTemplateField['qltype']   = 'enum';
                    $oTemplateField['php_type'] = 'string';
                    $oTemplateField['var']      = 's' . $oTemplateField['var'];
                    $oTemplateField['values']   = array();
                    $aData['count']['enum']++;

                    $sType = $oField->column_type;
                    $sEnumField = str_replace($aData['table']['singular'], '', $sField);
                    $sEnumField = trim($sEnumField, '_');
                    $sEnumType = strtoupper($sEnumField);

                    $oTemplateField['enum_type']  = $sEnumType;
                    $oTemplateField['enum_const'] = strtoupper(pluralize($sEnumType));

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
                            $oTemplateField['values'][$sKey]['const_padded'] = str_pad($aValue['const'], $iLongest);
                        }
                    }

                    if ($oField->column_default) {
                        $oTemplateField['default'] = 'self::' . $sEnumType . '_' . strtoupper(preg_replace('/[^0-9A-Za-z]/', '_', $oField->column_default));
                    }
                    break;

                default:
                    /** @noinspection ForgottenDebugOutputInspection */
                    print_r($oField);
                    exit('Danger Will Robinson');
            }

            if ($oField->column_key === 'PRI') {
                $oTemplateField['primary'] = true;

                if ($sField === 'user_id') {
                    $aData['has_owner']    = true;
                    $aData['interfaces'][] = 'OwnerColumn';
                }
            }

            if ($oField->column_key === 'UNI') {
                $oTemplateField['unique'] = true;
            }

            $sDefault = $oField->column_default ?? null;
            if ($sDefault !== null) {
                $sDefault = preg_replace('/[^a-z]/', '', strtolower($sDefault));
            }

            if ($sDefault === 'null') {
                $oTemplateField['default'] = null;
            }

            if (isset($aOverrides[$sTable][$sField])) {
                echo "Overriding $sTable.$sField\n";
                $oTemplateField = array_merge($oTemplateField, $aOverrides[$sTable][$sField]);
            }

            $aData['fields'][] = $oTemplateField;

            $sType = str_replace('Field\\', '', $oTemplateField['type']);
            if (!in_array($sType, $aData['types'], true)) {
                $aData['types'][] = $sType;
            }
        }

        foreach($aData['fields'] as &$aField) {
            $aField['short_pad'] = str_replace($aField['short'], '', str_pad($aField['short'], $iFieldNameShortLength));
            $aField['name_pad']  = str_replace($aField['name'],  '', str_pad($aField['name'],  $iFieldNameLength));

            if ($aField['primary']) {
                $aData['primary'][] = $aField;
                $aData['count']['primary']++;
            } else if ($aField['unique']) {
                $aData['unique'][] = $aField;
                $aData['count']['unique']++;
            }
        }
        unset($aField);

        $aData['interfaces'] = array_values(array_unique($aData['interfaces']));

        $aAllData['tables'][$aData['table']['name']] = $aData;
    }

    foreach ($aAllData['tables'] as $sTable => $aData) {
        if (isset($aIgnoreTables[$sTable])) {
            echo "Ignoring $sTable References\n";
            continue;
        }

        if (isset($aReferences[$sTable]) && count($aReferences[$sTable])) {
            foreach($aReferences[$sTable] as $sColumn => $aReference) {
                $iIndex            = array_search($sColumn, array_column($aAllData['tables'][$sTable]['fields'], 'name'), true);
                $sReferencedTable  = $aReference['table'];
                $sReferencedField  = $aReference['field'];
                $aReferencedFields = $aAllData['tables'][$sReferencedTable]['fields'];

                $aReferencedTable = $aAllData['tables'][$sReferencedTable]['table'];
                $aReferencedField = $aReferencedFields[array_search($sReferencedField, array_column($aReferencedFields, 'name'), true)];

                unset($aReferencedField['reference'], $aReferencedField['inbound_reference']);

                $sPreparedReferenceField = str_replace($sReferencedField, '', $sColumn);
                $bIsJustPrefix           = $sPreparedReferenceField === $aReferencedTable['name'] . '_';

                $sReferenceTitle         = !$bIsJustPrefix ? getClassName($sPreparedReferenceField) . $aReferencedTable['title'] : $aReferencedTable['title'];
                $sReferenceTitlePlural   = !$bIsJustPrefix ? getClassNamePlural($sPreparedReferenceField . $aReferencedTable['title']) : getClassNamePlural($aReferencedTable['title']);

                $aAllData['tables'][$sTable]['fields'][$iIndex]['reference'] = [
                    'title'         => $sReferenceTitle,
                    'title_plural'  => $sReferenceTitlePlural,
                    'table'         => $aReferencedTable,
                    'field'         => $aReferencedField
                ];

                $aAllData['tables'][$sTable]['count']['outbound']++;

                if ($sColumn === 'user_id') {
                    $aAllData['tables'][$sTable]['has_owner']    = true;
                    $aAllData['tables'][$sTable]['interfaces'][] = 'OwnerColumn';
                }
            }
        }

        if (isset($aReverseReferences[$sTable]) && count($aReverseReferences[$sTable])) {
            foreach ($aReverseReferences[$sTable] as $sColumn => $aReverseReferencesForColumn) {
                $iIndex = array_search($sColumn, array_column($aAllData['tables'][$sTable]['fields'], 'name'), true);
                foreach($aReverseReferencesForColumn as $aReverseReference) {
                    $sReferencedTable  = $aReverseReference['table'];
                    $sReferencedField  = $aReverseReference['field'];

                    if (isset($aIgnoreTables[$sReferencedTable])) {
                        continue;
                    }

                    $aReferencedFields = $aAllData['tables'][$sReferencedTable]['fields'];

                    $aReferencedTable  = $aAllData['tables'][$sReferencedTable]['table'];
                    $aReferencedField  = $aReferencedFields[array_search($sReferencedField, array_column($aReferencedFields, 'name'), true)];

                    unset($aReferencedField['reference'], $aReferencedField['inbound_reference']);

                    $aAllData['tables'][$sTable]['fields'][$iIndex]['inbound_reference'][] = [
                        'table' => $aReferencedTable,
                        'field' => $aReferencedField
                    ];
                }

                $aAllData['tables'][$sTable]['count']['inbound']++;
            }
        }

        $aAllData['tables'][$sTable]['interfaces'] = implode(', ', array_unique($aAllData['tables'][$sTable]['interfaces']));
    }

    // Ensuring objects are sorted consistently
    ksort($aAllData['tables']);

    foreach($aAllData['tables'] as $sTable => &$aTable) {
        foreach($aTable['fields'] as $iFieldIndex => &$aField) {
            if (isset($aField['inbound_reference'])) {
                usort($aField['inbound_reference'], static function($a, $b) {
                    return $a['table']['name'] <=> $b['table']['name'] ?: $a['field']['name'] <=> $b['field']['name'];
                });
            }
        }
        unset($aField);
    }
    unset($aTable);

    $sJsonFile = getcwd() . '/sql.json';
    file_put_contents($sJsonFile, json_encode($aAllData, JSON_PRETTY_PRINT));

    echo 'Created ' . $sJsonFile . "\n";
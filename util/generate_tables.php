<?php
    require_once __DIR__ .'/../../../autoload.php';

    use Enobrev\ORM\Db;

    if ($argc < 7) {
        echo 'Usage: php generate_tables.php host user pass database path';
        exit();
    }

    $sHost = $argv[1];
    $sUser = $argv[2];
    $sPass = $argv[3];
    $sName = $argv[4];
    $sPath = $argv[5];
    $sNamespace = $argv[6];

    $oLoader = new Twig_Loader_Filesystem(dirname(__FILE__));
    $oTwig = new Twig_Environment($oLoader, array('debug' => true));
    $oTemplate = $oTwig->loadTemplate('table_template.twig');

    $Db = Db::getInstance($sHost, $sUser, $sPass, $sName);
    $oTables = $Db->query('SHOW TABLES;');
    $aTables = array();
    $aReferences = array();
    
    while ($oTable = $oTables->fetch_object()) {
        $sTable = $oTable->{'Tables_in_' . $sName};
        $oFields = $Db->query('DESCRIBE ' . $sTable);
        $aFields = array();
        
        while ($oField = $oFields->fetch_object()) {
            $aFields[$oField->Field] = $oField;
        }
        
        $aTables[$sTable] = $aFields;

        $sSQL = "SELECT column_name, referenced_table_name, referenced_column_name FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema = '" . $oConfig->NAME . "' AND table_name = '" . $sTable . "' AND LENGTH(referenced_table_name) > 0;";
        $oReferences = $Db->query($sSQL);
        if ($oReferences->num_rows) {
            $aReferences[$sTable] = array();
            while($oReference = $oReferences->fetch_object()) {
                $aReferences[$sTable][$oReference->column_name] = array(
                    'table' => $oReference->referenced_table_name,
                    'field' => $oReference->referenced_column_name
                );
            }
        }
    }
    
    foreach(array_keys($aTables) as $sTable) {
        echo $sTable . "\n";
    }
    
    echo "\n";
    echo "\n";
    echo '!!!!! WARNING - This Overwrites Shit.  If you Do Not know what this script does, you should just hit Enter to stop now - WARNING !!!' . "\n";
    echo "\n";
    echo 'Which Tables (comma separated, ALL for all):';
    $sChosen = trim(fgets(STDIN));
    
    $aChosenTables = array();
    if ($sChosen === 'ALL') {
        $aChosenTables = $aTables;
    } else {
        $aChosen = explode(',', $sChosen);
        $aChosen = array_map('trim', $aChosen);
        if (count($aChosen)) {
            foreach($aChosen as $sChosenTable) {
                if (isset($aTables[$sChosenTable])) {
                    $aChosenTables[$sChosenTable] = $aTables[$sChosenTable];
                }
            }
        }
    }

    function getClassName($sTable) {
        return depluralize(str_replace(' ', '', (ucwords(str_replace('_', ' ', $sTable)))));
    }

    function getClassNamePlural($sTable) {
        return str_replace(' ', '', (ucwords(str_replace('_', ' ', $sTable))));
    }
        
    if (count($aChosenTables)) {
        $aFiles = array();

        foreach($aChosenTables as $sTable => $aFields) {
            $aOutput = array();

            $aData = array(
                'table'         => array(
                    'name'    => $sTable,
                    'title'   => getClassName($sTable),
                    'plural'  => getClassNamePlural($sTable)
                ),
                'fields'        => array(),
                'types'         => array(),
                'primary'       => array(),
                'date_added'    => false,
                'date_updated'  => false
            );
            
            foreach($aFields as $sField => $oField) {
                $oTemplateField = array(
                    'name'    => $sField,
                    'primary' => false,
                    'var'     => str_replace(' ', '', (ucwords(str_replace('_', ' ', $sField)))),
                    'default' => strlen($oField->Default) ? $oField->Default : null
                );

                switch(true) {
                    case strpos($oField->Type, 'int') !== false:
                        if ($oField->Key == 'PRI') {
                            $oTemplateField['type'] = 'F_Id';
                        } else {
                            $oTemplateField['type'] = 'F_Integer';
                        }
                        $oTemplateField['var']  = 'i' . $oTemplateField['var'];

                        if (isset($aReferences[$sTable])) {
                            if (count($aReferences[$sTable])) {
                                if (isset($aReferences[$sTable][$sField])) {
                                    $sClass = getClassName($aReferences[$sTable][$sField]['table']);
                                    $oTemplateField['reference'] = array(
                                        'title' => getClassName(str_replace($aReferences[$sTable][$sField]['field'], '', $sField) . $sClass),
                                        'class' => 'Table_' . $sClass
                                    );
                                }
                            }
                        }
                        break;

                    case strpos($oField->Type, 'float')   !== false:
                    case strpos($oField->Type, 'decimal') !== false:
                        $oTemplateField['type'] = 'F_Decimal';
                        $oTemplateField['var']  = 'f' . $oTemplateField['var'];
                        break;
                    
                    case strpos($oField->Type, 'varchar') !== false:
                    case strpos($oField->Type, 'text')    !== false:
                    case strpos($oField->Type, 'char')    !== false:
                        $oTemplateField['type']     = 'F_Text';
                        $oTemplateField['var']      = 's' . $oTemplateField['var'];
                        if ($oField->Default) {
                            $oTemplateField['default'] = '"' . $oField->Default . '"';
                        }
                        break;

                    case strpos($oField->Type, 'binary') !== false:
                        if (strpos($oField->Type, '20') !== false) {
                            if ($oField->Key == 'PRI') {
                                $oTemplateField['type'] = 'F_Hash';
                            } else {
                                $oTemplateField['type'] = 'F_HashNullable';
                            }
                        } else if (strpos($oField->Type, '16') !== false) {
                            if ($oField->Key == 'PRI') {
                                $oTemplateField['type'] = 'F_UUID';
                            } else {
                                $oTemplateField['type'] = 'F_UUIDNullable';
                            }
                        }

                        $oTemplateField['var']      = 's' . $oTemplateField['var'];

                        if (isset($aReferences[$sTable])) {
                            if (count($aReferences[$sTable])) {
                                if (isset($aReferences[$sTable][$sField])) {
                                    $sClass = getClassName($aReferences[$sTable][$sField]['table']);
                                    $oTemplateField['reference'] = array(
                                        'title' => getClassName(str_replace($aReferences[$sTable][$sField]['field'], '', $sField) . $sClass),
                                        'class' => 'Table_' . $sClass
                                    );
                                }
                            }
                        }
                        break;

                    case strpos($oField->Type, 'datetime') !== false:
                        $oTemplateField['type'] = 'F_DateTime';
                        $oTemplateField['var']  = 'd' . $oTemplateField['var'];

                        switch (true) {
                            case strpos($sField, 'added') !== false:
                                $aData['date_added'] = $oTemplateField;
                                break;

                            case strpos($sField, 'updated') !== false:
                                $aData['date_updated'] = $oTemplateField;
                                break;
                        }

                        if ($oField->Default) {
                            $oTemplateField['default'] = '"' . $oField->Default . '"';
                        }
                        break;

                    case strpos($oField->Type, 'date') !== false:
                        $oTemplateField['type']     = 'F_Date';
                        $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                        if ($oField->Default) {
                            $oTemplateField['default'] = '"' . $oField->Default . '"';
                        }
                        break;

                    case strpos($oField->Type, 'time') !== false:
                        $oTemplateField['type']     = 'F_Time';
                        $oTemplateField['var']      = 'd' . $oTemplateField['var'];

                        if ($oField->Default) {
                            $oTemplateField['default'] = '"' . $oField->Default . '"';
                        }
                        break;
                    
                    case strpos($oField->Type, 'enum') !== false:
                        $oTemplateField['type']    = 'F_Enum';
                        $oTemplateField['var']     = 's' . $oTemplateField['var'];
                        $oTemplateField['values']  = array();

                        $sType = $oField->Type;
                        $aEnumType = explode('_', $sField);
                        $sEnumType = end($aEnumType);
                        $sEnumType = strtoupper($sEnumType);
                        if (preg_match_all("/'([^']+)'/", $sType, $aMatches)) {
                            $aEnums = array();                    
                            foreach($aMatches[1] as $sEnum) {
                                $oTemplateField['values'][] = array(
                                    'name' => $sEnum,
                                    'const' => $sEnumType . '_' . strtoupper(str_replace(' ', '_', $sEnum))
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

                        if ($oField->Default) {
                            $oTemplateField['default'] = 'self::' . $sEnumType . '_' . strtoupper(str_replace(' ', '_', $oField->Default));
                        }
                        break;
                    
                    default:
                        print_r($oField);
                        exit('Danger Will Robinson');
                        break;
                }
                
                if ($oField->Key == 'PRI') {
                    $oTemplateField['primary'] = true;
                    $aData['primary'][] = $oTemplateField;
                }

                $aData['fields'][] = $oTemplateField;

                $sType = str_replace('F_', '', $oTemplateField['type']);
                if (!in_array($sType, $aData['types'])) {
                    $aData['types'][] = $sType;
                }
            }

            $aData['namespace']     = $sNamespace;
            $aData['primary_count'] = count($aData['primary']);
            $aFiles[$aData['table']['title'] . '.php'] = $oTemplate->render($aData);
            $aFiles[$aData['table']['plural'] . '.php'] = $oTemplate->render($aData);
        }

        foreach($aFiles as $sFile => $sOutput) {
            file_put_contents($sPath . $sFile, $sOutput);
            echo 'Created ' . $sPath . $sFile . "\n";
        }
    } else {
        echo 'No Tables Chosen' . "\n";
    }

    function depluralize($word){
        $rules = array(
            'ss'  => false,
            'os'  => 'o',
            'ies' => 'y',
            'xes' => 'x',
            'oes' => 'o',
            'ves' => 'f',
            's'   => ''
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

<?php
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        // Development
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Installed
        require_once __DIR__ . '/../../../autoload.php';
    }

    if ($argc < 3) {
        echo 'Usage: php generate_tables.php path_to_sql.json write_path namespace' . "\n";
        exit();
    }

    $sPathJsonSQL = $argv[1];
    $sPath        = $argv[2];
    $sNamespace   = $argv[3];

    $oLoader    = new Twig_Loader_Filesystem(dirname(__FILE__));
    $oTwig      = new Twig_Environment($oLoader, array('debug' => true));
    $oTemplate  = $oTwig->loadTemplate('template_table.twig');
    $oTemplates = $oTwig->loadTemplate('template_tables.twig');

    $aDatabase  = json_decode(file_get_contents($sPathJsonSQL), true);

    echo implode("\n", array_keys($aDatabase['tables']));
    echo "\n";
    echo "\n";
    echo '!!!!! WARNING - This Overwrites Shit.  If you Do Not know what this script does, you should just hit Enter to stop now - WARNING !!!' . "\n";
    echo "\n";
    echo 'Which Tables (comma separated, ALL for all):';
    $sChosen = trim(fgets(STDIN));
    
    $aChosenTables = array();
    if ($sChosen === 'ALL') {
        $aChosenTables = $aDatabase['tables'];
    } else {
        $aChosen = explode(',', $sChosen);
        $aChosen = array_map('trim', $aChosen);
        if (count($aChosen)) {
            foreach($aChosen as $sChosenTable) {
                if (isset($aDatabase['tables'][$sChosenTable])) {
                    $aChosenTables[$sChosenTable] = $aDatabase['tables'][$sChosenTable];
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

        foreach($aChosenTables as $sTable => $aData) {
            $aData['namespace']     = $sNamespace;
            $aFiles[$aData['table']['title']  . '.php'] = $oTemplate->render($aData);
            $aFiles[$aData['table']['plural'] . '.php'] = $oTemplates->render($aData);
        }

        foreach($aFiles as $sFile => $sOutput) {
            file_put_contents($sPath . $sFile, $sOutput);
            echo 'Created ' . $sPath . $sFile . "\n";
        }
    } else {
        echo 'No Tables Chosen' . "\n";
    }
?>

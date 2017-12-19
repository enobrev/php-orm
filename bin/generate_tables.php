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

    require $sAutoloadFile;

    $oOptions = new \Commando\Command();

    $oOptions->option('j')
        ->require()
        ->expectsFile()
        ->aka('json')
        ->describedAs('The JSON file output from sql_to_json.php')
        ->must(function($sFile) {
            return file_exists($sFile);
        });

    $oOptions->option('o')
        ->require()
        ->aka('output')
        ->describedAs('The output Path for the files to be written to');

    $oOptions->option('n')
        ->require()
        ->aka('namespace')
        ->describedAs('The namespace for the generated Table classes');

    $sPathJsonSQL = $oOptions['json'];
    $sPath        = rtrim($oOptions['output'], '/') . '/';
    $sNamespace   = $oOptions['namespace'];

    $oLoader    = new Twig_Loader_Filesystem(dirname(__FILE__));
    $oTwig      = new Twig_Environment($oLoader, array('debug' => true));
    $oTemplate  = $oTwig->loadTemplate('template_table.twig');
    $oTemplates = $oTwig->loadTemplate('template_tables.twig');

    $aDatabase  = json_decode(file_get_contents($sPathJsonSQL), true);
    $aTableNames = array_keys($aDatabase['tables']);

    echo str_pad('0', 3, ' ', STR_PAD_LEFT) . ': ALL' . "\n";
    foreach($aTableNames as $iIndex => $sTable) {
        echo str_pad($iIndex + 1, 3, ' ', STR_PAD_LEFT) . ': ' . $sTable . "\n";
    }
    echo "\n";
    echo "\n";
    echo '!!!!! WARNING - This Overwrites Shit.  If you Do Not know what this script does, you should just hit Enter to stop now - WARNING !!!' . "\n";
    echo "\n";

    $sChosen = trim(readline('Which Tables (commas and ranges, 0 for all): '));
    $sChosen = str_replace(' ', '', $sChosen);

    // http://stackoverflow.com/a/7698869/14651
    $aIndices = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
        return implode(',', range($m[1], $m[2]));
    }, $sChosen);

    $aIndices = array_unique(explode(',', $aIndices));

    $aChosenTables = array();
    if (in_array(0, $aIndices)) {
        $aChosenTables = $aDatabase['tables'];
    } else if (count($aIndices)) {
        $aChosen = array();
        foreach($aIndices as $iIndex) {
            $aChosen[] = $aTableNames[$iIndex - 1];
        }

        if (count($aChosen)) {
            foreach($aChosen as $sChosenTable) {
                if (isset($aDatabase['tables'][$sChosenTable])) {
                    $aChosenTables[$sChosenTable] = $aDatabase['tables'][$sChosenTable];
                }
            }
        }
    }

    if (count($aChosenTables)) {
        $aFiles = array();

        foreach($aChosenTables as $sTable => $aData) {
            $aData['namespace']     = $sNamespace;
            $aFiles[$aData['table']['title']  . '.php'] = $oTemplate->render($aData);
            $aFiles[$aData['table']['plural'] . '.php'] = $oTemplates->render($aData);
        }

        if (!file_exists($sPath)) {
            mkdir($sPath, 0777, true);
        }

        foreach($aFiles as $sFile => $sOutput) {
            file_put_contents($sPath . $sFile, $sOutput);
            echo 'Created ' . $sPath . $sFile . "\n";
        }
    } else {
        echo 'No Tables Chosen' . "\n";
    }
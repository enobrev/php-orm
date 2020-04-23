#!/usr/bin/env php
<?php
    use Garden\Cli\Cli;

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

    $oCLI = new Cli();

    $oCLI->description('Generate ORM Table Classes')
        ->opt('json:j',      'The JSON file output from sql_to_json.php',       true)
        ->opt('output:o',    'The output Path for the files to be written to',  true)
        ->opt('namespace:n', 'The namespace for the generated Table classes',   true);

    $oArgs = $oCLI->parse($argv, true);

    $sPathJsonSQL   = $oArgs->getOpt('json');
    $sPath          = rtrim($oArgs->getOpt('output'), '/') . '/';
    $sGeneratedPath = $sPath . '_generated/';
    $sNamespace     = $oArgs->getOpt('namespace');

    if (!file_exists($sPathJsonSQL)) {
        echo "Could not find json file at $sPathJsonSQL";
        exit(1);
    }

    $oLoader    = new Twig\Loader\FilesystemLoader(__DIR__);
    $oTwig      = new Twig\Environment($oLoader, array('debug' => true));

    try {
        $oBaseTemplate  = $oTwig->load('template_base_table.twig');
        $oBaseTemplates = $oTwig->load('template_base_tables.twig');
        $oTemplate      = $oTwig->load('template_table.twig');
        $oTemplates     = $oTwig->load('template_tables.twig');
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        exit(1);
    }

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
    $aIndices = preg_replace_callback('/(\d+)-(\d+)/', static function($m) {
        return implode(',', range($m[1], $m[2]));
    }, $sChosen);

    $aIndices = array_unique(explode(',', $aIndices));

    $aChosenTables = array();
    if (in_array(0, $aIndices, false)) {
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
        if (!file_exists($sGeneratedPath) && !mkdir($sGeneratedPath, 0777, true) && !is_dir($sGeneratedPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $sGeneratedPath));
        }

        $aPrivateFiles = [];
        $aPublicFiles  = [];

        foreach($aChosenTables as $sTable => $aData) {
            $aData['private_namespace'] = $sNamespace . "\\_generated";
            $aData['public_namespace']  = $sNamespace;

            $aPrivateFiles[$aData['table']['title'] . '.php']  = $oBaseTemplate->render($aData);
            $aPrivateFiles[$aData['table']['plural'] . '.php'] = $oBaseTemplates->render($aData);
            $aPublicFiles[$aData['table']['title'] . '.php']   = $oTemplate->render($aData);
            $aPublicFiles[$aData['table']['plural'] . '.php']  = $oTemplates->render($aData);
        }

        foreach($aPrivateFiles as $sFile => $sOutput) {
            $sFullName = $sGeneratedPath . $sFile;
            file_put_contents($sFullName, $sOutput);
            echo "Created $sFullName\n";
        }

        foreach($aPublicFiles as $sFile => $sOutput) {
            $sFullName = $sPath . $sFile;
            if (!file_exists($sFullName)) {
                file_put_contents($sFullName, $sOutput);
                echo "Created $sFullName\n";
            }
        }
    } else {
        echo 'No Tables Chosen' . "\n";
    }
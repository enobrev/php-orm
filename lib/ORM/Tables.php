<?php
    namespace Enobrev\ORM;

    use MySQLi_Result;
    use Enobrev\SQL;

    class TablesException extends DbException {}
    class TablesMultiplePrimaryException extends TablesException {}

    class Tables {

        protected static $sTable = null;

        /**
         * @param array $aData
         * @param array $aMap
         * @return array
         */
        public static function createAndUpdateFromMap(Array $aData, Array $aMap) {
            $aOutput = array();
            foreach($aData as $aDatum) {
                /** @var Table $sTable */
                $sTable = '\\Enobrev\\Table\\' . static::$sTable;
                $aOutput[] = $sTable::createAndUpdateFromMap($aDatum, $aMap);
            }

            return $aOutput;
        }

        /**
         * @param array $aData
         * @return array
         */
        public static function createAndUpdate(Array $aData) {
            $aOutput = array();
            foreach($aData as $aDatum) {
                /** @var Table $sTable */
                $sTable = '\\Enobrev\\Table\\' . static::$sTable;
                $aOutput[] = $sTable::createAndUpdate($aDatum);
            }

            return $aOutput;
        }

        /**
         * @param string        $sTable
         * @param MySQLi_Result $oResults
         * @param string        $sKey  Column to use as array key
         * @return Table[]
         */
        public static function toTables($sTable, MySQLi_Result $oResults, $sKey = null) {
            $sTable = '\\Enobrev\\Table\\' . $sTable;
            $aTables = array();
            if ($oResults->num_rows) {
                /** @var Table $sTable */
                if ($sKey) {
                    while ($oResult = $oResults->fetch_object()) {
                        $aTables[$oResult->$sKey] = $sTable::createFromObject($oResult);
                    }
                } else {
                    while ($oResult = $oResults->fetch_object()) {
                        $aTables[] = $sTable::createFromObject($oResult);
                    }
                }
            }

            return $aTables;
        }

        /**
         * @param array         $aTables
         * @param MySQLi_Result $oResults
         * @return Table[]
         */
        public static function toMultipleTables(Array $aTables, MySQLi_Result $oResults) {
            $aOutput = array();
            while ($oResult = $oResults->fetch_object()) {
                $aRow = array();
                foreach($aTables as $sTable) {
                    /** @var Table $sPrefixedTable */
                    $sPrefixedTable = '\\Enobrev\\Table\\' . $sTable;
                    $aRow[$sTable]  = $sPrefixedTable::createFromObject($oResult);
                }
                $aOutput[] = $aRow;
            }

            return $aOutput;
        }

        /**
         * @param $sTable
         * @param MySQLi_Result $oResults
         * @return array (ids)
         */
        public static function toIds($sTable, MySQLi_Result $oResults) {
            $aIds = array();
            if ($oResults->num_rows) {
                /** @var Table $oTable */
                $oTable = new $sTable;
                while($oResult = $oResults->fetch_object()) {
                    foreach ($oTable->getPrimary() as $oPrimary) {
                        $aIds[] = $oResult->{$oPrimary->sColumn};
                    }
                }
            }

            return $aIds;
        }

        /**
         * @param Table[] $aTables
         * @return array
         */
        public static function arrayToIds(Array $aTables) {
            $aIds = array();

            foreach($aTables as $oTable) {
                foreach ($oTable->getPrimary() as $oPrimary) {
                    $aIds[] = $oTable->{$oPrimary->sColumn};
                }
            }

            return $aIds;
        }

        /**
         * @param Table[] $aTables
         * @param string $sKey
         * @param string $sValue,...
         * @return array
         */
        public static function toArray(Array $aTables, $sKey = '', $sValue = '') {
            $aFilter = func_get_args();
            array_shift($aFilter);
            array_shift($aFilter);

            $aReturn = array();
            foreach ($aTables as $oTable) {
                $aValue = $oTable->toArray();

                if ($sKey) {
                    /** @var Field $oTable->$sKey */
                    $aReturn[$oTable->$sKey->getValue()] = $aValue;
                } else {
                    $aReturn[] = $aValue;
                }
            }

            return $aReturn;
        }
    }
?>
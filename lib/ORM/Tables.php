<?php
    namespace Enobrev\ORM;

    use ArrayIterator;
    use MySQLi_Result;
    use Enobrev\SQL;

    class TablesException extends DbException {}
    class TablesMultiplePrimaryException extends TablesException {}

    class Tables extends ArrayIterator {
        /**
         * @return Table
         * @throws TablesException
         */
        public static function getTable() {
            throw new TablesException('This Method Should Have Been Overridden');
        }

        /**
         * @return Tables
         */
        public static function get() {
            $oTable = static::getTable();
            $oSQL = SQL::select($oTable);

            $oResults = Db::getInstance()->namedQuery([__CLASS__, __METHOD__], $oSQL);
            return self::fromResults($oResults, $oTable);
        }

        /**
         * @return int
         */
        public static function total() {
            $oTable = static::getTable();
            $oSQL = SQL::count($oTable);

            $oResults = Db::getInstance()->namedQuery([__CLASS__, __METHOD__], $oSQL);
            if ($oResults->num_rows > 0) {
                return (int) $oResults->fetch_object()->row_count;
            }

            return 0;
        }

        /**
         * @param Table $oTable
         * @param array $aData
         * @param array $aMap
         * @return Tables
         */
        public static function createAndUpdateFromMap(Table $oTable, Array $aData, Array $aMap) {
            $aOutput = new static;
            foreach($aData as $aDatum) {
                /** @var Table $sTable */
                $sTable = get_class($oTable);
                $aOutput->append($sTable::createAndUpdateFromMap($aDatum, $aMap));
            }

            return $aOutput;
        }

        /**
         * @param Table $oTable
         * @param array $aData
         * @return Tables
         */
        public static function createAndUpdate(Table $oTable, Array $aData) {
            $aOutput = new static;
            foreach($aData as $aDatum) {
                /** @var Table $sTable */
                $sTable = get_class($oTable);
                $aOutput->append($sTable::createAndUpdate($aDatum));
            }

            return $aOutput;
        }

        /**
         * @param MySQLi_Result $oResults
         * @param Table[] ...$aTables
         * @return Tables
         */
        protected static function fromResults(MySQLi_Result $oResults, ...$aTables) {
            $oOutput = new static;
            if (count($aTables) > 1) {
                while ($oResult = $oResults->fetch_object()) {
                    $aRow = array();
                    foreach ($aTables as $oTable) {
                        /** @var Table $sPrefixedTable */
                        $sPrefixedTable = get_class($oTable);
                        $aRow[$oTable->getTitle()] = $sPrefixedTable::createFromObject($oResult);
                    }
                    $oOutput->append($aRow);
                }
            } else {
                $sPrefixedTable = get_class($aTables[0]);
                while ($oResult = $oResults->fetch_object($sPrefixedTable)) {
                    $oOutput->append($oResult);
                }
            }

            return $oOutput;
        }

        /**
         * @param string $sKey
         * @param string $sValue
         * @return array
         */
        public function toKeyValueArray($sKey, $sValue) {
            $aReturn = [];
            foreach($this as $oTable) {
                $aReturn[$oTable->$sKey->getValue()] = $oTable->$sValue->getValue();
            }

            return $aReturn;
        }

        /**
         * @return Field[]
         * @throws \Exception
         */
        public function toPrimaryFieldArray() {
            $aReturn = [];
            foreach($this as $oTable) {
                $aPrimary = $oTable->getPrimary();
                if (count($aPrimary) > 1) {
                    throw new \Exception("Can Only get Primary Array of Tables with Single Primary Keys");
                }

                $aReturn[] = $oTable->{$aPrimary[0]->sColumn};
            }

            return $aReturn;
        }

        /**
         * @return array
         * @throws \Exception
         */
        public function toPrimaryArray() {
            $aReturn = [];
            foreach($this as $oTable) {
                $aPrimary = $oTable->getPrimary();
                if (count($aPrimary) > 1) {
                    throw new \Exception("Can Only get Primary Array of Tables with Single Primary Keys");
                }

                $aReturn[] = $oTable->{$aPrimary[0]->sColumn}->getValue();
            }

            return $aReturn;
        }

        /**
         * @param string $sKey
         * @return array
         */
        public function toArray($sKey = '') {
            $aReturn = [];
            if ($sKey) {
                foreach ($this as $oTable) {
                    $aReturn[$oTable->$sKey->getValue()] = $oTable->toArray();
                }
            } else {
                foreach ($this as $oTable) {
                    $aReturn[] = $oTable->toArray();
                }
            }

            return $aReturn;
        }
    }
?>
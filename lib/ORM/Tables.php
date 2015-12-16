<?php
    namespace Enobrev\ORM;

    use ArrayIterator;
    use MySQLi_Result;
    use Enobrev\SQL;

    class TablesException extends DbException {}
    class TablesMultiplePrimaryException extends TablesException {}

    class Tables extends ArrayIterator {
        protected static $sTable = null;

        /**
         * @param Table $oTable
         * @param array $aData
         * @param array $aMap
         * @return Tables
         */
        public static function createAndUpdateFromMap(Table $oTable, Array $aData, Array $aMap) {
            $aOutput = new self;
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
            $aOutput = new self;
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
            $oOutput = new self;
            while ($oResult = $oResults->fetch_object()) {
                if (count($aTables) > 1) {
                    $aRow = array();
                    foreach ($aTables as $oTable) {
                        /** @var Table $sPrefixedTable */
                        $sPrefixedTable = get_class($oTable);
                        $aRow[$oTable->getTitle()] = $sPrefixedTable::createFromObject($oResult);
                    }
                    $oOutput->append($aRow);
                } else {
                    $oOutput->append($aTables[0]::createFromObject($oResult));
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
<?php
    namespace Enobrev\ORM;

    use ArrayIterator;
    use Enobrev\SQLBuilder;
    use PDO;
    use PDOStatement;

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
         * @return Table[]|static
         */
        public static function get() {
            $oTable   = static::getTable();
            $oResults = Db::getInstance()->namedQuery(__METHOD__, SQLBuilder::select($oTable));
            return self::fromResults($oResults, $oTable);
        }

        /**
         * @return int
         */
        public static function total() {
            $oTable   = static::getTable();
            $oResults = Db::getInstance()->namedQuery(__METHOD__, SQLBuilder::count($oTable));
            $iTotal   = $oResults->fetchColumn();
            if ($iTotal !== false) {
                return (int) $iTotal;
            }

            return 0;
        }

        /**
         * @param Table $oTable
         * @param array $aData
         * @param array $aMap
         * @return Table[]|static
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
         * @return Table[]|static
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
         * @param PDOStatement $oResults
         * @param Table[] ...$aTables
         * @return Table[]|static
         */
        protected static function fromResults(PDOStatement $oResults, ...$aTables) {
            if (count($aTables) > 1) {
                $oOutput = new static;
                while ($oResult = $oResults->fetchObject()) {
                    $aRow = array();
                    foreach ($aTables as $oTable) {
                        /** @var Table $sPrefixedTable */
                        $sPrefixedTable = get_class($oTable);
                        $aRow[$oTable->getTitle()] = $sPrefixedTable::createFromObject($oResult);
                    }
                    $oOutput->append($aRow);
                }

                return $oOutput;
            } else {
                $sPrefixedTable = get_class($aTables[0]);
                return new static($oResults->fetchAll(PDO::FETCH_CLASS, $sPrefixedTable));
            }

        }

        public function append($value) {
            $sClass = static::getTable();
            if ($value instanceof $sClass !== false) {
                parent::append($value);
            } else {
                // TODO: Log incorrect value or throw an exception or something
            }
        }

        /**
         * @param Field|array $mFields
         * @return array
         */
        public function toValueArray($mFields) {
            if ($mFields instanceof Field) {
                $sField  = $mFields->sColumn;
                $aReturn = [];
                foreach ($this as $oTable) {
                    $aReturn[] = $oTable->$sField->getValue();
                }

                return $aReturn;
            } else {
                $aFields = [];
                /** @var Field $oField */
                foreach($mFields as $oField) {
                    $aFields[] = $oField->sColumn;
                }

                $aReturn = [];
                foreach ($this as $oTable) {
                    $aRow = [];
                    foreach ($aFields as $sField) {
                        $aRow[$sField] = $oTable->$sField->getValue();
                    }

                    $aReturn[] = $aRow;
                }

                return $aReturn;
            }
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

        /**
         * @return string
         */
        public function toCSV() {
            /** @var Table $oRecord */
            $oRecord = $this->offsetGet(0);
            $aFields = [];
            foreach($oRecord->getFields() as $oField) {
                $aFields[] = $oField->toSQLColumnForFields(false);
            }

            $oOutput = fopen("php://temp", "w");

            fputcsv($oOutput, $aFields);

            foreach($this as $oTable) {
                fputcsv($oOutput, $oTable->toArray());
            }
            rewind($oOutput);

            $sOutput = '';
            while(!feof($oOutput)) {
                $sOutput .= fread($oOutput, 8192);
            }

            fclose($oOutput);

            return $sOutput;
        }
    }
?>
<?php
    namespace Enobrev\ORM;

    use Exception;
    use ArrayIterator;
    use Enobrev\SQLBuilder;
    use PDO;
    use PDOStatement;

    class TablesException extends DbException {}
    class TablesMultiplePrimaryException extends TablesException {}
    class TablesInvalidFieldException extends TablesException {}

    class Tables extends ArrayIterator {
        /**
         * @return Table
         */
        public static function getTable() {
            throw new TablesException('This Method Should Have Been Overridden');
        }

        /**
         * @return Table[]|static
         * @throws DbException
         * @psalm-suppress InvalidArgument
         */
        public static function get() {
            $oTable   = static::getTable();
            $oResults = Db::getInstance()->namedQuery(__METHOD__, SQLBuilder::select($oTable));
            return static::fromResults($oResults, $oTable);
        }

        /**
         * @return int
         * @throws DbException
         */
        public static function total() {
            $oTable   = static::getTable();
            $oResults = Db::getInstance()->namedQuery(__METHOD__, SQLBuilder::count($oTable));
            $iTotal   = $oResults->fetchColumn();
            /** @psalm-suppress TypeDoesNotContainType */
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
                    /** @var Table $oTable */
                    foreach ($aTables as $oTable) {
                        /** @var Table $sPrefixedTable */
                        /** @psalm-suppress InvalidArgument */
                        $sPrefixedTable = get_class($oTable);
                        $sTableName     = $oTable->getTitle();
                        if ($sTableName) {
                            $aRow[$sTableName] = $sPrefixedTable::createFromObject($oResult);
                        }
                    }
                    $oOutput->append($aRow);
                }

                return $oOutput;
            } else {
                /** @psalm-suppress InvalidArgument */
                $sPrefixedTable = get_class($aTables[0]);
                return new static($oResults->fetchAll(PDO::FETCH_CLASS, $sPrefixedTable));
            }

        }

        /**
         * @param PDOStatement $oResults
         * @param Table $oTable
         * @return static
         * @throws TablesException
         * @throws Exception
         */
        protected static function fromResultsWithMeta(PDOStatement $oResults, Table $oTable) {
            /** @var Table $sPrefixedTable */
            $sPrefixedTable = get_class($oTable);
            $oOutput        = new static;
            while ($oResult = $oResults->fetchObject()) {
                $oOutput->append($sPrefixedTable::createFromObject($oResult));
            }
            return $oOutput;
        }

        /**
         * @param mixed $value
         * @throws TablesException
         * @throws \Exception
         */
        public function append($value): void {
            $sClass = static::getTable();
            if ($value instanceof $sClass !== false) {
                parent::append($value);
            } else {
                // TODO: Log incorrect value or throw an exception or something
                throw new TablesException('Cannot Append Table of Type ' . get_class($value) . ' to ' . get_class($this));
            }
        }

        /**
         * @param Field|array $mFields
         * @return array
         */
        public function toValueArray($mFields): array {
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
        public function toKeyValueArray(string $sKey, string $sValue): array {
            $aReturn = [];
            foreach($this as $oTable) {
                $aReturn[$oTable->$sKey->getValue()] = $oTable->$sValue->getValue();
            }

            return $aReturn;
        }

        /**
         * @param string $sField
         * @return array
         * @throws TablesInvalidFieldException
         */
        public function toFieldValueArray(string $sField): array {
            $aFields = $this->toFieldArray($sField);
            $aReturn = [];
            if (count($aFields)) {
                foreach($aFields as $oField) {
                    $aReturn[] = $oField->getValue();
                }
            }

            return $aReturn;
        }

        /**
         * @param string $sField
         * @return Field[]
         * @throws TablesInvalidFieldException
         */
        public function toFieldArray(string $sField): array {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            if (array_values((array) $this)[0]->$sField instanceof Field === false) {
                throw new TablesInvalidFieldException("Invalid Field Requested");
            }

            foreach ($this as $oTable) {
                $aReturn[] = $oTable->$sField;
            }

            return $aReturn;
        }

        /**
         * @return Field[]
         * @throws TablesInvalidFieldException
         * @throws TablesMultiplePrimaryException
         */
        public function toPrimaryFieldArray() {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            return $this->toFieldArray($this->getOnlyPrimary()->sColumn);
        }

        /**
         * @return Field[]
         * @throws TablesInvalidFieldException
         * @throws TablesMultiplePrimaryException
         */
        public function toPrimaryArray() {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            return $this->toFieldValueArray($this->getOnlyPrimary()->sColumn);
        }

        /**
         * @return Field
         * @throws TablesMultiplePrimaryException
         */
        private function getOnlyPrimary() {
            /** @var Field[] $aPrimary */
            $aPrimary = static::getTable()->getPrimary();
            if (count($aPrimary) > 1) {
                throw new TablesMultiplePrimaryException("Can Only get Primary Array of Tables with Single Primary Keys");
            }

            return $aPrimary[0];
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
         * @return Table[]
         * @throws TablesMultiplePrimaryException
         */
        public function toPrimaryKeyedArray() {
            $sPrimary = $this->getOnlyPrimary()->sColumn;
            $aReturn  = [];
            foreach ($this as $oTable) {
                $aReturn[$oTable->$sPrimary->getValue()] = $oTable;
            }
            return $aReturn;
        }

        protected function getCSVFields() {
            /** @var Table $oRecord */
            /** @psalm-suppress InvalidScalarArgument */
            $oRecord = $this->offsetGet(0);
            $aFields = [];
            foreach($oRecord->getFields() as $oField) {
                $aFields[] = $oField->toSQLColumnForFields(false);
            }

            return $aFields;
        }

        protected function getCSVHeaders() {
            /** @var Table $oRecord */
            /** @psalm-suppress InvalidScalarArgument */
            $oRecord = $this->offsetGet(0);
            $aFields = [];
            foreach($oRecord->getFields() as $oField) {
                $aFields[] = $oField->toSQLColumnForFields(false);
            }

            return $aFields;
        }

        /**
         * @param array $aExclude
         * @return string
         */
        public function toCSV(array $aExclude = []) {
            /** @var Table $oRecord */
            /** @psalm-suppress InvalidScalarArgument */
            $oRecord = $this->offsetGet(0);
            $aHeaders = [];
            foreach($oRecord->getFields() as $oField) {
                if (in_array($oField->sColumn, $aExclude)) {
                    continue;
                }

                $aHeaders[] = $oField->toSQLColumnForFields(false);
            }

            $oOutput = fopen("php://temp", "w");

            if (!$oOutput) {
                return '';
            }

            fputcsv($oOutput, $aHeaders);

            foreach($this as $oTable) {
                $aValues   = $oTable->toArray();
                $aFiltered = [];

                foreach($aValues as $sKey => $sValue) {
                    if (in_array($sKey, $aExclude)) {
                        continue;
                    }

                    $aFiltered[$sKey] = $sValue;
                }

                fputcsv($oOutput, $aFiltered);
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
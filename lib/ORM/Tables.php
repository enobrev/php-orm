<?php
    namespace Enobrev\ORM;

    use ArrayIterator;
    use Exception;
    use PDO;
    use PDOStatement;
    use ReflectionClass;
    use ReflectionException;

    use Enobrev\Log;
    use Enobrev\SQL;
    use Enobrev\SQLBuilder;

    class TablesException extends DbException {}
    class TablesMultiplePrimaryException  extends TablesException {}
    class TablesInvalidTableException     extends TablesException {}
    class TablesInvalidFieldException     extends TablesException {}
    class TablesInvalidReferenceException extends TablesException {}

    class Tables extends ArrayIterator {

        /** @var string */
        private static $sNamespaceTable = null;
        /**
         * @param string $sNamespaceTable
         */
        public static function init(string $sNamespaceTable): void {
            self::$sNamespaceTable = trim($sNamespaceTable, '\\');
        }

        /**
         * @param string $sTableClass
         * @return string
         */
        public static function getNamespacedTableClassName(string $sTableClass): string {
            return implode('\\', [self::$sNamespaceTable, $sTableClass]);
        }

        /**
         * @return Table
         */
        public static function getTable() {
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
         * @param int $iCount
         * @return Table[]|Tables
         */
        public static function getRandom(int $iCount = 1) {
            if (!$iCount) {
                return new self();
            }

            $oTable   = static::getTable();
            $oSQL     = SQLBuilder::select($oTable);
            $sSQL     = $oSQL->toString() . ' ORDER BY RAND() ' . SQL::limit($iCount)->toSQL();
            $oResults = Db::getInstance()->namedQuery(__METHOD__, $sSQL);
            return self::fromResults($oResults, $oTable);
        }

        /**
         * @param null|string $sSearch
         * @return array
         */
        public static function searchTermPreProcess(?string $sSearch): array {
            if (!$sSearch) {
                return [];
            }

            $aResponse = [
                'type'       => 'OR',
                'conditions' => []
            ];

            if (preg_match('/^(AND|OR)/', $sSearch, $aMatches)) {
                $aResponse['type'] = $aMatches[1];
                $sSearch = trim(preg_replace('/^(AND|OR)/', '', $sSearch));
            };

            $sSearch = preg_replace('/\s+/', ' ', $sSearch);
            $sSearch = preg_replace('/(\w+)([%:><!]+)"(\w+)/', '"${1}${2}${3}', $sSearch); // Make things like field:"Some Value" into "field: Some Value"
            $aSearch = str_getcsv($sSearch, ' ');

            foreach($aSearch as $sSearchTerm) {
                if (strpos($sSearchTerm, ':') !== false) {
                    $aCondition = [];
                    $aSearchTerm  = explode(':', $sSearchTerm);
                    $aCondition['operator'] = ':';
                    $aCondition['field']    = array_shift($aSearchTerm);
                    $aCondition['value']    = implode(':', $aSearchTerm);
                    $aResponse['conditions'][] = $aCondition;

                } else if (strpos($sSearchTerm, '>') !== false) {
                    // FIXME: Obviously ridiculous.  we should be parsing this properly instead of repeating
                    $aCondition = [];
                    $aSearchTerm  = explode('>', $sSearchTerm);
                    $aCondition['operator'] = '>';
                    $aCondition['field']    = array_shift($aSearchTerm);
                    $aCondition['value']    = implode('>', $aSearchTerm);
                    $aResponse['conditions'][] = $aCondition;
                } else if (strpos($sSearchTerm, '<') !== false) {
                    // FIXME: Obviously ridiculous.  we should be parsing this properly instead of repeating
                    $aCondition = [];
                    $aSearchTerm  = explode('<', $sSearchTerm);
                    $aCondition['operator'] = '<';
                    $aCondition['field']    = array_shift($aSearchTerm);
                    $aCondition['value']    = implode('<', $aSearchTerm);
                    $aResponse['conditions'][] = $aCondition;
                } else if (strpos($sSearchTerm, '!') !== false) {
                    // FIXME: Obviously ridiculous.  we should be parsing this properly instead of repeating
                    $aCondition = [];
                    $aSearchTerm  = explode('!', $sSearchTerm);
                    $aCondition['operator'] = '!';
                    $aCondition['field']    = array_shift($aSearchTerm);
                    $aCondition['value']    = implode('!', $aSearchTerm);
                    $aResponse['conditions'][] = $aCondition;
                } else {
                    $aCondition['operator'] = '::';
                    $aCondition['value']    = $sSearchTerm;
                    $aResponse['conditions'][] = $aCondition;
                }
            }

            return $aResponse;
        }

        /**
         * @param string $sSort
         * @return array
         */
        public static function sortTermPreProcess(string $sSort): array {
            if (!$sSort) {
                return [];
            }

            $aResponse = [];
            $sSort     = trim($sSort);
            $sGetSort  = preg_replace('/,\s+/', ',', $sSort);
            $aSort     = explode(',', $sGetSort);

            foreach($aSort as $sSort) {
                if (strpos($sSort, '.')) {
                    $aSplit = explode('.', $sSort);
                    if (count($aSplit) == 2) {
                        $aResponse[] = [
                            'table' => $aSplit[0],
                            'field' => $aSplit[1]
                        ];
                    }
                } else {
                    $aResponse[] = [
                        'field' => $sSort
                    ];
                }
            }

            return $aResponse;
        }

        /**
         * @param int|null $iPage
         * @param int|null $iPer
         * @param array|null $aSearch
         * @param array|null $aSort
         * @param null|string $sSyncDate
         * @return Table[]|Tables
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TablesException
         * @throws TablesInvalidReferenceException
         * @throws TablesInvalidTableException
         */
        public static function getForCMS(?int $iPage = 1, ?int $iPer = 100, ?array $aSearch = null, ?array $aSort = null, ?string $sSyncDate = null) {
            $oQuery = self::getQueryForCMS($aSearch, $iPage, $iPer, $aSort, $sSyncDate);
            $oResults = Db::getInstance()->namedQuery(__METHOD__, $oQuery);
            return static::fromResults($oResults, static::getTable());
        }

        /**
         * @param array|null $aSearch
         * @return int
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TablesInvalidReferenceException
         * @throws TablesInvalidTableException
         */
        public static function countForCMS(?array $aSearch = null): int {
            if (!$aSearch) {
                $oTable   = static::getTable();
                $sTable   = $oTable->getTitle();
                $oResults = Db::getInstance()->namedQuery(__METHOD__, "SHOW TABLE STATUS LIKE '$sTable'");
                return (int) $oResults->fetchObject()->Rows;
            }

            $oQuery = self::getQueryForCMS($aSearch);
            $oQuery->setType(SQLBuilder::TYPE_COUNT);
            $oResults = Db::getInstance()->namedQuery(__METHOD__, $oQuery);
            return (int) $oResults->fetchObject()->row_count;
        }

        /**
         * @param array|null $aSearch
         * @param int|null $iPage
         * @param int|null $iPer
         * @param array|null $aSort
         * @param null|string $sSyncDate
         * @param null|Field[] $aFields
         * @return SQLBuilder
         * @throws TablesInvalidReferenceException
         * @throws TablesInvalidTableException
         */
        protected static function getQueryForCMS(?array $aSearch = null, ?int $iPage = null, ?int $iPer = null, ?array $aSort = null, ?string $sSyncDate = null, ?array $aFields = []) {
            $oTable      = static::getTable();
            $oQuery      = SQLBuilder::select($oTable);

            if (count($aFields)) {
                $oQuery->fields(...$aFields);
            } else {
                $oQuery->fields($oTable);
            }

            if ($iPer) {
                if (!$iPage) {
                    $iPage = 1;
                }

                $iStart = $iPer * ($iPage - 1);
                $oQuery->limit($iStart, $iPer);
            }

            if ($aSearch) {
                $aSQLConditions = [];

                foreach($aSearch['conditions'] as $aCondition) {
                    $sSearchValue = $aCondition['value'];
                    $sSearchField = $aCondition['field'] ?? null;

                    Log::d('Tables.getForCMS', [
                        'table'     => $oTable->getTitle(),
                        'condition' => $aCondition
                    ]);

                    if ($sSearchField) {
                        $oSearchField = $oTable->$sSearchField;

                        if ($oSearchField instanceof Field === false) {
                            // TODO: Throw Error
                            continue;
                        }
                    }

                    switch($aCondition['operator']) {
                        case '::':
                            // Search all Searchable fields - we should be checking if this is a general search (no colons or >'s or anything) and then only do this in that case
                            foreach ($oTable->getFields() as $oField) {
                                if ($oField instanceof Field\Number
                                ||  $oField instanceof Field\Enum
                                ||  $oField instanceof Field\Date) {
                                    $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                } else if ($oField instanceof Field\Text && strpos($sSearchValue, '%') !== false) {
                                    $aSQLConditions[] = SQL::like($oField, $sSearchValue);
                                } else {
                                    $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                }
                            }
                            break;

                        case ':':
                            if ($sSearchValue == 'null') {
                                $aSQLConditions[] = SQL::nul($oSearchField);
                            } else if ($oSearchField instanceof Field\Number
                                   ||  $oSearchField instanceof Field\Enum
                                   ||  $oSearchField instanceof Field\Date) {
                                $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                            } else if (strpos($sSearchValue, '%') !== false) {
                                $aSQLConditions[] = SQL::like($oSearchField, $sSearchValue);
                            } else {
                                $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                            }
                            break;

                        case '!':
                            if ($sSearchValue == 'null') {
                                $aSQLConditions[] = SQL::nnul($oSearchField);
                            } else if ($oSearchField instanceof Field\Number
                                   ||  $oSearchField instanceof Field\Enum
                                   ||  $oSearchField instanceof Field\Date) {
                                $aSQLConditions[] = SQL::neq($oSearchField, $sSearchValue);
                            } else {
                                $aSQLConditions[] = SQL::nlike($oSearchField, '%' . $sSearchValue . '%');
                            }
                            break;

                        case '>':
                            if ($oSearchField instanceof Field\Number
                            ||  $oSearchField instanceof Field\Date) {
                                $aSQLConditions[] = SQL::gt($oSearchField, $sSearchValue);
                            }
                            break;

                        case '<':
                            if ($oSearchField instanceof Field\Number
                            ||  $oSearchField instanceof Field\Date) {
                                $aSQLConditions[] = SQL::lt($oSearchField, $sSearchValue);
                            }
                            break;
                    }
                }

                if (count($aSQLConditions)) {
                    if ($aSearch['type'] == 'AND') {
                        $oQuery->also(...$aSQLConditions);
                    } else {
                        $oQuery->either(...$aSQLConditions);
                    }
                }
            }

            if ($aSort) {
                foreach($aSort as $aField) {
                    $sSortTableField = $aField['field'];

                    if (isset($aField['table'])) {
                        Log::d('Tables.getForCMS.Sort.Foreign', $aField);
                        $sSortTableClass = $aField['table'];

                        /** @var Table $oSortTable */
                        $oSortTable = new $sSortTableClass();
                        if (!$oSortTable instanceof Table) {
                            throw new TablesInvalidTableException($sSortTableClass . " is not a valid Table");
                        }

                        $oSortReference = $oSortTable->getFieldThatReferencesTable($oTable);
                        if ($oSortReference instanceof Field !== false) {
                            try {
                                // The SortBy Field is in a table that references our Primary Table
                                // Join from the Referenced Primary Table Field to the Sort Table Referencing Field
                                $sReferenceField = $oSortReference->referenceField();
                                $oQuery->fields($oTable); // Setting Primary Table fields to ensure joined fields aren't the only ones returned
                                $oQuery->join($oTable->$sReferenceField, $oSortReference);
                            } catch (ConditionsNonConditionException $e) {
                                Log::e('Tables.getForCMS.Sort.Foreign.JoinConditionError', ['error' => $e]);
                            }
                        } else {
                            $oSortReference = $oTable->getFieldThatReferencesTable($oSortTable);

                            if ($oSortReference instanceof Field === false) {
                                $sTableName = '[Table]';
                                $sRefTableName = '[Referenced Table]';
                                try {
                                    $sTableName    = (new ReflectionClass($oTable))->getShortName();
                                    $sRefTableName = (new ReflectionClass($oSortReference))->getShortName();
                                } catch (ReflectionException $e) {
                                    // No worries
                                }
                                
                                throw new TablesInvalidReferenceException("Cannot Associate $sTableName with  $sRefTableName");
                            }

                            try {
                                // The SortBy Field is in a table that our Primary Table references
                                // Join from the Referencing Primary Table Field to the Referenced Sort Table Field Base Table Field
                                $sReferenceField = $oSortReference->referenceField();
                                $oQuery->fields($oTable); // Setting Primary Table fields to ensure joined fields aren't the only ones returned
                                $oQuery->join($oSortReference, $oSortTable->$sReferenceField);
                            } catch (ConditionsNonConditionException $e) {
                                Log::e('Tables.getForCMS.Sort.Foreign.JoinConditionError', ['error' => $e]);
                            }
                        }

                        $oQuery->asc($oSortTable->$sSortTableField);
                    } else {
                        $oSortField = $oTable->$sSortTableField;
                        if ($oSortField instanceof Field) {
                            $oQuery->asc($oSortField);
                        }
                    }
                }
            }

            if ($sSyncDate) {
                if ($oTable instanceof ModifiedDateColumn) {
                    $oQuery->also(
                        SQL::gte($oTable->getModifiedDateField(), $sSyncDate)
                    );
                }
            }

            return $oQuery;
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
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TableException
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
                return new static($oResults->fetchAll(PDO::FETCH_CLASS, $sPrefixedTable, ['', true]));
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
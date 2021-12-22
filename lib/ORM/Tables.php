<?php
    namespace Enobrev\ORM;

    use ArrayIterator;
    use Enobrev\ORM\Exceptions\TablesInvalidTableException;
    use PDO;
    use PDOStatement;
    use Throwable;

    use Enobrev\Log;
    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\SQL;
    use Enobrev\SQLBuilder;

    abstract class Tables extends ArrayIterator {
        const WILDCARD = '*';

        private static string $sNamespaceTable;

        /**
         * @return Db
         * @throws DbException
         */
        protected static function Db(): Db {
            return Db::getInstance();
        }
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
        abstract public static function getTable();

        /**
         * @return static
         * @throws DbException
         * @psalm-suppress InvalidArgument
         */
        public static function get() {
            $oTable   = static::getTable();
            $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, SQLBuilder::select($oTable));
            return static::fromResults($oResults, $oTable);
        }

        /**
         * @param int $iCount
         *
         * @return static
         * @throws DbException
         */
        public static function getRandom(int $iCount = 1) {
            if (!$iCount) {
                return new static();
            }

            $oTable   = static::getTable();
            $oSQL     = SQLBuilder::select($oTable);
            $sSQL     = $oSQL->toString() . ' ORDER BY RAND() ' . SQL::limit($iCount)->toSQL();
            $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, $sSQL);

            if (!$oResults) {
                return new static();
            }

            return self::fromResults($oResults, $oTable);
        }

        /**
         * @param string|null $sSearch
         *
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
            }

            $sSearch = (string) preg_replace('/\s+/', ' ', $sSearch);
            $sSearch = (string) preg_replace('/(\w+)([*:><!]+)"(\w+)/', '"${1}${2}${3}', $sSearch); // Make things like field:"Some Value" into "field: Some Value"
            $aSearch = str_getcsv($sSearch, ' ');

            foreach($aSearch as $sSearchTerm) {
                if ($sSearchTerm === null) {
                    continue;
                }

                if (strpos($sSearchTerm, '>') !== false) {
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
                } else if (strpos($sSearchTerm, ':') !== false) { // This used to be first, but because dates have colons in them and are likely to by > or <, it's best to make this last
                    $aCondition = [];
                    $aSearchTerm  = explode(':', $sSearchTerm);
                    $aCondition['operator'] = ':';
                    $aCondition['field']    = array_shift($aSearchTerm);
                    $aCondition['value']    = implode(':', $aSearchTerm);
                    $aResponse['conditions'][] = $aCondition;
                } else {
                    $aCondition = [];
                    $aCondition['operator'] = '::';
                    $aCondition['value']    = $sSearchTerm;
                    $aResponse['conditions'][] = $aCondition;
                }
            }

            return $aResponse;
        }

        /**
         * @param string $sSort
         *
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

            foreach($aSort as $sSortField) {
                if (strpos($sSortField, '.')) {
                    $aSplit = explode('.', $sSortField);
                    if (count($aSplit) === 2) {
                        $aResponse[] = [
                            'table' => $aSplit[0],
                            'field' => $aSplit[1]
                        ];
                    }
                } else {
                    $aResponse[] = [
                        'field' => $sSortField
                    ];
                }
            }

            return $aResponse;
        }

        /**
         * @param int|null    $iPage
         * @param int|null    $iPer
         * @param array|null  $aSearch
         * @param array|null  $aSort
         * @param string|null $sSyncDate
         *
         * @return static
         * @throws DbException
         */
        public static function getForCMS(?int $iPage = 1, ?int $iPer = 100, ?array $aSearch = null, ?array $aSort = null, ?string $sSyncDate = null) {
            $oQuery = self::getQueryForCMS($aSearch, $iPage, $iPer, $aSort, $sSyncDate);
            $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, $oQuery);

            if(!$oResults) {
                return new static();
            }

            return static::fromResults($oResults, static::getTable());
        }

        /**
         * @param array|null $aSearch
         *
         * @return int
         * @throws DbException
         */
        public static function countForCMS(?array $aSearch = null): int {
            if (!$aSearch) {
                $oTable   = static::getTable();
                $sTable   = $oTable->getTitle();
                $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, "SHOW TABLE STATUS LIKE '$sTable'");
                if (!$oResults) {
                    return 0;
                }
                return (int) $oResults->fetchObject()->Rows;
            }

            $oQuery = self::getQueryForCMS($aSearch);
            $oQuery->setType(SQLBuilder::TYPE_COUNT);
            $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, $oQuery);
            if (!$oResults) {
                return 0;
            }
            return (int) $oResults->fetchObject()->row_count;
        }

        /**
         * @param array|null  $aSearch
         * @param int|null    $iPage
         * @param int|null    $iPer
         * @param array|null  $aSort
         * @param string|null $sSyncDate
         * @param array       $aFields
         *
         * @return SQLBuilder
         */
        protected static function getQueryForCMS(?array $aSearch = null, ?int $iPage = null, ?int $iPer = null, ?array $aSort = null, ?string $sSyncDate = null, array $aFields = []): SQLBuilder {
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
                    $oSearchField = null;

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

                    /** @var Field $oSearchField */

                    switch($aCondition['operator']) {
                        case '::':
                            // Search all Searchable fields - we should be checking if this is a general search (no colons or >'s or anything) and then only do this in that case
                            foreach ($oTable->getFields() as $oField) {
                                if ($oField instanceof Field\Number) {
                                    $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                } else if ($oField instanceof Field\Date) {
                                    try {
                                        $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                    } catch (Throwable $e) {
                                        Log::w('Tables.getQueryForCMS.InvalidValueForDateSearch');
                                    }
                                } else if ($oField instanceof Field\Enum) {
                                    if ($oField->isValue($sSearchValue)) {
                                        $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                    }
                                } else if ($oField instanceof Field\Text && strpos($sSearchValue, self::WILDCARD) !== false) {
                                    $aSQLConditions[] = SQL::like($oField, str_replace(self::WILDCARD, '%', $sSearchValue));
                                } else {
                                    $aSQLConditions[] = SQL::eq($oField, $sSearchValue);
                                }
                            }
                            break;

                        case ':':
                            if ($sSearchValue === 'null') {
                                $aSQLConditions[] = SQL::nul($oSearchField);
                            } else if ($oSearchField instanceof Field\Date) {
                                try {
                                    $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                                } catch (Throwable $e) {
                                    Log::w('Tables.getQueryForCMS.InvalidValueForDateSearch');
                                }
                            } else if ($oSearchField instanceof Field\Enum) {
                                if ($oSearchField->isValue($sSearchValue)) {
                                    $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                                }
                            } else if ($oSearchField instanceof Field\Number) {
                                if (strpos($sSearchValue, ',') !== false) {
                                    $aSQLConditions[] = SQL::in($oSearchField, explode(',', $sSearchValue));
                                } else {
                                    $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                                }
                            } else if (strpos($sSearchValue, self::WILDCARD) !== false) {
                                $aSQLConditions[] = SQL::like($oSearchField, str_replace(self::WILDCARD, '%', $sSearchValue));
                            } else if (strpos($sSearchValue, ',') !== false) {
                                $aSQLConditions[] = SQL::in($oSearchField, explode(',', $sSearchValue));
                            } else {
                                $aSQLConditions[] = SQL::eq($oSearchField, $sSearchValue);
                            }
                            break;

                        case '!':
                            if ($sSearchValue === 'null') {
                                $aSQLConditions[] = SQL::nnul($oSearchField);
                            } else if ($oSearchField instanceof Field\Date) {
                                $aSQLConditions[] = SQL::neq($oSearchField, $sSearchValue);
                            } else if ($oSearchField instanceof Field\Number
                                   ||  $oSearchField instanceof Field\Enum) {
                                if (strpos($sSearchValue, ',') !== false) {
                                    $aSQLConditions[] = SQL::nin($oSearchField, explode(',', $sSearchValue));
                                } else {
                                    $aSQLConditions[] = SQL::neq($oSearchField, $sSearchValue);
                                }
                            } else if (strpos($sSearchValue, self::WILDCARD) !== false) {
                                $aSQLConditions[] = SQL::nlike($oSearchField, str_replace(self::WILDCARD, '%', $sSearchValue));
                            } else if (strpos($sSearchValue, ',') !== false) {
                                $aSQLConditions[] = SQL::nin($oSearchField, explode(',', $sSearchValue));
                            } else {
                                $aSQLConditions[] = SQL::neq($oSearchField, $sSearchValue);
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
                    if ($aSearch['type'] === 'AND') {
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

                        $oSortTable = new $sSortTableClass();

                        assert($oSortTable instanceof Table, new Exceptions\TablesInvalidTableException($sSortTableClass . ' is not a valid Table'));

                        $oSortReference = $oSortTable->getFieldThatReferencesTable($oTable);
                        if ($oSortReference instanceof Field) {
                            try {
                                // The SortBy Field is in a table that references our Primary Table
                                // Join from the Referenced Primary Table Field to the Sort Table Referencing Field
                                $sReferenceField = $oSortReference->referenceField();
                                $oQuery->fields($oTable); // Setting Primary Table fields to ensure joined fields aren't the only ones returned
                                $oQuery->join($oTable->$sReferenceField, $oSortReference);
                            } catch (Throwable $e) {
                                Log::ex('Tables.getForCMS.Sort.Foreign.JoinConditionError', $e);
                            }
                        } else {
                            $oSortReference = $oTable->getFieldThatReferencesTable($oSortTable);

                            assert($oSortReference instanceof Field, new Exceptions\TablesInvalidReferenceException("Cannot Associate [Table] with [Referenced Table] "));

                            try {
                                // The SortBy Field is in a table that our Primary Table references
                                // Join from the Referencing Primary Table Field to the Referenced Sort Table Field Base Table Field
                                $sReferenceField = $oSortReference->referenceField();
                                $oQuery->fields($oTable); // Setting Primary Table fields to ensure joined fields aren't the only ones returned
                                $oQuery->join($oSortReference, $oSortTable->$sReferenceField);
                            } catch (Throwable $e) {
                                Log::ex('Tables.getForCMS.Sort.Foreign.JoinConditionError', $e);
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

            if ($sSyncDate && $oTable instanceof ModifiedDateColumn) {
                $oQuery->also(
                    SQL::gte($oTable->getModifiedDateField(), $sSyncDate)
                );
            }

            return $oQuery;
        }

        /**
         * @return int
         * @throws DbException
         */
        public static function total(): int {
            $oTable   = static::getTable();
            $oResults = static::Db()->namedQuery(static::class . '.' . __FUNCTION__, SQLBuilder::count($oTable));

            if (!$oResults) {
                return 0;
            }

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
         *
         * @return static
         * @throws DbException
         */
        public static function createAndUpdateFromMap(Table $oTable, array $aData, array $aMap) {
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
         *
         * @return static
         * @throws DbException
         */
        public static function createAndUpdate(Table $oTable, array $aData) {
            $aOutput = new static;
            foreach($aData as $aDatum) {
                /** @var Table $sTable */
                $sTable = get_class($oTable);
                $aOutput->append($sTable::createAndUpdate($aDatum));
            }

            return $aOutput;
        }

        /**
         * @param PDOStatement|null $oResults
         * @param mixed             ...$aTables
         *
         * @return static
         */
        public static function fromResults(?PDOStatement $oResults, ...$aTables) {
            if ($oResults === null) {
                return new static();
            }

            if (count($aTables) > 1) {
                $oOutput = new static;
                while ($oResult = $oResults->fetchObject()) {
                    $aRow = array();
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
            }

            if ($oResults->rowCount() === 0) {
                return new static();
            }

            if (!count($aTables)) {
                return self::fromResultsWithMeta($oResults, static::getTable());
            }

            return self::fromResultsWithMeta($oResults, $aTables[0]);

        }

        /**
         * @param PDOStatement $oResults
         * @param Table        $oTable
         *
         * @return static
         */
        protected static function fromResultsWithMeta(PDOStatement $oResults, Table $oTable) {
            $oOutput        = new static;
            while ($oResult = $oTable->createFromPDOStatement($oResults)) {
                $oOutput->append($oResult);
            }
            return $oOutput;
        }

        /**
         * @param mixed $value
         */
        public function append($value): void {
            $sClass = static::getTable();

            assert($value instanceof $sClass, new Exceptions\TablesException('Cannot Append Table of Type ' . get_class($value) . ' to ' . get_class($this)));

            parent::append($value);
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
            }

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
         * @param string $sKey
         * @param string $sValue
         * @return array
         */
        public function toKeyGroupedValueArray(string $sKey, string $sValue): array {
            $aReturn = [];
            foreach($this as $oTable) {
                $sGroup = $oTable->$sKey->getValue();
                if (!isseT($aReturn[$sGroup])) {
                    $aReturn[$sGroup] = [];
                }

                $aReturn[$sGroup][] = $oTable->$sValue->getValue();
            }

            return $aReturn;
        }

        /**
         * @param string $sKey
         * @return array
         */
        public function toKeyGroupedArray(string $sKey): array {
            $aReturn = [];
            foreach($this as $oTable) {
                $sGroup = $oTable->$sKey->getValue();
                if (!isset($aReturn[$sGroup])) {
                    $aReturn[$sGroup] = [];
                }

                $aReturn[$sGroup][] = $oTable;
            }

            return $aReturn;
        }

        /**
         * @param array $aKeys
         *
         * @return array
         */
        public function toKeysGroupedArray(array $aKeys): array {
            $aReturn = [];
            foreach($this as $oTable) {
                $aGroup = [];
                foreach($aKeys as $sKey) {
                    $aGroup[] = $oTable->$sKey->getValue();
                }

                $sGroup = implode('|', $aGroup);
                if (!isset($aReturn[$sGroup])) {
                    $aReturn[$sGroup] = [];
                }

                $aReturn[$sGroup][] = $oTable;
            }

            return $aReturn;
        }

        /**
         * @param string $sField
         *
         * @return array
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
         *
         * @return Field[]
         */
        public function toFieldArray(string $sField): array {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            assert(array_values((array) $this)[0]->$sField instanceof Field, new Exceptions\TablesInvalidFieldException('Invalid Field Requested'));

            foreach ($this as $oTable) {
                $aReturn[] = $oTable->$sField;
            }

            return $aReturn;
        }

        /**
         * @return Field[]
         */
        public function toPrimaryFieldArray(): array {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            return $this->toFieldArray($this->getOnlyPrimary()->sColumn);
        }

        /**
         * @return Field[]
         */
        public function toPrimaryArray(): array {
            $aReturn = [];

            if (!$this->count()) {
                return $aReturn;
            }

            return $this->toFieldValueArray($this->getOnlyPrimary()->sColumn);
        }

        /**
         * @return Field
         */
        private function getOnlyPrimary(): Field {
            $aPrimary = static::getTable()->getPrimary();

            assert(count($aPrimary) === 1, new Exceptions\TablesMultiplePrimaryException('Can Only get Primary Array of Tables with Single Primary Keys'));

            return $aPrimary[0];
        }

        /**
         * @param string $sKey
         * @return array
         */
        public function toArray($sKey = ''): array {
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
         */
        public function toPrimaryKeyedArray(): array {
            $sPrimary = $this->getOnlyPrimary()->sColumn;
            $aReturn  = [];
            foreach ($this as $oTable) {
                $aReturn[$oTable->$sPrimary->getValue()] = $oTable;
            }
            return $aReturn;
        }

        protected function getCSVFields(): array {
            /** @var Table $oRecord */
            /** @psalm-suppress InvalidScalarArgument */
            $oRecord = $this->offsetGet(0);
            $aFields = [];
            foreach($oRecord->getFields() as $oField) {
                $aFields[] = $oField->toSQLColumnForFields(false);
            }

            return $aFields;
        }

        protected function getCSVHeaders(): array {
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
        public function toCSV(array $aExclude = []): string {
            /** @var Table $oRecord */
            /** @psalm-suppress InvalidScalarArgument */
            $oRecord = $this->offsetGet(0);
            $aHeaders = [];
            foreach($oRecord->getFields() as $oField) {
                if (in_array($oField->sColumn, $aExclude, true)) {
                    continue;
                }

                $aHeaders[] = $oField->toSQLColumnForFields(false);
            }

            $oOutput = fopen('php://temp', 'wb');

            if (!$oOutput) {
                return '';
            }

            fputcsv($oOutput, $aHeaders);

            foreach($this as $oTable) {
                $aValues   = $oTable->toArray();
                $aFiltered = [];

                foreach($aValues as $sKey => $sValue) {
                    if (in_array($sKey, $aExclude, true)) {
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
<?php
    namespace Enobrev;

    use Enobrev\ORM\Field;
    use stdClass;

    class SQLBuilderException extends \Exception {}
    class SQLBuilderMissingTableOrFieldsException extends SQLBuilderException {}
    class SQLBuilderMissingConditionException extends SQLBuilderException {}
    class SQLBuilderPrimaryValuesNotSetException extends SQLBuilderException {}

    class SQLBuilder {
        const TYPE_SELECT = 'SELECT';
        const TYPE_COUNT  = 'SELECT COUNT';
        const TYPE_INSERT = 'INSERT';
        const TYPE_UPDATE = 'UPDATE';
        const TYPE_UPSERT = 'UPSERT';
        const TYPE_DELETE = 'DELETE';

        /** @var string  */
        public $sSQL      = NULL;

        /** @var string  */
        public $sSQLGroup = NULL;

        /** @var string  */
        public $sSQLTable = NULL;

        /** @var string  */
        public $sSQLType  = NULL;

        /** @var bool  */
        private $bStar       = false;

        /** @var ORM\Table */
        private $oTable      = null;

        /** @var ORM\Field[] $aFields */
        private $aFields     = [];

        /** @var ORM\Table[] $aTables */
        private $aTables     = [];

        /** @var ORM\Join[] $aJoins */
        private $aJoins      = [];

        /** @var ORM\Order[] $aOrders */
        private $aOrders     = [];

        /** @var ORM\Group $oGroup */
        private $oGroup      = NULL;

        /** @var ORM\Limit $oLimit */
        private $oLimit      = NULL;

        /** @var ORM\Conditions $oConditions */
        private $oConditions;

        public function __construct($sMethod) {
            $this->setType($sMethod);
            $this->oConditions  = new ORM\Conditions;
        }

        /**
         * @return bool
         */
        public function hasConditions() {
            return $this->oConditions->count() > 0;
        }

        /**
         * @param $sType
         */
        public function setType($sType) {
            $this->sSQLType = $sType;
            $this->sSQL     = null; // Reset SQL so it will be regenerated when using (string) self
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function from(...$aArguments) {
            foreach($aArguments as $oTable) {
                if ($oTable instanceof ORM\Table) {
                    $this->aTables[] = $oTable;

                    if (count($this->aTables) == 1) {
                        $this->oTable    = $oTable;
                        $this->sSQLTable = $oTable->getTitle();
                    }
                }
            }

            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function either(...$aArguments) {
            $this->oConditions->add(SQL::either(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function also(...$aArguments) {
            $this->oConditions->add(SQL::also(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function eq(...$aArguments) {
            $this->oConditions->add(SQL::eq(...$aArguments));
            return $this;
        }

        /**
         * @param Field $oField
         * @param mixed $mValue
         * @return SQLBuilder
         * @throws ORM\ConditionsNonConditionException
         */
        public function eq_in(Field $oField, $mValue) {
            if (strpos($mValue, ',')) {
                $this->oConditions->add(SQL::in($oField, explode(',', $mValue)));
            } else {
                $this->oConditions->add(SQL::eq($oField, $mValue));
            }
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function neq(...$aArguments) {
            $this->oConditions->add(SQL::neq(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function lt(...$aArguments) {
            $this->oConditions->add(SQL::lt(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function gt(...$aArguments) {
            $this->oConditions->add(SQL::gt(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function gte(...$aArguments) {
            $this->oConditions->add(SQL::gte(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function like(...$aArguments) {
            $this->oConditions->add(SQL::like(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function nlike(...$aArguments) {
            $this->oConditions->add(SQL::nlike(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function nul(...$aArguments) {
            $this->oConditions->add(SQL::nul(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function nnul(...$aArguments) {
            $this->oConditions->add(SQL::nnul(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function in(...$aArguments) {
            $this->oConditions->add(SQL::in(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function nin(...$aArguments) {
            $this->oConditions->add(SQL::nin(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         * @return SQLBuilder
         */
        public function between(...$aArguments) {
            $this->oConditions->add(SQL::between(...$aArguments));
            return $this;
        }

        /**
         * @param ORM\Field $oFrom
         * @param ORM\Field $oTo
         * @return SQLBuilder
         */
        public function join(ORM\Field $oFrom, ORM\Field $oTo) {
            $this->aJoins[] = SQL::join($oFrom, $oTo);
            return $this;
        }

        /**
         * @param int|null $iStart
         * @param int|null $iOffset
         * @return SQLBuilder
         */
        public function limit($iStart = null, $iOffset = null) {
            $this->oLimit = SQL::limit($iStart, $iOffset);
            return $this;
        }

        /**
         * @param ORM\Field[]  ...$aFields
         * @return SQLBuilder
         */
        public function group(...$aFields) {
            $this->oGroup = SQL::group(...$aFields);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return SQLBuilder
         */
        public function desc(ORM\Field $oField, Array $aValues = array()) {
            $this->aOrders[] = SQL::desc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return SQLBuilder
         */
        public function asc(ORM\Field $oField, Array $aValues = array()) {
            $this->aOrders[] = SQL::asc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return SQLBuilder
         */
        public function byfield(ORM\Field $oField, Array $aValues = array()) {
            $this->aOrders[] = SQL::byfield($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @return SQLBuilder
         */
        public function field(ORM\Field $oField) {
            $this->aFields[] = $oField;

            return $this;
        }

        /**
         * @param ORM\Table|ORM\Field[] $aFields
         * @return SQLBuilder
         */
        public function fields(...$aFields) {
            if (is_array($aFields) && count($aFields) && $aFields[0] instanceof ORM\Table) {
                /** @var ORM\Table $aFields */
                $aFields = $aFields[0];
                $aFields = $aFields->getFields();
            }

            foreach ($aFields as $oField) {
                $this->field($oField);
            }

            return $this;
        }

        /**
         * @param ORM\Table $oTable
         * @param ORM\Field[] ...$aFields
         * @return SQLBuilder
         */
        public static function select(ORM\Table $oTable, ...$aFields) {
            $oBuilder = new self(self::TYPE_SELECT);
            $oBuilder->from($oTable);
            $oBuilder->fields(...$aFields);
            return $oBuilder;
        }

        public static function count(ORM\Table $oTable, ...$aFields) {
            $oBuilder = new self(self::TYPE_COUNT);
            $oBuilder->from($oTable);
            $oBuilder->fields(...$aFields);
            return $oBuilder;
        }

        public static function insert(ORM\Table $oTable) {
            $oBuilder = new self(self::TYPE_INSERT);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        public static function update(ORM\Table $oTable) {
            $oBuilder = new self(self::TYPE_UPDATE);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        public static function upsert(ORM\Table $oTable) {
            $oBuilder = new self(self::TYPE_UPSERT);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        public static function delete(ORM\Table $oTable) {
            $oBuilder = new self(self::TYPE_DELETE);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        public function build() {
            switch($this->sSQLType) {
                case self::TYPE_SELECT: return $this->buildSelect();
                case self::TYPE_COUNT:  return $this->buildCount();
                case self::TYPE_INSERT: return $this->buildInsert();
                case self::TYPE_UPDATE: return $this->buildUpdate();
                case self::TYPE_UPSERT: return $this->buildUpsert();
                case self::TYPE_DELETE: return $this->buildDelete();
            }

            return null;
        }

        /**
         * @return SQLBuilder
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        private function buildSelect() {
            if (count($this->aFields)) {
                foreach($this->aFields as $oField) {
                    $this->aTables[] = $oField->getTable();
                }
            } else if (count($this->aTables)) {
                $this->bStar  = true;
            } else {
                throw new SQLBuilderMissingTableOrFieldsException;
            }

            $aSQL = array(self::TYPE_SELECT);
            if ($this->bStar) {
                $aSQLFields = array('*');

                // Add hex'd aliases
                foreach($this->oTable->getFields() as $oField) {
                    if ($oField instanceof ORM\Field\Hash) { // TODO: `|| $oField instanceof ORM\Field\UUID` -- UUID is no longer binary, but should we add a BinaryUUID, it should be added here
                        $aSQLFields[] = $oField->toSQLColumnForSelect();
                    }
                }

                $aSQL[] = implode(', ', $aSQLFields);
            } else {
                $aSQL[] = self::toSQLColumnsForSelect($this->aFields);
            }

            $aSQL[] = 'FROM';
            $aSQL[] = $this->oTable->getTitle();

            if (count($this->aJoins)) {
                foreach($this->aJoins as $oJoin) {
                    $aSQL[] = $oJoin->toSQL();
                }
            }

            $aSQLLog = $aSQL;
            if ($this->oConditions->count()) {
                $aSQL[] = 'WHERE';
                $aSQL[] = $this->oConditions->toSQL();

                $aSQLLog[] = 'WHERE';
                $aSQLLog[] = $this->oConditions->toSQLLog();
            }

            if ($this->oGroup) {
                $aGroup  = ['GROUP BY', $this->oGroup->toSQL()];
                $aSQL    = array_merge($aSQL, $aGroup);
                $aSQLLog = array_merge($aSQLLog, $aGroup);
            }

            if (count($this->aOrders)) {
                $aOrderSQL = array();
                foreach($this->aOrders as $oOrder) {
                    $aOrderSQL[] = $oOrder->toSQL();
                }

                $aOrder  = ['ORDER BY', implode(', ', $aOrderSQL)];
                $aSQL    = array_merge($aSQL, $aOrder);
                $aSQLLog = array_merge($aSQLLog, $aOrder);
            }

            if ($this->oLimit instanceof ORM\Limit) {
                $aSQL[]    = $this->oLimit->toSQL();
                $aSQLLog[] = $this->oLimit->toSQL();
            }

            $this->sSQL      = implode(' ', $aSQL);
            $this->sSQLGroup = implode(' ', $aSQLLog);

            return $this;
        }

        /**
         * @return SQLBuilder
         */
        private function buildCount() {
            $aSQL     = array(self::TYPE_SELECT);
            $aPrimary = $this->oTable->getPrimary();

            if (count($aPrimary) == 1) {
                $aSQL[] = 'COUNT(' . self::toSQLColumnsForCount($aPrimary) . ') AS row_count';
            } else {
                $aSQL[] = 'COUNT(*) AS row_count';
            }

            $aSQL[] = 'FROM';
            $aSQL[] = $this->oTable->getTitle();

            if (count($this->aJoins)) {
                foreach($this->aJoins as $oJoin) {
                    $aSQL[] = $oJoin->toSQL();
                }
            }

            $aSQLLog = $aSQL;
            if ($this->oConditions->count()) {
                $aSQL[] = 'WHERE';
                $aSQL[] = $this->oConditions->toSQL();

                $aSQLLog[] = 'WHERE';
                $aSQLLog[] = $this->oConditions->toSQLLog();
            }

            if ($this->oGroup) {
                $aGroup  = ['GROUP BY', $this->oGroup->toSQL()];
                $aSQL    = array_merge($aSQL, $aGroup);
                $aSQLLog = array_merge($aSQL, $aGroup);
            }

            $this->sSQL      = implode(' ', $aSQL);
            $this->sSQLGroup = implode(' ', $aSQLLog);

            return $this;
        }

        /**
         * @return SQLBuilder
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        private function buildInsert() {
            if (count($this->aFields) == 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getFields();
                } else {
                    throw new SQLBuilderMissingTableOrFieldsException;
                }
            }

            $this->sSQL      = implode(' ',
                [
                    self::TYPE_INSERT . ' INTO', $this->oTable->getTitle(),
                    '(',
                        self::toSQLColumnsForInsert($this->aFields),
                    ') VALUES (',
                        self::toSQL($this->aFields),
                    ')'
                ]
            );

            $this->sSQLGroup = implode(' ',
                [
                    self::TYPE_INSERT . ' INTO', $this->oTable->getTitle(),
                    '(',
                        self::toSQLColumnsForInsert($this->aFields),
                    ') VALUES (',
                        self::toSQLLog($this->aFields),
                    ')'
                ]
            );

            return $this;
        }

        /**
         * @return SQLBuilder
         * @throws SQLBuilderMissingConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        public function buildUpdate() {
            if (count($this->aFields) == 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getFields();
                } else {
                    throw new SQLBuilderMissingTableOrFieldsException;
                }
            }

            if ($this->oConditions->count() == 0) {
                throw new SQLBuilderMissingConditionException;
            }

            $this->sSQL = implode(' ',
                [
                    self::TYPE_UPDATE, $this->oTable->getTitle(),
                    'SET', self::toSQLUpdate($this->aFields, $this->oTable->oResult),
                    'WHERE', $this->oConditions->toSQL()
                ]
            );

            $this->sSQLGroup = implode(' ',
                [
                    self::TYPE_UPDATE, $this->oTable->getTitle(),
                    'SET', self::toSQLUpdateLog($this->aFields, $this->oTable->oResult),
                    'WHERE', $this->oConditions->toSQLLog()
                ]
            );

            return $this;
        }

        /**
         * @return SQLBuilder
         * @throws SQLBuilderMissingTableOrFieldsException
         * @throws SQLBuilderPrimaryValuesNotSetException
         */
        public function buildUpsert() {
            if (!$this->oTable->primaryHasValue()) {
                throw new SQLBuilderPrimaryValuesNotSetException;
            }

            if (count($this->aFields) == 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getFields();
                } else {
                    throw new SQLBuilderMissingTableOrFieldsException;
                }
            }

            $this->sSQL      = implode(' ',
                [
                    'INSERT INTO', $this->oTable->getTitle(),
                    '(',
                        self::toSQLColumnsForInsert($this->aFields),
                    ') VALUES (',
                        self::toSQL($this->aFields),
                    ') ON DUPLICATE KEY UPDATE',
                        self::toSQLUpdate($this->aFields)
                ]
            );

            $this->sSQLGroup = implode(' ',
                [
                    'INSERT INTO', $this->oTable->getTitle(),
                    '(',
                        self::toSQLColumnsForInsert($this->aFields),
                    ') VALUES (',
                        self::toSQLLog($this->aFields),
                    ') ON DUPLICATE KEY UPDATE',
                        self::toSQLLog($this->aFields),
                    ')'
                ]
            );

            return $this;
        }

        /**
         * @return SQLBuilder
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        public function buildDelete() {
            if ($this->oConditions->count() == 0) {
                if (count($this->aFields) == 0) {
                    throw new SQLBuilderMissingTableOrFieldsException;
                }

                $this->oConditions->add($this->aFields);
            }

            $this->sSQL      = implode(' ', ['DELETE FROM', $this->oTable->getTitle(), 'WHERE', $this->oConditions->toSQL()]);
            $this->sSQLGroup = implode(' ', ['DELETE FROM', $this->oTable->getTitle(), 'WHERE', $this->oConditions->toSQLLog()]);

            return $this;
        }

        /**
         * @param ORM\Field[] $aFields
         * @param bool $bWithTable
         * @return string
         */
        private static function toSQLColumnsForSelect($aFields, $bWithTable = true) {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForSelect($bWithTable);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @param bool $bWithTable
         * @return string
         */
        private static function toSQLColumnsForCount($aFields, $bWithTable = true) {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForCount($bWithTable);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQLColumnsForInsert($aFields) {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForInsert();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQL($aFields) {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQLLog($aFields) {
            $aColumns = array();
            foreach($aFields as $oField) {
                $aColumns[] = get_class($oField);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpdate(Array $aFields, stdClass $oResult = NULL) {
            $aColumns = array();

            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                $aColumns[] = $oField->toSQLColumn(false) . ' = ' . $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] array $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpdateLog(Array $aFields, stdClass $oResult = NULL) {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                /** @var ORM\Field $oField */
                $aColumns[] = $oField->toSQLColumn(false) . ' = ' . get_class($oField);
            }

            return implode(', ', $aColumns);
        }

        public function __toString() {
            if ($this->sSQL === NULL) {
                try {
                    $this->build();
                } catch (SQLBuilderMissingTableOrFieldsException $e) {
                    if (defined('PHPUNIT_ENOBREV_ORM_TESTSUITE') === true) {
                        dbg('SQLBuilderMissingTableOrFieldsException');
                    } else {
                        Log::e('ORM.SQLBuilder.__toString.error', [
                            'error' => [
                                'type'    => get_class($e),
                                'code'    => $e->getCode(),
                                'message' => $e->getMessage(),
                                'trace'   => json_encode($e->getTrace())
                            ]
                        ]);
                    }

                    return '';
                }
            }

            return $this->sSQL;
        }
    }
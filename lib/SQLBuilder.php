<?php
    namespace Enobrev;

    use Enobrev\ORM\Field;
    use Exception;
    use ReflectionClass;
    use stdClass;

    class SQLBuilderException extends Exception {}
    class SQLBuilderMissingTableOrFieldsException extends SQLBuilderException {}
    class SQLBuilderMissingConditionException extends SQLBuilderException {}
    class SQLBuilderPrimaryValuesNotSetException extends SQLBuilderException {}

    class SQLBuilder {
        public const TYPE_SELECT = 'SELECT';
        public const TYPE_COUNT  = 'SELECT COUNT';
        public const TYPE_INSERT = 'INSERT';
        public const TYPE_UPDATE = 'UPDATE';
        public const TYPE_UPSERT = 'UPSERT';
        public const TYPE_DELETE = 'DELETE';

        /** @var null|string  */
        public $sSQL;

        /** @var string  */
        public $sSQLGroup;

        /** @var string  */
        public $sSQLTable;

        /** @var string  */
        public $sSQLType;

        /** @var string  */
        public $sSelectFieldExtra;

        /** @var bool  */
        private $bStar       = false;

        /** @var ORM\Table */
        private $oTable;

        /** @var ORM\Field[] $aFields */
        private $aFields     = [];

        /** @var ORM\Table[] $aTables */
        private $aTables     = [];

        /** @var ORM\Join[] $aJoins */
        private $aJoins      = [];

        /** @var ORM\Order[] $aOrders */
        private $aOrders     = [];

        /** @var ORM\Group $oGroup */
        private $oGroup;

        /** @var ORM\Limit $oLimit */
        private $oLimit;

        /** @var ORM\Conditions $oConditions */
        private $oConditions;

        public function __clone() {
            if ($this->oTable) {
                $this->oTable = clone $this->oTable;
            }

            if (count($this->aFields)) {
                foreach($this->aFields as $iIndex => $oField) {
                    $this->aFields[$iIndex] = clone $oField;
                }
            }

            if (count($this->aTables)) {
                foreach($this->aTables as $iIndex => $oTable) {
                    $this->aTables[$iIndex] = clone $oTable;
                }
            }

            if (count($this->aJoins)) {
                foreach($this->aJoins as $iIndex => $oJoin) {
                    $this->aJoins[$iIndex] = clone $oJoin;
                }
            }

            if (count($this->aOrders)) {
                foreach($this->aOrders as $iIndex => $oOrder) {
                    $this->aOrders[$iIndex] = clone $oOrder;
                }
            }

            if ($this->oGroup) {
                $this->oGroup = clone $this->oGroup;
            }

            if ($this->oLimit) {
                $this->oLimit = clone $this->oLimit;
            }

            if ($this->oConditions) {
                $this->oConditions = clone $this->oConditions;
            }
        }

        public function __construct(string $sMethod) {
            $this->setType($sMethod);
            $this->oConditions  = new ORM\Conditions;
        }

        /**
         * @return bool
         */
        public function hasConditions(): bool {
            return $this->oConditions->count() > 0;
        }

        /**
         * @param $sType
         */
        public function setType(string $sType): void {
            $this->sSQLType = $sType;
            $this->sSQL     = null; // Reset SQL so it will be regenerated when using (string) self
        }

        /**
         * @param ORM\Table|array ...$aArguments
         * @return $this
         */
        public function from(...$aArguments): self {
            foreach($aArguments as $oTable) {
                if ($oTable instanceof ORM\Table) {
                    $this->aTables[] = $oTable;

                    if (count($this->aTables) === 1) {
                        $this->oTable    = $oTable;
                        $this->sSQLTable = (string) $oTable->getTitle();
                    }
                }
            }

            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function either(...$aArguments): self {
            $this->oConditions->add(SQL::either(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function also(...$aArguments): self {
            $this->oConditions->add(SQL::also(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function eq(...$aArguments): self {
            $this->oConditions->add(SQL::eq(...$aArguments));
            return $this;
        }

        /**
         * @param Field $oField
         * @param mixed $mValue
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function eq_in(Field $oField, $mValue): self {
            /** @psalm-suppress InvalidArgument */
            if (is_array($mValue)) {
                $this->oConditions->add(SQL::in($oField, $mValue));
            } else if (is_string($mValue) && strpos($mValue, ',')) {
                $this->oConditions->add(SQL::in($oField, explode(',', $mValue)));
            } else {
                $this->oConditions->add(SQL::eq($oField, $mValue));
            }
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function neq(...$aArguments): self {
            $this->oConditions->add(SQL::neq(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function lt(...$aArguments): self {
            $this->oConditions->add(SQL::lt(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function gt(...$aArguments): self {
            $this->oConditions->add(SQL::gt(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function lte(...$aArguments): self {
            $this->oConditions->add(SQL::lte(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function gte(...$aArguments): self {
            $this->oConditions->add(SQL::gte(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function like(...$aArguments): self {
            $this->oConditions->add(SQL::like(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function nlike(...$aArguments): self {
            $this->oConditions->add(SQL::nlike(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function nul(...$aArguments): self {
            $this->oConditions->add(SQL::nul(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function nnul(...$aArguments): self {
            $this->oConditions->add(SQL::nnul(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function in(...$aArguments): self {
            $this->oConditions->add(SQL::in(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function nin(...$aArguments): self {
            $this->oConditions->add(SQL::nin(...$aArguments));
            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function between(...$aArguments): self {
            $this->oConditions->add(SQL::between(...$aArguments));
            return $this;
        }

        /**
         * @param ORM\Field                         $oFrom
         * @param ORM\Field                         $oTo
         * @param ORM\Condition|ORM\Conditions|null $oConditions
         *
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         */
        public function join(ORM\Field $oFrom, ORM\Field $oTo, $oConditions = null): self {
            $this->aJoins[] = SQL::join($oFrom, $oTo, $oConditions);
            return $this;
        }

        /**
         * @param int|null $iStart
         * @param int|null $iOffset
         * @return $this
         */
        public function limit($iStart = null, $iOffset = null): self {
            $this->oLimit = SQL::limit($iStart, $iOffset);
            return $this;
        }

        /**
         * @param ORM\Field[]  ...$aFields
         * @return $this
         */
        public function group(...$aFields): self {
            $this->oGroup = SQL::group(...$aFields);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return $this
         */
        public function desc(ORM\Field $oField, Array $aValues = array()): self {
            $this->aOrders[] = SQL::desc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return $this
         */
        public function asc(ORM\Field $oField, Array $aValues = array()): self {
            $this->aOrders[] = SQL::asc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return $this
         */
        public function byfield(ORM\Field $oField, Array $aValues = array()): self {
            $this->aOrders[] = SQL::byfield($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @return $this
         */
        public function field(ORM\Field $oField): self {
            $this->aFields[] = $oField;

            return $this;
        }

        /**
         * @param ORM\Table|ORM\Field[] $aFields
         * @return $this
         * @psalm-suppress MismatchingDocblockParamType
         */
        public function fields(...$aFields): self {
            if (is_array($aFields) && count($aFields) && $aFields[0] instanceof ORM\Table) {
                /** @var ORM\Table $oTable */
                $oTable  = $aFields[0];
                $aFields = $oTable->getFields();
            }

            /** @var ORM\Field $oField */
            /** @psalm-suppress RawObjectIteration */
            foreach ($aFields as $oField) {
                $this->field($oField);
            }

            return $this;
        }

        /**
         * @param string $sExtra
         * @return $this
         */
        public function selectFieldsExtra(string $sExtra): self {
            $this->sSelectFieldExtra = $sExtra;
            return $this;
        }

        /**
         * @param ORM\Table   $oTable
         * @param ORM\Field[] ...$aFields
         * @return $this
         */
        public static function select(ORM\Table $oTable, ...$aFields): self {
            $oBuilder = new self(self::TYPE_SELECT);
            $oBuilder->from($oTable);
            $oBuilder->fields(...$aFields);
            return $oBuilder;
        }

        /**
         * @param ORM\Table   $oTable
         * @param ORM\Field[] ...$aFields
         * @return $this
         */
        public static function count(ORM\Table $oTable, ...$aFields): self {
            $oBuilder = new self(self::TYPE_COUNT);
            $oBuilder->from($oTable);
            $oBuilder->fields(...$aFields);
            return $oBuilder;
        }

        /**
         * @param ORM\Table   $oTable
         * @return $this
         */
        public static function insert(ORM\Table $oTable): self {
            $oBuilder = new self(self::TYPE_INSERT);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        /**
         * @param ORM\Table $oTable
         * @return $this
         */
        public static function update(ORM\Table $oTable): self {
            $oBuilder = new self(self::TYPE_UPDATE);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        /**
         * @param ORM\Table $oTable
         * @return $this
         */
        public static function upsert(ORM\Table $oTable): self {
            $oBuilder = new self(self::TYPE_UPSERT);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        /**
         * @param ORM\Table $oTable
         * @return $this
         */
        public static function delete(ORM\Table $oTable): self {
            $oBuilder = new self(self::TYPE_DELETE);
            $oBuilder->from($oTable);
            return $oBuilder;
        }

        /**
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLBuilderException
         * @throws SQLBuilderMissingConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         * @throws SQLBuilderPrimaryValuesNotSetException
         */
        public function build(): self {
            switch($this->sSQLType) {
                case self::TYPE_SELECT: return $this->buildSelect();
                case self::TYPE_COUNT:  return $this->buildCount();
                case self::TYPE_INSERT: return $this->buildInsert();
                case self::TYPE_UPDATE: return $this->buildUpdate();
                case self::TYPE_UPSERT: return $this->buildUpsert();
                case self::TYPE_DELETE: return $this->buildDelete();
            }

            throw new SQLBuilderException('Invalid SQL Builder Type');
        }

        /**
         * @return $this
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        private function buildSelect(): self {
            if (count($this->aFields)) {
                foreach($this->aFields as $oField) {
                    $sTable = $oField->getTable();
                    if ($sTable) {
                        $this->aTables[] = $sTable;
                    }
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

                if ($this->sSelectFieldExtra) {
                    $aSQLFields[] = trim($this->sSelectFieldExtra, ',');
                }

                $aSQL[] = implode(', ', $aSQLFields);
            } else {
                $aSQL[] = self::toSQLColumnsForSelect($this->aFields);

                if ($this->sSelectFieldExtra) {
                    $aSQL[] = ', ' . trim($this->sSelectFieldExtra, ',');
                }
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
         * @return $this
         */
        private function buildCount(): self {
            $aSQL     = array(self::TYPE_SELECT);
            $aPrimary = $this->oTable->getPrimary();

            if (count($aPrimary) === 1) {
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
         * @return $this
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        private function buildInsert(): self {
            if (count($this->aFields) === 0) {
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
         * @return $this
         * @throws SQLBuilderMissingConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        public function buildUpdate(): self {
            if (count($this->aFields) === 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getFields();
                } else {
                    throw new SQLBuilderMissingTableOrFieldsException;
                }
            }

            if ($this->oConditions->count() === 0) {
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
         * @return $this
         * @throws SQLBuilderMissingTableOrFieldsException
         * @throws SQLBuilderPrimaryValuesNotSetException
         */
        public function buildUpsert(): self {
            if (!$this->oTable->primaryHasValue()) {
                throw new SQLBuilderPrimaryValuesNotSetException;
            }

            if (count($this->aFields) === 0) {
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
                        self::toSQLUpsert($this->aFields)
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
         * @return $this
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         */
        public function buildDelete(): self {
            if ($this->oConditions->count() === 0) {
                if (count($this->aFields) === 0) {
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
        private static function toSQLColumnsForSelect($aFields, $bWithTable = true): string {
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
        private static function toSQLColumnsForCount($aFields, $bWithTable = true): string {
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
        private static function toSQLColumnsForInsert($aFields): string {
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
        private static function toSQL($aFields): string {
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
        private static function toSQLLog($aFields): string {
            $aColumns = array();
            foreach($aFields as $oField) {
                $aColumns[] = (new ReflectionClass($oField))->getShortName();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpdate(Array $aFields, stdClass $oResult = NULL): string {
            $aColumns = array();

            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    $sColumn = $oField->sColumn;
                    if (property_exists($oResult, $sColumn) && $oField->is($oResult->$sColumn)) {
                        continue;
                    }
                }

                $aColumns[] = $oField->toSQLColumn(false) . ' = ' . $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpsert(Array $aFields, stdClass $oResult = NULL): string {
            $aColumns = array();

            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    $sColumn = $oField->sColumn;
                    if (property_exists($oResult, $sColumn) && $oField->is($oResult->$sColumn)) {
                        continue;
                    }
                }

                $aColumns[] = $oField->toSQLColumn(false) . ' = VALUES(' . $oField->toSQLColumn(false) . ')';
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] array $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpdateLog(Array $aFields, stdClass $oResult = NULL): string {
            $aColumns = array();

            /** @var ORM\Field $oField */
            foreach($aFields as $oField) {
                if (($oResult !== null) &&
                    property_exists($oResult, $oField->sColumn) &&
                    $oField->is($oResult->{$oField->sColumn})) {
                        continue;
                    }

                /** @var ORM\Field $oField */
                $aColumns[] = $oField->toSQLColumn(false) . ' = ' . (new ReflectionClass($oTable))->getShortName($oField);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @return string
         * @throws ORM\ConditionInvalidTypeException
         * @throws ORM\ConditionMissingBetweenValueException
         * @throws ORM\ConditionMissingFieldException
         * @throws ORM\ConditionMissingInValueException
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLBuilderException
         * @throws SQLBuilderMissingConditionException
         * @throws SQLBuilderMissingTableOrFieldsException
         * @throws SQLBuilderPrimaryValuesNotSetException
         */
        public function toString():string {
            if ($this->sSQL === null) {
                $this->build();
            }

            if ($this->sSQL === null) {
                return '';
            }

            return $this->sSQL;
        }

        /**
         * @return string
         */
        public function __toString() {
            try {
                return $this->toString();
            } catch (Exception $e) {
                Log::e('ORM.SQLBuilder.__toString.error', [
                    '--error' => [
                        'type'    => get_class($e),
                        'code'    => $e->getCode(),
                        'message' => $e->getMessage(),
                        'trace'   => json_encode($e->getTrace())
                    ]
                ]);

                return '';
            }
        }
    }
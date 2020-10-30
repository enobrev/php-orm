<?php
    namespace Enobrev;

    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\ConditionFactory;
    use Enobrev\ORM\Conditions;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Group;
    use Enobrev\ORM\Limit;
    use Enobrev\ORM\Table;
    use Exception;
    use stdClass;

    class SQLBuilder {
        public const TYPE_SELECT = 'SELECT';
        public const TYPE_COUNT  = 'SELECT COUNT';
        public const TYPE_INSERT = 'INSERT';
        public const TYPE_UPDATE = 'UPDATE';
        public const TYPE_UPSERT = 'UPSERT';
        public const TYPE_DELETE = 'DELETE';
        private const TYPES = [
            self::TYPE_SELECT,
            self::TYPE_COUNT,
            self::TYPE_INSERT,
            self::TYPE_UPDATE,
            self::TYPE_UPSERT,
            self::TYPE_DELETE
        ];

        public ?string $sSQL = null;

        public ?string $sSQLGroup = null;

        public ?string $sSQLTable = null;

        public ?string $sSQLType = null;

        public ?string $sSelectFieldExtra = null;

        private bool $bStar       = false;

        private ?Table $oTable;

        /** @var ORM\Field[] */
        private array $aFields = [];

        /** @var ORM\Table[] */
        private array $aTables = [];

        /** @var ORM\Join[] */
        private array $aJoins  = [];

        /** @var ORM\Order[] */
        private array $aOrders = [];

        private ?Group $oGroup = null;

        private ?Limit $oLimit = null;

        private ?Conditions $oConditions = null;

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

        public function hasConditions(): bool {
            return $this->oConditions->count() > 0;
        }

        public function setType(string $sType): void {
            assert(in_array($sType, self::TYPES));

            $this->sSQLType = $sType;
            $this->sSQL     = null; // Reset SQL so it will be regenerated when using (string) self
        }

        /**
         * @param Table $oTable
         *
         * @return $this
         */
        public function from(Table $oTable): self {
            $this->aTables[] = $oTable;

            if (count($this->aTables) === 1) {
                $this->oTable    = $oTable;
                $this->sSQLTable = (string) $oTable->getTitle();
            }

            return $this;
        }

        /**
         * @param array ...$aArguments
         *
         * @return $this
         */

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return $this
         */
        public function either(...$aArguments): self {
            $this->oConditions->add(SQL::either(...$aArguments));
            return $this;
        }

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return $this
         */
        public function also(...$aArguments): self {
            $this->oConditions->add(SQL::also(...$aArguments));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function eq(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::eq($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field             $oField
         * @param Field|mixed|array $oFieldOrValue
         *
         * @return $this
         */
        public function eq_in(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            if (is_array($oFieldOrValue)) {
                return $this->in($oField, $oFieldOrValue);
            }

            if (is_string($oFieldOrValue) && strpos($oFieldOrValue, ',') !== false) {
                return $this->in($oField, explode(', ', $oFieldOrValue));
            }

            return $this->eq($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function neq(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::neq($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function like(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::like($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function nlike(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::nlike($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function gt(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::gt($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function gte(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::gte($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function lt(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::lt($oField, $oFieldOrValue));
            return $this;
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return $this
         */
        public function lte(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::lte($oField, $oFieldOrValue));
            return $this;
        }



        public function nul(Field $oField): self {
            $this->oConditions->add(SQL::nul($oField));
            return $this;
        }

        public function nnul(Field $oField): self {
            $this->oConditions->add(SQL::nnul($oField));
            return $this;
        }

        public function in(Field $oField, array $aValues): self {
            $this->oConditions->add(SQL::in($oField, $aValues));
            return $this;
        }

        public function nin(Field $oField, array $aValues): self {
            $this->oConditions->add(SQL::nin($oField, $aValues));
            return $this;
        }
        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return $this
         */
        public function between(Field $oField, $mLow = ConditionFactory::NOT_SET, $mHigh = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::between($oField, $mLow, $mHigh));
            return $this;
        }

        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return $this
         */
        public function nbetween(Field $oField, $mLow = ConditionFactory::NOT_SET, $mHigh = ConditionFactory::NOT_SET): self {
            $this->oConditions->add(SQL::nbetween($oField, $mLow, $mHigh));
            return $this;
        }

        /**
         * @param Field $oFrom
         * @param Field $oTo
         * @param ConditionInterface|Conditions|null $oConditions
         *
         * @return $this
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
        public function limit(?int $iStart = null, ?int $iOffset = null): self {
            $this->oLimit = SQL::limit($iStart, $iOffset);
            return $this;
        }

        /**
         * @param ORM\Field[] $aFields
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
        public function desc(ORM\Field $oField, array $aValues = []): self {
            $this->aOrders[] = SQL::desc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return $this
         */
        public function asc(ORM\Field $oField, Array $aValues = []): self {
            $this->aOrders[] = SQL::asc($oField, $aValues);
            return $this;
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return $this
         */
        public function byfield(ORM\Field $oField, Array $aValues = []): self {
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
         * @param ORM\Field[] $aFields
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
         * @param ORM\Field[] $aFields
         * @return $this
         */
        public static function count(ORM\Table $oTable, ...$aFields): self {
            $oBuilder = new self(self::TYPE_COUNT);
            $oBuilder->from($oTable);
            $oBuilder->fields(...$aFields);
            return $oBuilder;
        }

        /**
         * @param ORM\Table $oTable
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
         */
        public function build(): self {
            assert(in_array($this->sSQLType, self::TYPES), new ORM\Exceptions\SQLBuilderException('Invalid SQL Builder Type'));

            switch($this->sSQLType) {
                default:
                case self::TYPE_SELECT: return $this->buildSelect();
                case self::TYPE_COUNT:  return $this->buildCount();
                case self::TYPE_INSERT: return $this->buildInsert();
                case self::TYPE_UPDATE: return $this->buildUpdate();
                case self::TYPE_UPSERT: return $this->buildUpsert();
                case self::TYPE_DELETE: return $this->buildDelete();
            }
        }

        /**
         * @return $this
         */
        private function buildSelect(): self {
            assert(count($this->aFields) || count($this->aTables), new ORM\Exceptions\SQLBuilderMissingTableOrFieldsException);

            if (count($this->aFields)) {
                foreach($this->aFields as $oField) {
                    $sTable = $oField->getTable();
                    if ($sTable) {
                        $this->aTables[] = $sTable;
                    }
                }
            } else if (count($this->aTables)) {
                $this->bStar  = true;
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
                $aSQL[] = self::toSQLColumnsForSelect($this->aFields, $this->oGroup);

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
                $aOrderSQL = [];
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
         */
        private function buildInsert(): self {
            assert(count($this->aFields) || count($this->aTables), new ORM\Exceptions\SQLBuilderMissingTableOrFieldsException);

            if (count($this->aFields) === 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getNonGeneratedFields();
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
         */
        public function buildUpdate(): self {
            assert(count($this->aFields) || count($this->aTables), new ORM\Exceptions\SQLBuilderMissingTableOrFieldsException);
            assert($this->oConditions->count() > 0,                new ORM\Exceptions\SQLBuilderMissingConditionException);

            if (count($this->aFields) === 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getNonGeneratedFields();
                }
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
         */
        public function buildUpsert(): self {
            assert(count($this->aFields) || count($this->aTables), new ORM\Exceptions\SQLBuilderMissingTableOrFieldsException);
            assert($this->oTable->primaryHasValue(), new ORM\Exceptions\SQLBuilderPrimaryValuesNotSetException);

            if (count($this->aFields) === 0) {
                if (count($this->aTables)) {
                    $this->aFields = $this->aTables[0]->getNonGeneratedFields();
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
         * @return $this
         */
        public function buildDelete(): self {
            assert($this->oConditions->count() > 0 || count($this->aFields), new ORM\Exceptions\SQLBuilderMissingTableOrFieldsException);

            if ($this->oConditions->count() === 0) {
                $this->oConditions->add($this->aFields);
            }

            $this->sSQL      = implode(' ', ['DELETE FROM', $this->oTable->getTitle(), 'WHERE', $this->oConditions->toSQL()]);
            $this->sSQLGroup = implode(' ', ['DELETE FROM', $this->oTable->getTitle(), 'WHERE', $this->oConditions->toSQLLog()]);

            return $this;
        }

        /**
         * @param ORM\Field[] $aFields
         * @param Group|null  $oGroup
         *
         * @return string
         */
        private static function toSQLColumnsForSelect(array $aFields, ?Group $oGroup = null): string {
            $aColumns = [];

            if ($oGroup instanceof Group) {
                foreach ($aFields as $oField) {
                    $aColumns[] = $oField->toSQLColumnForSelect(true, !$oGroup->hasField($oField));
                }
            } else {
                foreach ($aFields as $oField) {
                    $aColumns[] = $oField->toSQLColumnForSelect(true);
                }
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @param bool        $bWithTable
         *
         * @return string
         */
        private static function toSQLColumnsForCount(array $aFields, $bWithTable = true): string {
            $aColumns = [];

            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForCount($bWithTable);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQLColumnsForInsert(array $aFields): string {
            $aColumns = [];

            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForInsert();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQL(array $aFields): string {
            $aColumns = [];

            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return string
         */
        private static function toSQLLog(array $aFields): string {
            $aColumns = [];
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
        public static function toSQLUpdate(array $aFields, stdClass $oResult = NULL): string {
            $aColumns = [];

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
         * @param ORM\Field[] array $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public static function toSQLUpdateLog(array $aFields, stdClass $oResult = NULL): string {
            $aColumns = [];

            foreach($aFields as $oField) {
                if (($oResult !== null) &&
                    property_exists($oResult, $oField->sColumn) &&
                    $oField->is($oResult->{$oField->sColumn})) {
                        continue;
                    }

                /** @var ORM\Field $oField */
                $aColumns[] = $oField->toSQLColumn(false) . ' = ' . get_class($oField);
            }

            return implode(', ', $aColumns);
        }

        public function toString():string {
            if ($this->sSQL === null) {
                /*
                try {
                */
                    $this->build();
                /*
                } catch (\Exception $e) {
                    dbg($e);
                    throw $e;
                }
                */
            }

            if ($this->sSQL === null) {
                return '';
            }

            return $this->sSQL;
        }

        public function __toString(): string {
            try {
                return $this->toString();
            } catch (Exception $e) {
                Log::ex('ORM.SQLBuilder.__toString.error', $e);

                return '';
            }
        }
    }
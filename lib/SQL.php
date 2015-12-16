<?php
    namespace Enobrev;

    use stdClass;
    use Enobrev\ORM\Condition;
    use Enobrev\ORM\Conditions;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Group;
    use Enobrev\ORM\Join;
    use Enobrev\ORM\Joins;
    use Enobrev\ORM\Limit;
    use Enobrev\ORM\Order;
    use Enobrev\ORM\Table;

    class SQLException extends \Exception {}
    class SQLMissingTableOrFieldsException extends SQLException {}
    class SQLMissingConditionException extends SQLException {}
    class SQLPrimaryValuesNotSetException extends SQLException {}

    class SQL {
        const TYPE_SELECT = 'SELECT';
        const TYPE_INSERT = 'INSERT';
        const TYPE_UPDATE = 'UPDATE';
        const TYPE_DELETE = 'DELETE';

        /** @var string  */
        public $sSQL      = NULL;

        /** @var string  */
        public $sSQLGroup = NULL;

        /** @var string  */
        public $sSQLTable = NULL;

        /** @var string  */
        public $sSQLType  = NULL;

        /**
         * @param array ...$aArguments
         * @return Conditions
         */
        public static function either(...$aArguments) {
            return Conditions::either(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Conditions
         */
        public static function also(...$aArguments) {
            return Conditions::also(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function eq(...$aArguments) {
            return Condition::eq(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function neq(...$aArguments) {
            return Condition::neq(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function lt(...$aArguments) {
            return Condition::lt(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function gt(...$aArguments) {
            return Condition::gt(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function gte(...$aArguments) {
            return Condition::gte(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function like(...$aArguments) {
            return Condition::like(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function nlike(...$aArguments) {
            return Condition::nlike(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function nul(...$aArguments) {
            return Condition::nul(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function nnul(...$aArguments) {
            return Condition::nnul(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function in(...$aArguments) {
            return Condition::in(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function nin(...$aArguments) {
            return Condition::nin(...$aArguments);
        }

        /**
         * @param array ...$aArguments
         * @return Condition
         */
        public static function between(...$aArguments) {
            return Condition::between(...$aArguments);
        }

        /**
         * @param Field $oFrom
         * @param Field $oTo
         * @return Join
         */
        public static function join(Field $oFrom, Field $oTo) {
            return Join::create($oFrom, $oTo);
        }

        /**
         * @param int|null $iStart
         * @param int|null $iOffset
         * @return Limit
         */
        public static function limit($iStart = null, $iOffset = null) {
            return Limit::create($iStart, $iOffset);
        }

        /**
         * @param Field[]  ...$aFields
         * @return Group
         */
        public static function group(...$aFields) {
            return Group::create(...$aFields);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function desc(Field $oField, Array $aValues = array()) {
            return Order::desc($oField, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function asc(Field $oField, Array $aValues = array()) {
            return Order::asc($oField, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function byfield(Field $oField, Array $aValues = array()) {
            return Order::byfield($oField, $aValues);
        }

        /**
         * @param array ...$aArguments
         * @return SQL
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLMissingTableOrFieldsException
         */
        public static function select(...$aArguments) {
            $bStar       = false;

            /** @var Field[] $aFields */
            $aFields     = array();

            /** @var Table[] $aTables */
            $aTables     = array();

            /** @var Join[] $aJoins */
            $aJoins      = array();

            /** @var Order[] $aOrders */
            $aOrders     = array();

            /** @var Limit $oLimit */
            $oLimit      = NULL;

            /** @var Group $oGroup */
            $oGroup      = NULL;

            /** @var Conditions $oConditions */
            $oConditions = new Conditions;

            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Join:
                        $aJoins[] = $mArgument;
                        break;

                    case $mArgument instanceof Joins:
                        foreach($mArgument as $oJoin) {
                            $aJoins[] = $oJoin;
                        }
                        break;

                    case $mArgument instanceof ORM\Order:
                        $aOrders[] = $mArgument;
                        break;

                    case $mArgument instanceof ORM\Group:
                        $oGroup = $mArgument;
                        break;

                    case $mArgument instanceof ORM\Limit:
                        $oLimit = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument */
                        $aTables[] = $mArgument;
                        break;

                    case $mArgument instanceof Conditions:
                    case $mArgument instanceof Condition:
                        $oConditions->add($mArgument);
                        break;

                    case $mArgument instanceof Field:
                        /** @var Field $mArgument */
                        $aFields[] = $mArgument;
                        break;

                    case is_array($mArgument):
                        foreach($mArgument as $oField) {
                            if ($oField instanceof Field) {
                                $aFields[] = $mArgument;
                            }
                        }
                        break;
                }
            }

            if (count($aFields)) {
                foreach($aFields as $oField) {
                    $aTables[] = $oField->getTable();
                }
            } else if (count($aTables)) {
                $bStar  = true;
            } else {
                throw new SQLMissingTableOrFieldsException;
            }

            $aSQL = array(self::TYPE_SELECT);
            if ($bStar) {
                $aSQLFields = array('*');

                // Add hex'd aliases
                /** @var Field $oField*/
                foreach($aTables[0]->getFields() as $oField) {
                    if ($oField instanceof Field\Hash
                    ||  $oField instanceof Field\UUID) {
                        $aSQLFields[] = $oField->toSQLColumnForSelect();
                    }
                }

                $aSQL[] = implode(', ', $aSQLFields);
            } else {
                $aSQL[] = self::toSQLColumnsForSelect($aFields);
            }

            $aSQL[] = 'FROM';
            $aSQL[] = $aTables[0]->getTitle();

            /** @var ORM\Join[] $aJoins */
            if (count($aJoins)) {
                foreach($aJoins as $oJoin) {
                    $aSQL[] = $oJoin->toSQL();
                }
            }

            $aSQLLog = $aSQL;
            if ($oConditions->count()) {
                $aSQL[] = 'WHERE';
                $aSQL[] = $oConditions->toSQL();

                $aSQLLog[] = 'WHERE';
                $aSQLLog[] = $oConditions->toSQLLog();
            }

            if ($oGroup instanceof ORM\Group) {
                $aSQL[] = $oGroup->toSQL();

                $aSQLLog[] = $oGroup->toSQL();
            }

            /** @var ORM\Order[] $aOrders */
            if (count($aOrders)) {
                $aOrderSQL = array();
                foreach($aOrders as $oOrder) {
                    $aOrderSQL[] = $oOrder->toSQL();
                }

                $aSQL[] = 'ORDER BY';
                $aSQL[] = implode(', ', $aOrderSQL);

                $aSQLLog[] = 'ORDER BY';
                $aSQLLog[] = implode(', ', $aOrderSQL);
            }

            if ($oLimit instanceof ORM\Limit) {
                $aSQL[] = $oLimit->toSQL();

                $aSQLLog[] = $oLimit->toSQL();
            }

            $oSQL = new self;
            $oSQL->sSQLType  = self::TYPE_SELECT;
            $oSQL->sSQLTable = $aTables[0]->getTitle();
            $oSQL->sSQL      = implode(' ', $aSQL);
            $oSQL->sSQLGroup = implode(' ', $aSQLLog);

            return $oSQL;
        }

        /**
         * @param array ...$aArguments
         * @return SQL
         * @throws ORM\ConditionsNonConditionException
         */
        public static function count(...$aArguments) {
            $aTables     = array();
            $aJoins      = array();
            $oGroup      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Join:
                        $aJoins[] = $mArgument;
                        break;

                    case $mArgument instanceof Joins:
                        foreach($mArgument as $oJoin) {
                            $aJoins[] = $oJoin;
                        }
                        break;

                    case $mArgument instanceof ORM\Group:
                        $oGroup = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        $aTables[] = $mArgument;
                        break;

                    case $mArgument instanceof Conditions:
                    case $mArgument instanceof Condition:
                        $oConditions->add($mArgument);
                        break;
                }
            }

            /** @var Table $oTable */
            $oTable = $aTables[0];
            $aSQL   = array(self::TYPE_SELECT);

            $aPrimary = $oTable->getPrimary();

            if (count($aPrimary) == 1) {
                $aSQL[] = 'COUNT(' . self::toSQLColumnsForCount($aPrimary) . ') AS row_count';
            } else {
                $aSQL[] = 'COUNT(*) AS row_count';
            }

            $aSQL[] = 'FROM';
            $aSQL[] = $oTable->getTitle();

            /** @var ORM\Join[] $aJoins */
            if (count($aJoins)) {
                foreach($aJoins as $oJoin) {
                    $aSQL[] = $oJoin->toSQL();
                }
            }

            $aSQLLog = $aSQL;
            if ($oConditions->count()) {
                $aSQL[] = 'WHERE';
                $aSQL[] = $oConditions->toSQL();

                $aSQLLog[] = 'WHERE';
                $aSQLLog[] = $oConditions->toSQLLog();
            }

            if ($oGroup instanceof ORM\Group) {
                $aSQL[] = $oGroup->toSQL();
                $aSQLLog[] = $oGroup->toSQL();
            }

            $oSQL = new self;
            $oSQL->sSQLType  = self::TYPE_SELECT;
            $oSQL->sSQLTable = $oTable->getTitle();
            $oSQL->sSQL      = implode(' ', $aSQL);
            $oSQL->sSQLGroup = implode(' ', $aSQLLog);

            return $oSQL;
        }

        /**
         * @param array ...$aArguments
         * @return SQL
         * @throws SQLMissingTableOrFieldsException
         */
        public static function insert(...$aArguments) {
            $aFields     = [];
            $sTable      = NULL;

            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $aFields[] = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $sTable = $mArgument->getTitle();
                        $aFields[] = $mArgument->getFields();
                        break;
                }
            }

            if (count($aFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($sTable === NULL) {
                $sTable = $aFields[0]->sTable;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = self::TYPE_INSERT;
            $oSQL->sSQLTable = $sTable;
            $oSQL->sSQL      = implode(' ',
                array(
                    self::TYPE_INSERT . ' INTO',
                        $sTable,
                    '(',
                        self::toSQLColumnsForInsert($aFields),
                    ') VALUES (',
                        self::toSQL($aFields),
                    ')'
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    self::TYPE_INSERT . ' INTO',
                        $sTable,
                    '(',
                        self::toSQLColumnsForInsert($aFields),
                    ') VALUES (',
                        self::toSQLLog($aFields),
                    ')'
                )
            );

            return $oSQL;
        }

        /**
         * @param array ...$aArguments
         * @return SQL
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLMissingConditionException
         * @throws SQLMissingTableOrFieldsException
         */
        public static function update(...$aArguments) {
            $aFields     = [];
            $oTable      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $aFields[] = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        /** @var Table $oTable  */
                        $oTable = $mArgument;
                        break;

                    case $mArgument instanceof Conditions:
                    case $mArgument instanceof Condition:
                        $oConditions->add($mArgument);
                        break;

                    case is_array($mArgument):
                        foreach($mArgument as $oField) {
                            if ($oField instanceof Field) {
                                $aFields[] = $mArgument;
                            }
                        }
                        break;
                }
            }

            if (count($aFields) == 0) {
                if ($oTable instanceof Table) {
                    $aFields = $oTable->getFields();
                } else {
                    throw new SQLMissingTableOrFieldsException;
                }
            }

            if ($oConditions->count() == 0) {
                throw new SQLMissingConditionException;
            }

            if ($oTable instanceof Table === false) {
                $sTableObject = 'Table_' . $aFields[0]->sTable;

                /** @var Table $oTable */
                $oTable = new $sTableObject;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = self::TYPE_UPDATE;
            $oSQL->sSQLTable = $oTable->getTitle();
            $oSQL->sSQL      = implode(' ',
                array(
                    self::TYPE_UPDATE,
                        $oTable->getTitle(),
                    'SET',
                        self::toSQLUpdate($aFields, $oTable->oResult),
                    'WHERE',
                        $oConditions->toSQL()
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    self::TYPE_UPDATE,
                        $oTable->getTitle(),
                    'SET',
                        self::toSQLUpdateLog($aFields, $oTable->oResult),
                    'WHERE',
                        $oConditions->toSQLLog()
                )
            );

            return $oSQL;
        }

        /**
         * @return SQL
         * @throws SQLPrimaryValuesNotSetException
         * @throws SQLMissingTableOrFieldsException
         */
        public static function upsert(...$aArguments) {
            $aFields     = [];
            $sTable      = null;
            $oTable      = null;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $aFields[] = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $oTable  = $mArgument;
                        $aFields = array_merge($aFields, $mArgument->getFields());
                        break;

                    case is_array($mArgument):
                        foreach($mArgument as $oField) {
                            if ($oField instanceof Field) {
                                $aFields[] = $mArgument;
                            }
                        }
                        break;
                }
            }

            if (count($aFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($oTable instanceof Table === false) {
                $sTableObject = 'Table_' . $aFields[0]->sTable;

                /** @var Table $oTable */
                $oTable = new $sTableObject;
            }

            if (!$oTable->primaryHasValue()) {
                throw new SQLPrimaryValuesNotSetException;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = 'UPSERT';
            $oSQL->sSQLTable = $oTable->getTitle();
            $oSQL->sSQL      = implode(' ',
                array(
                    'INSERT INTO',
                        $oTable->getTitle(),
                    '(',
                        self::toSQLColumnsForInsert($aFields),
                    ') VALUES (',
                        self::toSQL($aFields),
                    ') ON DUPLICATE KEY UPDATE',
                        self::toSQLUpdate($aFields)
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    'INSERT INTO',
                        $oTable->getTitle(),
                        '(',
                            self::toSQLColumnsForInsert($aFields),
                        ') VALUES (',
                            self::toSQLLog($aFields),
                        ')',
                    ') ON DUPLICATE KEY UPDATE',
                        self::toSQLLog($aFields)
                )
            );

            return $oSQL;
        }

        /**
         * @param array ...$aArguments
         * @return SQL
         * @throws ORM\ConditionsNonConditionException
         * @throws SQLMissingTableOrFieldsException
         */
        public static function delete(...$aArguments) {
            $aFields     = [];
            $sTable      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $aFields[] = $mArgument;
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $sTable = $mArgument->getTitle();
                        $aFields = array_merge($aFields, $mArgument->getFields());
                        break;

                    case $mArgument instanceof Conditions:
                    case $mArgument instanceof Condition:
                        $oConditions->add($mArgument);
                        break;

                    case is_array($mArgument):
                        foreach($mArgument as $oField) {
                            if ($oField instanceof Field) {
                                $aFields[] = $mArgument;
                            }
                        }
                        break;
                }
            }

            if (count($aFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($oConditions->count() == 0) {
                $oConditions->add($aFields);
            }

            if ($sTable === NULL) {
                $sTable = $aFields[0]->sTable;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = self::TYPE_DELETE;
            $oSQL->sSQLTable = $sTable;
            $oSQL->sSQL      = implode(' ',
                array(
                    'DELETE FROM',
                        $sTable,
                    'WHERE',
                        $oConditions->toSQL()
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    'DELETE FROM',
                        $sTable,
                    'WHERE',
                        $oConditions->toSQLLog()
                )
            );

            return $oSQL;
        }

        /**
         * @param Field[] $aFields
         * @param bool $bWithTable
         * @return string
         */
        private static function toSQLColumnsForSelect($aFields, $bWithTable = true) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForSelect($bWithTable);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] $aFields
         * @param bool $bWithTable
         * @return string
         */
        private static function toSQLColumnsForCount($aFields, $bWithTable = true) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForCount($bWithTable);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] $aFields
         * @return string
         */
        private static function toSQLColumnsForInsert($aFields) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQLColumnForInsert();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] $aFields
         * @return string
         */
        private static function toSQL($aFields) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                $aColumns[] = $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] $aFields
         * @return string
         */
        private static function toSQLLog($aFields) {
            $aColumns = array();
            foreach($aFields as $oField) {
                /** @var Field $oField */
                $aColumns[] = get_class($oField);
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public function toSQLUpdate(Array $aFields, stdClass $oResult = NULL) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                $aColumns[] = $oField->toSQLColumn() . ' = ' . $oField->toSQL();
            }

            return implode(', ', $aColumns);
        }

        /**
         * @param Field[] array $aFields
         * @param stdClass|null $oResult
         * @return string
         */
        public function toSQLUpdateLog(Array $aFields, stdClass $oResult = NULL) {
            $aColumns = array();

            /** @var Field $oField */
            foreach($aFields as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                /** @var Field $oField */
                $aColumns[] = $oField->toSQLColumn() . ' = ' . get_class($oField);
            }

            return implode(', ', $aColumns);
        }

        public function __toString() {
            return $this->sSQL;
        }
    }

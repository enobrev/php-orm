<?php
    namespace Enobrev;
    
    use Enobrev\ORM\Condition;
    use Enobrev\ORM\Conditions;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Fields;
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

    /**
     * @method static Conditions either()           either(Condition $oCondition)
     * @method static Conditions also()             also(Condition $oCondition)
     * @method static Condition eq()                eq(Field $oField, mixed $mValue)
     * @method static Condition neq()               neq(Field $oField, mixed $mValue)
     * @method static Condition lt()                lt(Field $oField, mixed $mValue)
     * @method static Condition lte()               lte(Field $oField, mixed $mValue)
     * @method static Condition gt()                gt(Field $oField, mixed $mValue)
     * @method static Condition gte()               gte(Field $oField, mixed $mValue)
     * @method static Condition like()              like(Field $oField, mixed $mValue)
     * @method static Condition nlike()             nlike(Field $oField, mixed $mValue)
     * @method static Condition nul()               nul(Field $oField)
     * @method static Condition notnul()            notnul(Field $oField)
     * @method static Condition in()                in(Field $oField, array $aValues)
     * @method static Condition nin()               nin(Field $oField, array $aValues)
     * @method static Condition between()           between(Field $oField, mixed $mMinimum, mixed $mMaximum)
     * @method static Join join()                   join(Field $oFieldFrom, Field $oFieldTo)
     * @method static Limit limit()                 limit(int $iStart, int $iOffset = null)
     * @method static Group group()                 group(Field $oField)
     * @method static Order desc()                  desc(Field $oField)
     * @method static Order asc()                   asc(Field $oField)
     * @method static Order byfield()               byfield(Field $oField, array $aValues)
     */
    class SQL {
        public $sSQL      = NULL;
        public $sSQLGroup = NULL;
        public $sSQLTable = NULL;
        public $sSQLType  = NULL;

        /**
         * Wrapper method for ORM SQL generation
         * @static
         * @param string $sName
         * @param array $aArguments
         * @return Condition
         */
        public static function __callStatic($sName, $aArguments) {
            $sMethod = NULL;

            switch($sName) {
                case 'either':
                case 'also':
                    $sMethod = '\Enobrev\ORM\Conditions::' . $sName;
                    break;

                case 'eq':
                case 'neq':
                case 'lt':
                case 'lte':
                case 'gt':
                case 'gte':
                case 'like':
                case 'nlike':
                case 'nul':
                case 'notnul':
                case 'in':
                case 'nin':
                case 'between':
                    $sMethod = '\Enobrev\ORM\Condition::' . $sName;
                    break;

                case 'join':
                    $sMethod = '\Enobrev\ORM\Join::create';
                    break;

                case 'limit':
                    $sMethod = '\Enobrev\ORM\Limit::create';
                    break;

                case 'group':
                    $sMethod = '\Enobrev\ORM\Group::create';
                    break;

                case 'havinglte':
                case 'havinggte':
                    $sMethod = '\Enobrev\ORM\HavingCondition::' . $sName;
                    break;

                case 'desc':
                case 'asc':
                case 'byfield':
                    $sMethod = '\Enobrev\ORM\Order::' . $sName;
                    break;
            }

            if ($sMethod !== NULL) {
                return call_user_func_array($sMethod, $aArguments);
            }
        }

        /**
         * @static
         * @return SQL
         * @throws SQLMissingTableOrFieldsException
         */
        public static function select() {
            $aArguments  = func_get_args();
            $bStar       = false;
            $oFields     = new Fields(array());

            /** @var Table[] $aTables */
            $aTables     = array();
            $aJoins      = array();
            $aOrders     = array();
            $oLimit      = NULL;
            $oGroup      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Fields:
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument */
                        $oFields->add($mArgument);
                        break;

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
                }
            }

            if (count($oFields)) {
                /** @var Field $oField*/
                foreach($oFields as $oField) {
                    $aTables[] = $oField->getTable();
                }
            } else if (count($aTables)) {
                $bStar  = true;
            } else {
                throw new SQLMissingTableOrFieldsException;
            }

            $aSQL = array('SELECT');
            if ($bStar) {
                $aSQLFields = array('*');

                // Add hex'd aliases
                /** @var Field $oField*/
                foreach($aTables[0]->Fields as $oField) {
                    if ($oField instanceof Field\Hash
                    ||  $oField instanceof Field\UUID) {
                        $aSQLFields[] = $oField->toSQLColumnForSelect();
                    }
                }

                $aSQL[] = implode(', ', $aSQLFields);
            } else {
                $aSQL[] = $oFields->toSQLColumnsForSelect();
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
            $oSQL->sSQLType  = 'SELECT';
            $oSQL->sSQLTable = $aTables[0]->getTitle();
            $oSQL->sSQL      = implode(' ', $aSQL);
            $oSQL->sSQLGroup = implode(' ', $aSQLLog);

            return $oSQL;
        }

        public static function count() {
            $aArguments  = func_get_args();
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

            $oTable = $aTables[0];
            $aSQL   = array('SELECT');

            /** @var Table $oTable */
            if ($oTable->Primary->count() == 1) {
                $aSQL[] = 'COUNT(' . $oTable->Primary->toSQLColumnsForCount() . ') AS row_count';
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
            $oSQL->sSQLType  = 'SELECT';
            $oSQL->sSQLTable = $oTable->getTitle();
            $oSQL->sSQL      = implode(' ', $aSQL);
            $oSQL->sSQLGroup = implode(' ', $aSQLLog);

            return $oSQL;
        }

        /**
         * @static
         * @return string
         * @throws SQLMissingTableOrFieldsException
         */
        public static function insert() {
            $aArguments = func_get_args();
            $oFields     = new Fields(array());
            $sTable      = NULL;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Fields:
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $oFields->add($mArgument);
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $sTable = $mArgument->getTitle();
                        $oFields->add($mArgument->Fields);
                        break;
                }
            }

            if (count($oFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($sTable === NULL) {
                $sTable = $oFields->offsetGet(0)->sTable;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = 'INSERT';
            $oSQL->sSQLTable = $sTable;
            $oSQL->sSQL      = implode(' ',
                array(
                    'INSERT INTO',
                        $sTable,
                    '(',
                        $oFields->toSQLColumnsForInsert(),
                    ') VALUES (',
                        $oFields->toSQL(),
                    ')'
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    'INSERT INTO',
                        $sTable,
                    '(',
                        $oFields->toSQLColumnsForInsert(),
                    ') VALUES (',
                        $oFields->toSQLLog(),
                    ')'
                )
            );

            return $oSQL;
        }

        /**
         * @static
         * @return string
         * @throws SQLMissingConditionException|SQLMissingTableOrFieldsException
         */
        public static function update() {
            $aArguments = func_get_args();
            $oFields     = new Fields(array());
            $oTable      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Fields:
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $oFields->add($mArgument);
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
                }
            }

            if (count($oFields) == 0) {
                if ($oTable instanceof Table) {
                    $oFields->add($oTable->Fields);
                } else {
                    throw new SQLMissingTableOrFieldsException;
                }
            }

            if ($oConditions->count() == 0) {
                throw new SQLMissingConditionException;
            }

            if ($oTable instanceof Table === false) {
                $sTableObject = 'Table_' . $oFields->offsetGet(0)->sTable;

                /** @var Table $oTable */
                $oTable = new $sTableObject;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = 'UPDATE';
            $oSQL->sSQLTable = $oTable->getTitle();
            $oSQL->sSQL      = implode(' ',
                array(
                    'UPDATE',
                        $oTable->getTitle(),
                    'SET',
                        $oFields->toSQLUpdate($oTable->oResult),
                    'WHERE',
                        $oConditions->toSQL()
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    'UPDATE',
                    $oTable->getTitle(),
                    'SET',
                        $oFields->toSQLUpdateLog($oTable->oResult),
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
        public static function upsert() {
            $aArguments = func_get_args();
            $oFields     = new Fields(array());
            $sTable      = null;
            $oTable      = null;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Fields:
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $oFields->add($mArgument);
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $oTable = $mArgument;
                        $oFields->add($mArgument->Fields);
                        break;
                }
            }

            if (count($oFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($oTable instanceof Table === false) {
                $sTableObject = 'Table_' . $oFields->offsetGet(0)->sTable;

                /** @var Table $oTable */
                $oTable = new $sTableObject;
            }

            if (!$oTable->Primary->hasValue()) {
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
                    $oFields->toSQLColumnsForInsert(),
                    ') VALUES (',
                    $oFields->toSQL(),
                    ') ON DUPLICATE KEY UPDATE',
                    $oFields->toSQLUpdate()
                )
            );

            $oSQL->sSQLGroup = implode(' ',
                array(
                    'INSERT INTO',
                    $oTable->getTitle(),
                    '(',
                    $oFields->toSQLColumnsForInsert(),
                    ') VALUES (',
                    $oFields->toSQLLog(),
                    ')',
                    ') ON DUPLICATE KEY UPDATE',
                    $oFields->toSQLLog()
                )
            );

            return $oSQL;
        }

        /**
         * @static
         * @return string
         * @throws SQLMissingTableOrFieldsException
         */
        public static function delete() {
            $aArguments = func_get_args();
            $oFields     = new Fields(array());
            $sTable      = NULL;
            $oConditions = new Conditions;
            foreach($aArguments as $mArgument) {
                switch(true) {
                    case $mArgument instanceof Fields:
                    case $mArgument instanceof Field:
                        /** @var Field $mArgument  */
                        $oFields->add($mArgument);
                        break;

                    case $mArgument instanceof Table:
                        /** @var Table $mArgument  */
                        $sTable = $mArgument->getTitle();
                        $oFields->add($mArgument->Fields);
                        break;

                    case $mArgument instanceof Conditions:
                    case $mArgument instanceof Condition:
                        $oConditions->add($mArgument);
                        break;
                }
            }

            if (count($oFields) == 0) {
                throw new SQLMissingTableOrFieldsException;
            }

            if ($oConditions->count() == 0) {
                $oConditions->add($oFields);
            }

            if ($sTable === NULL) {
                $sTable = $oFields->offsetGet(0)->sTable;
            }

            $oSQL = new self;
            $oSQL->sSQLType  = 'DELETE';
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
    }

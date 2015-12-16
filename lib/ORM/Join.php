<?php
    namespace Enobrev\ORM;
    
    class JoinException extends DbException {}
    class JoinWrongFieldCountException extends JoinException {}

    class Join {
        const LEFT_OUTER = 'LEFT OUTER';

        private $sType;

        /**
         * @var Fields
         */
        private $Fields;

        /**
         * @param mixed|Field[] $aFields...
         *
         * @return Join
         * @throws JoinWrongFieldCountException
         */
        public static function create($aFields) {
            $aFields = func_get_args();
            $oJoin   = new self;

            foreach($aFields as $oField) {
                $oJoin->Fields->add($oField);
            }

            if (count($oJoin->Fields) !== 2) {
                throw new JoinWrongFieldCountException;
            }

            return $oJoin;
        }

        public function __construct() {
            $this->sType  = self::LEFT_OUTER;
            $this->Fields = new Fields(array());
        }

        public function toSQL() {
            /** @var Field $oFrom */
            /** @var Field $oTo */
            $oFrom = $this->Fields->offsetGet(0);
            $oTo   = $this->Fields->offsetGet(1);

            if ($oTo->hasAlias()) {
                return implode(' ',
                    array(
                        $this->sType,
                        'JOIN',
                        $oTo->sTable,
                        'AS',
                        $oTo->sAlias,
                        'ON',
                        $oFrom->toSQLColumn(),
                        '=',
                        $oTo->toSQLColumn()
                    )
                );
            } else {
                return implode(' ',
                    array(
                        $this->sType,
                        'JOIN',
                        $oTo->sTable,
                        'ON',
                        $oFrom->toSQLColumn(),
                        '=',
                        $oTo->toSQLColumn()
                    )
                );
            }
        }
    }

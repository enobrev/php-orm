<?php
    namespace Enobrev\ORM;

    class OrderException extends DbException {}

    /**
     * @method static Order desc()
     * @method static Order asc()
     * @method static Order byfield()
     */
    class Order {
        const TYPE_DESC  = 'DESC';
        const TYPE_ASC   = 'ASC';
        const TYPE_FIELD = 'BYFIELD';

        /**
         * @var Field
         */
        private $oField;
        private $sType;
        private $aValues;

        /**
         * @param Field       $oField
         * @param string      $sType
         * @param array       $aValues
         *
         * @return Order
         */
        public static function create(Field $oField, $sType = self::TYPE_ASC, Array $aValues = array()) {
            $oOrder   = new self;
            $oOrder->oField  = $oField;
            $oOrder->sType   = $sType;
            $oOrder->aValues = $aValues;

            return $oOrder;
        }

        /**
         * Wrapper method defining group types in method name
         * @param string $sName
         * @param array $aArguments
         * @return Condition
         */
        public static function __callStatic($sName, $aArguments) {
            switch($sName) {
                default:
                case 'desc':    array_push($aArguments, self::TYPE_DESC);           break;
                case 'asc':     array_push($aArguments, self::TYPE_ASC);            break;
                case 'byfield': array_splice($aArguments, 1, 0, self::TYPE_FIELD);  break;
            }

            return call_user_func_array('self::create', $aArguments);
        }

        public function __construct() {
            $this->Fields = new Fields(array());
        }

        public function toSQL() {
            if ($this->sType == self::TYPE_FIELD) {
                $aValues = $this->aValues;
                foreach($aValues as &$sValue) {
                    $this->oField->setValue($sValue);
                    $sValue = $this->oField->toSQL();
                }

                array_unshift($aValues, $this->oField->toSQLColumn());

                return 'FIELD(' . implode(', ', $aValues) . ')';
            } else {
                return $this->oField->toSQLColumn() . ' ' . $this->sType;
            }
        }
    }

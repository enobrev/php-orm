<?php
    namespace Enobrev\ORM;

    class OrderException extends DbException {}

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
        private static function create(Field $oField, $sType = self::TYPE_ASC, Array $aValues = array()) {
            $oOrder   = new self;
            $oOrder->oField  = $oField;
            $oOrder->sType   = $sType;
            $oOrder->aValues = $aValues;

            return $oOrder;
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function desc(Field $oField, Array $aValues = array()) {
            return self::create($oField, self::TYPE_DESC, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function asc(Field $oField, Array $aValues = array()) {
            return self::create($oField, self::TYPE_ASC, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function byfield(Field $oField, Array $aValues = array()) {
            return self::create($oField, self::TYPE_FIELD, $aValues);
        }

        public function __construct() {
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

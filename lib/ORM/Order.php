<?php
    namespace Enobrev\ORM;

    class Order {
        protected const TYPE_DESC  = 'DESC';
        protected const TYPE_ASC   = 'ASC';
        protected const TYPE_FIELD = 'BYFIELD';

        private Field $oField;

        private string $sType;

        private array $aValues;

        /**
         * @param Field       $oField
         * @param string      $sType
         * @param array       $aValues
         *
         * @return Order
         */
        private static function create(Field $oField, string $sType = self::TYPE_ASC, array $aValues = []): self {
            $oOrder          = new self;
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
        public static function desc(Field $oField, array $aValues = []): self {
            return self::create($oField, self::TYPE_DESC, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function asc(Field $oField, array $aValues = []): self {
            return self::create($oField, self::TYPE_ASC, $aValues);
        }

        /**
         * @param Field $oField
         * @param array $aValues
         * @return Order
         */
        public static function byfield(Field $oField, Array $aValues = []): self {
            return self::create($oField, self::TYPE_FIELD, $aValues);
        }

        private function __construct() {
        }

        public function toSQL(): string {
            if ($this->sType === self::TYPE_FIELD) {
                $aValues = $this->aValues;
                foreach($aValues as &$sValue) {
                    $this->oField->setValue($sValue);
                    $sValue = $this->oField->toSQL();
                }
                unset($sValue);

                array_unshift($aValues, $this->oField->toSQLColumn());

                return 'FIELD(' . implode(', ', $aValues) . ')';
            }

            return $this->oField->toSQLColumn() . ' ' . $this->sType;
        }
    }

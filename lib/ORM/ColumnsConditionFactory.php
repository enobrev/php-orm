<?php
    namespace Enobrev\ORM;
    
    use Enobrev\ORM\Condition\ColumnBetweenColumns;
    use Enobrev\ORM\Condition\ColumnToColumn;
    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\Exceptions\ConditionInvalidTypeException;

    class ColumnsConditionFactory {
        private const NOT_SET       = '__NOT_SET__';

        public const LT           = '<';
        public const LTE          = '<=';
        public const GT           = '>';
        public const GTE          = '>=';
        public const EQUAL        = '=';
        public const NEQ          = '<>';
        public const LIKE         = 'LIKE';
        public const N_LIKE       = 'NOT LIKE';
        public const BETWEEN      = 'BETWEEN';
        public const N_BETWEEN    = 'NOT BETWEEN';

        protected string $sSign;

        protected static array $aSignsSimple = [
            self::LT,       self::LTE,
            self::GT,       self::GTE,
            self::EQUAL,    self::NEQ,
            self::LIKE,     self::N_LIKE
        ];

        protected static array $aSignsBetween = [
            self::BETWEEN,  self::N_BETWEEN
        ];

        public static function eq(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::EQUAL, $oField, $oFieldOrValue);
        }

        public static function neq(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::NEQ, $oField, $oFieldOrValue);
        }

        public static function like(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LIKE, $oField, $oFieldOrValue);
        }

        public static function nlike(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::N_LIKE, $oField, $oFieldOrValue);
        }

        public static function gt(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::GT, $oField, $oFieldOrValue);
        }

        public static function gte(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::GTE, $oField, $oFieldOrValue);
        }

        public static function lt(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LT, $oField, $oFieldOrValue);
        }

        public static function lte(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LTE, $oField, $oFieldOrValue);
        }

        public static function between(Field $oField, $mLow = self::NOT_SET, $mHigh = self::NOT_SET) {
            return self::_between(self::BETWEEN, $oField, $mLow, $mHigh);
        }

        public static function nbetween(Field $oField, $mLow = self::NOT_SET, $mHigh = self::NOT_SET) {
            return self::_between(self::N_BETWEEN, $oField, $mLow, $mHigh);
        }

        /**
         * @param string $sSign
         * @param Field  $oLeft
         * @param Field  $oRight
         *
         * @return ConditionInterface
         */
        private static function _simple(string $sSign, Field $oLeft, Field $oRight): ConditionInterface {
            assert(in_array($sSign, self::$aSignsSimple), new ConditionInvalidTypeException());
            return new ColumnToColumn($sSign, $oLeft, $oRight);
        }

        /**
         * @param string $sSign
         * @param Field  $oField
         * @param Field  $oLow
         * @param Field  $oHigh
         *
         * @return ConditionInterface
         */
        private static function _between(string $sSign, Field $oField, Field $oLow, Field $oHigh): ConditionInterface {
            assert(in_array($sSign, self::$aSignsBetween), new ConditionInvalidTypeException());
            return new ColumnBetweenColumns($sSign, $oField, $oLow, $oHigh);
        }
    }

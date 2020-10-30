<?php
    namespace Enobrev\ORM;
    
    use Enobrev\ORM\Condition\ColumnBetweenColumns;
    use Enobrev\ORM\Condition\ColumnToColumn;
    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\Exceptions\ConditionInvalidTypeException;

    class ColumnsConditionFactory {
        public const LT           = '<';
        public const LTE          = '<=';
        public const GT           = '>';
        public const GTE          = '>=';
        public const EQ           = '=';
        public const N_EQ         = '<>';
        public const LIKE         = 'LIKE';
        public const N_LIKE       = 'NOT LIKE';
        public const BETWEEN      = 'BETWEEN';
        public const N_BETWEEN    = 'NOT BETWEEN';

        protected string $sSign;

        protected static array $aSignsSimple = [
            self::LT,   self::LTE,
            self::GT,   self::GTE,
            self::EQ,   self::N_EQ,
            self::LIKE, self::N_LIKE
        ];

        protected static array $aSignsBetween = [
            self::BETWEEN,  self::N_BETWEEN
        ];

        public static function eq(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::EQ, $oLeft, $oRight);
        }

        public static function neq(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::N_EQ, $oLeft, $oRight);
        }

        public static function like(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::LIKE, $oLeft, $oRight);
        }

        public static function nlike(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::N_LIKE, $oLeft, $oRight);
        }

        public static function gt(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::GT, $oLeft, $oRight);
        }

        public static function gte(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::GTE, $oLeft, $oRight);
        }

        public static function lt(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::LT, $oLeft, $oRight);
        }

        public static function lte(Field $oLeft, Field $oRight): ConditionInterface {
            return self::_simple(self::LTE, $oLeft, $oRight);
        }

        public static function between(Field $oField, Field $oLow, Field $oHigh): ConditionInterface {
            return self::_between(self::BETWEEN, $oField, $oLow, $oHigh);
        }

        public static function nbetween(Field $oField, Field $oLow, Field $oHigh): ConditionInterface {
            return self::_between(self::N_BETWEEN, $oField, $oLow, $oHigh);
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

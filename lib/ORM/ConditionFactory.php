<?php
    namespace Enobrev\ORM;
    
    use Enobrev\ORM\Condition\ColumnBetweenFieldAndValue;
    use Enobrev\ORM\Condition\ColumnBetweenFieldValues;
    use Enobrev\ORM\Condition\ColumnBetweenValues;
    use Enobrev\ORM\Condition\ColumnInValue;
    use Enobrev\ORM\Condition\ColumnNull;
    use Enobrev\ORM\Condition\ColumnToFieldValue;
    use Enobrev\ORM\Condition\ColumnToOtherFieldValue;
    use Enobrev\ORM\Condition\ColumnToValue;
    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\Exceptions\ConditionInvalidTypeException;

    class ConditionFactory {
        public const NOT_SET       = '__NOT_SET__';

        public const LT           = '<';
        public const LTE          = '<=';
        public const GT           = '>';
        public const GTE          = '>=';
        public const EQ           = '=';
        public const NEQ          = '<>';
        public const IN           = 'IN';
        public const N_IN         = 'NOT IN';
        public const LIKE         = 'LIKE';
        public const N_LIKE       = 'NOT LIKE';
        public const ISNULL       = 'IS NULL';
        public const N_NULL       = 'IS NOT NULL';
        public const BETWEEN      = 'BETWEEN';
        public const N_BETWEEN    = 'NOT BETWEEN';

        protected string $sSign;

        protected static array $aSignsSimple = [
            self::LT,       self::LTE,
            self::GT,       self::GTE,
            self::EQ,       self::NEQ,
            self::LIKE,     self::N_LIKE
        ];

        protected static array $aSignsIn = [
            self::IN,       self::N_IN
        ];

        protected static array $aSignsNull = [
            self::ISNULL,   self::N_NULL
        ];

        protected static array $aSignsBetween = [
            self::BETWEEN,  self::N_BETWEEN
        ];

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function eq(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::EQ, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function neq(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::NEQ, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function like(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LIKE, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function nlike(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::N_LIKE, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function gt(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::GT, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function gte(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::GTE, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function lt(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LT, $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function lte(Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            return self::_simple(self::LTE, $oField, $oFieldOrValue);
        }

        public static function nul(Field $oField): ConditionInterface {
            return self::_null(self::ISNULL, $oField);
        }

        public static function nnul(Field $oField): ConditionInterface {
            return self::_null(self::N_NULL, $oField);
        }

        public static function in(Field $oField, array $aValues): ConditionInterface {
            return self::_in(self::IN, $oField, $aValues);
        }

        public static function nin(Field $oField, array $aValues): ConditionInterface {
            return self::_in(self::N_IN, $oField, $aValues);
        }

        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return ConditionInterface
         */
        public static function between(Field $oField, $mLow = self::NOT_SET, $mHigh = self::NOT_SET) {
            return self::_between(self::BETWEEN, $oField, $mLow, $mHigh);
        }

        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return ConditionInterface
         */
        public static function nbetween(Field $oField, $mLow = self::NOT_SET, $mHigh = self::NOT_SET) {
            return self::_between(self::N_BETWEEN, $oField, $mLow, $mHigh);
        }

        /**
         * @param string      $sSign
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        protected static function _simple(string $sSign, Field $oField, $oFieldOrValue = self::NOT_SET): ConditionInterface {
            assert(in_array($sSign, self::$aSignsSimple), new ConditionInvalidTypeException());

            if ($oFieldOrValue !== self::NOT_SET) {
                if ($oFieldOrValue instanceof Field) {
                    return new ColumnToOtherFieldValue($sSign, $oField, $oFieldOrValue);
                }

                return new ColumnToValue($sSign, $oField, $oFieldOrValue);
            }

            return new ColumnToFieldValue($sSign, $oField) ;
        }

        /**
         * @param string $sSign
         * @param Field  $oField
         * @param array  $aValues
         *
         * @return ConditionInterface
         */
        protected static function _in(string $sSign, Field $oField, array $aValues): ConditionInterface {
            assert(in_array($sSign, self::$aSignsIn), new ConditionInvalidTypeException());

            return new ColumnInValue($sSign, $oField, $aValues);
        }

        /**
         * @param string $sSign
         * @param Field  $oField
         *
         * @return ConditionInterface
         */
        protected static function _null(string $sSign, Field $oField): ConditionInterface {
            assert(in_array($sSign, self::$aSignsNull), new ConditionInvalidTypeException());

            return new ColumnNull($sSign, $oField) ;
        }

        /**
         * @param string        $sSign
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return ConditionInterface
         */
        protected static function _between(string $sSign, Field $oField, $mLow = self::NOT_SET, $mHigh = self::NOT_SET): ConditionInterface {
            assert(in_array($sSign, self::$aSignsBetween), new ConditionInvalidTypeException());

            if ($mHigh instanceof Field) {
                return new ColumnBetweenValues($sSign, $oField, $mLow, $mHigh);
            } else if ($mHigh !== self::NOT_SET) {
                return new ColumnBetweenValues($sSign, $oField, $mLow, $mHigh);
            } else if ($mLow instanceof Field) {
                return new ColumnBetweenFieldValues($sSign, $oField, $mLow);
            }

            return new ColumnBetweenFieldAndValue($sSign, $oField, $mLow);
        }
    }

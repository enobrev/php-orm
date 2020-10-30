<?php
    namespace Enobrev;

    use Enobrev\ORM\Condition\ConditionInterface;
    use Enobrev\ORM\ConditionFactory;
    use Enobrev\ORM\Conditions;
    use Enobrev\ORM\Field;

    /**
     * Class SQL
     * Wrapper for Query Building Functionality
     *
     * @package Enobrev
     */
    class SQL {
        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return ORM\Conditions
         */
        public static function either(...$aArguments): ORM\Conditions {
            return ORM\Conditions::either(...$aArguments);
        }

        /**
         * @return string
         */
        public static function NOW(): string {
            return ORM\DateFunction::FUNC_NOW;
        }

        /**
         * @param ConditionInterface[]|ConditionInterface|Conditions|Field|Field[] $aArguments
         *
         * @return Conditions
         */
        public static function also(...$aArguments): ORM\Conditions {
            return ORM\Conditions::also(...$aArguments);
        }


        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function eq(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::eq($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function neq(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::neq($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function like(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::like( $oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function nlike(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::nlike($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function gt(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::gt($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function gte(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::gte($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function lt(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::lt($oField, $oFieldOrValue);
        }

        /**
         * @param Field       $oField
         * @param Field|mixed $oFieldOrValue
         *
         * @return ConditionInterface
         */
        public static function lte(Field $oField, $oFieldOrValue = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::lte($oField, $oFieldOrValue);
        }

        public static function nul(Field $oField): ConditionInterface {
            return ConditionFactory::nul($oField);
        }

        public static function nnul(Field $oField): ConditionInterface {
            return ConditionFactory::nnul($oField);
        }

        public static function in(Field $oField, array $aValues): ConditionInterface {
            return ConditionFactory::in($oField, $aValues);
        }

        public static function nin(Field $oField, array $aValues): ConditionInterface {
            return ConditionFactory::nin($oField, $aValues);
        }

        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return ConditionInterface
         */
        public static function between(Field $oField, $mLow = ConditionFactory::NOT_SET, $mHigh = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::between($oField, $mLow, $mHigh);
        }

        /**
         * @param Field         $oField
         * @param Field|mixed   $mLow
         * @param Field|mixed   $mHigh
         *
         * @return ConditionInterface
         */
        public static function nbetween(Field $oField, $mLow = ConditionFactory::NOT_SET, $mHigh = ConditionFactory::NOT_SET): ConditionInterface {
            return ConditionFactory::nbetween($oField, $mLow, $mHigh);
        }

        /**
         * @param Field $oFrom
         * @param Field $oTo
         * @param ConditionInterface|Conditions|null  $oConditions
         *
         * @return ORM\Join
         */
        public static function join(ORM\Field $oFrom, ORM\Field $oTo, $oConditions = null): ORM\Join {
            return ORM\Join::create($oFrom, $oTo, $oConditions);
        }

        /**
         * @param int|null $iStart
         * @param int|null $iOffset
         * @return ORM\Limit
         */
        public static function limit(?int $iStart = null, ?int $iOffset = null): ORM\Limit {
            return ORM\Limit::create($iStart, $iOffset);
        }

        /**
         * @param ORM\Field[] $aFields
         * @return ORM\Group
         */
        public static function group(...$aFields): ORM\Group {
            return ORM\Group::create(...$aFields);
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return ORM\Order
         */
        public static function desc(ORM\Field $oField, array $aValues = []): ORM\Order {
            return ORM\Order::desc($oField, $aValues);
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return ORM\Order
         */
        public static function asc(ORM\Field $oField, array $aValues = []): ORM\Order {
            return ORM\Order::asc($oField, $aValues);
        }

        /**
         * @param ORM\Field $oField
         * @param array $aValues
         * @return ORM\Order
         */
        public static function byfield(ORM\Field $oField, array $aValues = []): ORM\Order {
            return ORM\Order::byfield($oField, $aValues);
        }
    }

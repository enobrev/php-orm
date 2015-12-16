<?php
    namespace Enobrev\ORM;

    use ArrayIterator;

    class Joins extends ArrayIterator {

        /**
         * @param $aJoins
         * @return Joins
         */
        public static function create($aJoins) {
            $aJoins = func_get_args();
            $oJoins = new self();
            foreach($aJoins as $mCondition) {
                $oJoins->append($mCondition);
            }

            return $oJoins;
        }
    }
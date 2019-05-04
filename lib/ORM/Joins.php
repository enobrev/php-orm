<?php
    namespace Enobrev\ORM;

    use ArrayIterator;

    class Joins extends ArrayIterator {

        /**
         * @param Join[] ...$aJoins
         * @return Joins
         */
        public static function create(...$aJoins): Joins {
            $oJoins = new self();
            foreach($aJoins as $oJoin) {
                $oJoins->append($oJoin);
            }

            return $oJoins;
        }
    }
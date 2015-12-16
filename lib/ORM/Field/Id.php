<?php
    namespace Enobrev\ORM\Field;

    class Id extends Integer {
        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && (int) $this->sValue > 0;
        }
    }
?>
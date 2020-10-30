<?php
    namespace Enobrev\ORM\Field;

    class Id extends Integer {
        public function hasValue():bool {
            return parent::hasValue() && (int) $this->sValue > 0;
        }
    }
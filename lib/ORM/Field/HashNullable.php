<?php
    namespace Enobrev\ORM\Field;

    class HashNullable extends Hash {

        /**
         *
         * @return string
         */
        public function toSQL() {
            if ($this->isNull()) {
                return 'NULL';
            } else {
                return parent::toSQL();
            }
        }
    }
?>

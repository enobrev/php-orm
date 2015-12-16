<?php
    namespace Enobrev\ORM\Field;

    class UUIDNullable extends UUID {

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

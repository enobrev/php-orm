<?php
    namespace Enobrev\ORM;

    use DateTime;

    interface ModifiedDateColumn {

        /**
         * @return Field\DateTime
         */
        public function getModifiedDateField();

        /**
         * @return DateTime
         */
        public function getLastModified();
    }
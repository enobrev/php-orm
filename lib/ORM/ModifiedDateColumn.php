<?php
    namespace Enobrev\ORM;

    use DateTime;

    interface ModifiedDateColumn {

        /**
         * @return Field\DateTime
         */
        public function getModifiedDateField(): Field\DateTime;

        /**
         * @return DateTime
         */
        public function getLastModified(): DateTime;
    }
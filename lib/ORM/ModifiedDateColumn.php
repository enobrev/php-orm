<?php
    namespace Enobrev\ORM;

    use DateTime;

    interface ModifiedDateColumn {

        /**
         * @return DateTime
         */
        public function getLastModified();
    }
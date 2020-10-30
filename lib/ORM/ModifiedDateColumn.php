<?php
    namespace Enobrev\ORM;

    use DateTime;

    interface ModifiedDateColumn {

        public function getModifiedDateField(): Field\DateTime;

        public function getLastModified(): DateTime;
    }
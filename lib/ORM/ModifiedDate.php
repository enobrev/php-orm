<?php
    namespace Enobrev\ORM;


    trait ModifiedDate {
        public function getLastModified() {
            return $this->getModifiedDateField()->getValue();
        }
    }
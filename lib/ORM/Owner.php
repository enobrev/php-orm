<?php
    namespace Enobrev\ORM;


    trait Owner {

        /**
         * @return Table
         */
        public function getOwner() {
            if ($this->getOwnerField()->hasValue()) {
                $sTable = $this->getOwnerTable();
                return $sTable::getById($this->getOwnerField()->getValue());
            }
        }

        /**
         * @param Table|null $oOwner
         * @return bool
         */
        public function hasOwner(Table $oOwner = null) {
            $sTable = $this->getOwnerTable();
            return $oOwner instanceof $sTable && $this->getOwnerField()->is($oOwner);
        }
    }
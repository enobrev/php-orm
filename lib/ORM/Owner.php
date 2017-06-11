<?php
    namespace Enobrev\ORM;


    trait Owner {

        /**
         * @return static
         */
        public function getOwner() {
            /** @var Field $oOwnerField */
            $oOwnerField = $this->getOwnerField();
            if ($oOwnerField->hasValue()) {
                $sTable = $this->getOwnerTable();
                return $sTable::getById($oOwnerField->getValue());
            }
        }

        /**
         * @param Table|null $oOwner
         * @return bool
         */
        public function hasOwner(Table $oOwner = null) {
            /** @var Field $oOwnerField */
            $oOwnerField = $this->getOwnerField();
            $sTable      = $this->getOwnerTable();
            return $oOwner instanceof $sTable && $oOwnerField->is($oOwner);
        }
    }
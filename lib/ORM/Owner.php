<?php
    namespace Enobrev\ORM;


    trait Owner {

        /**
         * @return Table
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
         * @throws \Exception
         */
        public function hasOwner(Table $oOwner = null) {
            /** @var Field $oOwnerField */
            $oOwnerField = $this->getOwnerField();
            $sTable      = $this->getOwnerTable();

            $bOwnerIsRightType   = $oOwner instanceof $sTable;

            if (!$bOwnerIsRightType) {
                throw new \Exception('Invalid Owner Table');
            }

            $bOwnerHasRightField = $oOwner->{$oOwnerField->sColumn} instanceof Field;

            if (!$bOwnerHasRightField) {
                throw new \Exception('Owner Table does not have Owner Field');
            }

            $bOwnerHasRightValue = $oOwnerField->is($oOwner->{$oOwnerField->sColumn});

            return $bOwnerIsRightType
                && $bOwnerHasRightField
                && $bOwnerHasRightValue;
        }
    }
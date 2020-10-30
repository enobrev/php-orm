<?php
    namespace Enobrev\ORM;


    use Exception;

    trait Owner {

        /**
         * @return Table
         */
        public function getOwner(): ?Table {
            /** @var Field $oOwnerField */
            $oOwnerField = $this->getOwnerField();
            if ($oOwnerField->hasValue()) {
                $sTable = $this->getOwnerTable();
                return $sTable::getById($oOwnerField->getValue());
            }

            return null;
        }

        /**
         * @param Table|null $oOwner
         * @return bool
         */
        public function hasOwner(?Table $oOwner = null): bool {
            /** @var Field $oOwnerField */
            $oOwnerField = $this->getOwnerField();
            $sTable      = $this->getOwnerTable();

            assert($oOwner instanceof $sTable,                        new Exception('Invalid Owner Table'));
            assert($oOwner->{$oOwnerField->sColumn} instanceof Field, new Exception('Owner Table does not have Owner Field'));

            return $oOwnerField->is($oOwner->{$oOwnerField->sColumn});
        }
    }
<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        /**
         * @return Table
         */
        public function getOwnerTable(): TAble;

        /**
         * @return Field
         */
        public function getOwnerField(): Field;

        /**
         * @return Table
         */
        public function getOwner(): Table;

        /**
         * @param Table $oOwner
         * @return bool
         */
        public function hasOwner(Table $oOwner): bool;
    }
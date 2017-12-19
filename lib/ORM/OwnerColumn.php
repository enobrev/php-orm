<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        /**
         * @return Table
         */
        public function getOwnerTable();

        /**
         * @return Field
         */
        public function getOwnerField();

        /**
         * @return Table
         */
        public function getOwner();

        /**
         * @param Table $oOwner
         * @return bool
         */
        public function hasOwner(Table $oOwner);
    }
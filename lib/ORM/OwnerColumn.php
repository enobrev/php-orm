<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        /**
         * @return static
         */
        public function getOwnerTable();

        /**
         * @return Field
         */
        public function getOwnerField();

        /**
         * @return static
         */
        public function getOwner();

        /**
         * @param Table $oOwner
         * @return bool
         */
        public function hasOwner(Table $oOwner);
    }
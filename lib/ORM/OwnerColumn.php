<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        public function getOwnerField();

        public function getOwner();

        public function hasOwner(Table $oOwner);
    }
<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        public function getOwner();

        public function hasOwner(Table $oOwner);
    }
<?php
    namespace Enobrev\ORM;

    interface OwnerColumn {
        public function getOwner(Table $oOwner);

        public function hasOwner(Table $oOwner);
    }
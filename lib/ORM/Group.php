<?php
    namespace Enobrev\ORM;
    
    class GroupException extends DbException {}

    class Group {
        /**
         * @var Field[]
         */
        private $aFields;

        /**
         * @param array ...$aFields
         * @return Group
         */
        public static function create(...$aFields) {
            $oGroup   = new self;
            $oGroup->aFields = $aFields;

            return $oGroup;
        }

        public function __construct() {
            $this->aFields = [];
        }

        public function toSQL() {
            $aFields = array();
            foreach($this->aFields as $oField) {
                $aFields[] = $oField->toSQLColumnForFields(true);
            }

            return implode(', ', $aFields);
        }
    }

<?php
    namespace Enobrev\ORM;
    
    class GroupException extends DbException {}

    class Group {
        /**
         * @var Field[]
         */
        private $aFields;

        /**
         * @param Field[] ...$aFields
         * @return Group
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        public static function create(...$aFields): Group {
            $oGroup   = new self;
            $oGroup->aFields = $aFields;

            return $oGroup;
        }

        public function __construct() {
            $this->aFields = [];
        }

        public function hasField(Field $oCheckField): bool {
            foreach($this->aFields as $oField) {
                if ($oField === $oCheckField) {
                    return true;
                }
            }

            return false;
        }

        public function toSQL(): string {
            $aFields = array();
            foreach($this->aFields as $oField) {
                $aFields[] = $oField->toSQLColumnForFields();
            }

            return implode(', ', $aFields);
        }
    }

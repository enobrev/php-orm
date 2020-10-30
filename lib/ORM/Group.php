<?php
    namespace Enobrev\ORM;

    class Group {
        /**
         * @var Field[]
         */
        private array $aFields;

        /**
         * @param Field[] $aFields
         * @return Group
         */
        public static function create(...$aFields): Group {
            return new self($aFields);
        }

        /**
         * @param Field[] $aFields
         */
        private function __construct(array $aFields = []) {
            $this->aFields = $aFields;
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

<?php
    namespace Enobrev\ORM;
    
    class GroupException extends DbException {}

    class Group {
        /**
         * @var Fields
         */
        private $Fields;

        public static function create() {
            $aFields = func_get_args();
            $oGroup   = new self;

            /** @var Field $oField */
            foreach($aFields as $oField) {
                $oGroup->Fields->add($oField);
            }

            return $oGroup;
        }

        public function __construct() {
            $this->Fields = new Fields(array());
        }

        public function toSQL() {
            return 'GROUP BY ' . $this->Fields->toSQLColumns();
        }
    }

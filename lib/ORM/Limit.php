<?php
    namespace Enobrev\ORM;
    
    class Limit {
        private $iStart;
        private $iOffset;

        /**
         * @param int $iStart
         * @param int $iOffset
         * @return Limit
         */
        public static function create($iStart = null, $iOffset = null) {
            $oLimit = new self;

            if ($iOffset !== null) {
                $oLimit->iOffset = abs((int) $iOffset);
            }

            if ($iStart !== null) {
                $oLimit->iStart  = abs((int) $iStart);
            }

            return $oLimit;
        }

        public function __construct() {
            $this->iStart  = null;
            $this->iOffset = null;
        }

        public function toSQL() {
            if ($this->iOffset !== null) {
                if ($this->iStart !== null) {
                    return 'LIMIT ' . $this->iStart . ', ' . $this->iOffset;
                }

                return 'LIMIT ' . $this->iOffset;
            }

            if ($this->iStart !== null) {
                return 'LIMIT ' . $this->iStart;
            }

            return '';
        }
    }

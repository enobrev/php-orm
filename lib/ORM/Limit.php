<?php
    namespace Enobrev\ORM;
    
    class Limit {
        private ?int $iStart = null;

        private ?int $iOffset = null;

        /**
         * @param int $iStart
         * @param int $iOffset
         * @return Limit
         */
        public static function create(?int $iStart = null, ?int $iOffset = null): self {
            $oLimit = new self;

            if ($iOffset !== null) {
                $oLimit->iOffset = abs($iOffset);
            }

            if ($iStart !== null) {
                $oLimit->iStart  = abs($iStart);
            }

            return $oLimit;
        }

        public function toSQL(): string {
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

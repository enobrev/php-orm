<?php
    namespace Enobrev\ORM;
    
    class Limit {
        /** @var null|int */
        private $iStart;

        /** @var null|int */
        private $iOffset;

        /**
         * @param int $iStart
         * @param int $iOffset
         * @return Limit
         */
        public static function create(int $iStart = null, int $iOffset = null): self {
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

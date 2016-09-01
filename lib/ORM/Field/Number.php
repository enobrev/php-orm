<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Db;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;

    abstract class Number extends Field {
        /**
         *
         * @return string|integer
         */
        public function __toString() {
            return $this->sValue != 0 ? (string) $this->sValue : '0';
        }
        
        /**
         *
         * @return string
         */
        public function toSQL() {
            return Escape::string($this->__toString());
        }
    }
?>
<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Db;
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
            return Db::getInstance()->real_escape_string($this->__toString());
        }
    }
?>
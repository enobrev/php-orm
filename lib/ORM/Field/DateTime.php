<?php
    namespace Enobrev\ORM\Field;

    use DateTime as PHPDateTime;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class DateTime extends Date {

        const DEFAULT_FORMAT = PHPDateTime::RFC3339;
        const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var PHPDateTime
         */
        public $sValue;

        /**
         *
         * @param mixed $sValue
         * @return DateTime
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            if ($sValue instanceof PHPDateTime) {
                $sValue = $sValue->format(self::DEFAULT_FORMAT);
            }

            if ($sValue === 'NULL'
            ||  $sValue === NULL) {
                $this->sValue = NULL;
            } else {
                parent::setValue($sValue);
            }

            return $this;
        }


        /**
         * @return bool
         */
        public function isNull() {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }

        /**
         *
         * @return string|integer
         */
        public function __toString() {
            $sValue = $this->sValue instanceof PHPDateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                $sValue = 'NULL';
            }

            return $sValue;
        }

        /**
         *
         * @return string
         */
        public function toSQL() {
            if ($this->isNull()) {
                return 'NULL';
            } else {
                switch ($this->sValue) {
                    case self::MYSQL_NOW:
                        return $this->sValue;

                    default:
                        return Escape::string($this->sValue->format('Y-m-d H:i:s'));
                }
            }
        }
    }
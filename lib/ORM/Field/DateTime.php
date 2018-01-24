<?php
    namespace Enobrev\ORM\Field;

    use DateTime as PHPDateTime;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\DateFunction;

    class DateTime extends Date {

        const DEFAULT_FORMAT = PHPDateTime::RFC3339;
        const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var PHPDateTime|null
         */
        public $sValue;

        /**
         *
         * @param mixed $sValue
         * @return $this
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
        public function isNull():bool {
            $sValue = $this->sValue instanceof \DateTime ? $this->sValue->format(self::DEFAULT_FORMAT) : self::NULL_VALUE;

            if (substr($sValue, 0, 1) == '-') {
                return true;
            }

            return parent::isNull();
        }

        /**
         * @return bool
         */
        public function hasValue(): bool {
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }


        public function __toString():string {
            $sValue = self::NULL_VALUE;

            if ($this->sValue instanceof PHPDateTime) {
                $sValue = $this->sValue->format(self::DEFAULT_FORMAT);
            } else if ($this->sValue instanceof DateFunction) {
                $sValue = (new PHPDateTime())->format(self::DEFAULT_FORMAT);
            }

            if (substr($sValue, 0, 1) == '-') {
                $sValue = 'NULL';
            }

            return $sValue;
        }

        /**
         *
         * @return string
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            if ($this->sValue instanceof DateFunction) {
                return $this->sValue->getName();
            } else if ($this->sValue instanceof \DateTime) {
                return Escape::string($this->sValue->format('Y-m-d H:i:s'));
            } else {
                return 'NULL';
            }
        }
    }
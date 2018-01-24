<?php
    namespace Enobrev\ORM\Field;

    use DateTime;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\DateFunction;

    class Date extends Text {

        const DEFAULT_FORMAT = 'Y-m-d';
        const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var \DateTime|DateFunction|null
         */
        public $sValue;

        /**
         * @return \DateTime|null
         */
        public function getValue() {
            if ($this->sValue instanceof DateFunction) {
                return new \DateTime();
            }

            return $this->sValue;
        }

        /**
         * @return bool
         */
        public function hasValue():bool {
            return parent::hasValue() && (string) $this != self::NULL_VALUE;
        }

        /**
         *
         * @return string
         */
        public function __toString() {
            $sValue = self::NULL_VALUE;

            if ($this->sValue instanceof \DateTime) {
                $sValue = $this->sValue->format(self::DEFAULT_FORMAT);
            } else if ($this->sValue instanceof DateFunction) {
                $sValue = (new DateTime())->format(self::DEFAULT_FORMAT);
            }

            if (substr($sValue, 0, 1) == '-') {
                $sValue = self::NULL_VALUE;
            }
            
            return $sValue;
        }

        /**
         * @return string
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            if ($this->sValue instanceof DateFunction) {
                return $this->sValue->getName();
            } else if ($this->sValue instanceof \DateTime) {
                return Escape::string($this->sValue->format('Y-m-d'));
            } else {
                return 'NULL';
            }
        }

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
            
            switch(true) {
                case $sValue == self::NULL_VALUE:
                    $this->sValue = null;
                    break;

                case $sValue === null:
                case $sValue instanceof DateTime:
                case $sValue instanceof DateFunction:
                    $this->sValue = $sValue;
                    break;

                case DateFunction::isSupportedType($sValue):
                    $this->sValue = DateFunction::createFromString($sValue);
                    break;

                default:
                    $this->sValue = new DateTime($sValue);
                    break;
            }

            return $this;
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue):bool {
            if ($mValue instanceof \stdClass) {
                if (property_exists($mValue, 'date')) { // coming from json
                    $mValue = $mValue->date;
                }
            }

            return parent::is($mValue);
        }
    }

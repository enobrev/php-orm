<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\Log;
    use Exception;
    use DateTime as PHP_DateTime;

    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\DateFunction;

    class Date extends Text {

        const DEFAULT_FORMAT = 'Y-m-d';
        const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var PHP_DateTime|DateFunction|null
         */
        public $sValue;

        /**
         * @return PHP_DateTime|null
         */
        public function getValue() {
            if ($this->sValue instanceof DateFunction) {
                return new PHP_DateTime();
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

            if ($this->sValue instanceof PHP_DateTime) {
                $sValue = $this->sValue->format(self::DEFAULT_FORMAT);
            } else if ($this->sValue instanceof DateFunction) {
                $sValue = (new PHP_DateTime())->format(self::DEFAULT_FORMAT);
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
            } else if ($this->sValue instanceof PHP_DateTime) {
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
                case $sValue instanceof PHP_DateTime:
                case $sValue instanceof DateFunction:
                    $this->sValue = $sValue;
                    break;

                case DateFunction::isSupportedType($sValue):
                    $this->sValue = DateFunction::createFromString($sValue);
                    break;

                default:
                    try {
                        $this->sValue = new PHP_DateTime($sValue);
                    } catch (Exception $e) {
                        if (stripos($e->getMessage(), 'Double time') !== false) { // GMT 0 dates end up with 00:00 appended to the date without any indication that it's for timezone.  This resolves that
                            $sValue = trim(preg_replace('/\d{1,2}:\d{2}$/', '', $sValue));
                            $this->sValue = new PHP_DateTime($sValue);
                        } else {
                            Log::e('Date.setValue', ['date' => $sValue, 'error' => $e]);
                            throw $e;
                        }
                    }
                    break;
            }

            return $this;
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue): bool {
            if ($mValue instanceof Table) {
                $mValue = $mValue->{$this->sColumn};
            }

            if ($mValue instanceof self) {
                $mValue = $mValue->getValue();
            }

            if ($mValue instanceof PHP_DateTime) {
                $mValue = $mValue->format(self::DEFAULT_FORMAT);
            }

            if ($mValue instanceof \stdClass) {
                if (property_exists($mValue, 'date')) { // coming from json
                    $mValue = $mValue->date;
                }
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            } else if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            return (string) $this == (string) (new self($this->sTable))->setValue($mValue);
        }
    }

<?php
    namespace Enobrev\ORM\Field;

    use Exception;
    use stdClass;
    use DateTime as PHP_DateTime;

    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use Enobrev\ORM\DateFunction;
    use Enobrev\ORM\Exceptions\DbException;

    class Date extends Text {

        protected const DEFAULT_FORMAT = 'Y-m-d';
        protected const NULL_VALUE     = '0000-00-00 00:00:00';

        /**
         * @var PHP_DateTime|DateFunction|null
         */
        public $sValue;

        /**
         * @return PHP_DateTime|null
         * @throws Exception
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
            return parent::hasValue() && (string) $this !== self::NULL_VALUE;
        }

        public function __toString(): string {
            $sValue = self::NULL_VALUE;

            if ($this->sValue instanceof PHP_DateTime) {
                $sValue = $this->sValue->format(self::DEFAULT_FORMAT);
            } else if ($this->sValue instanceof DateFunction) {
                $sValue = (new PHP_DateTime())->format(self::DEFAULT_FORMAT);
            }

            if (strpos($sValue, '-') === 0) {
                $sValue = self::NULL_VALUE;
            }
            
            return $sValue;
        }

        /**
         * @return string
         * @throws DbException
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            if ($this->sValue instanceof DateFunction) {
                return $this->sValue->getName();
            }

            if ($this->sValue instanceof PHP_DateTime) {
                return Escape::string($this->sValue->format('Y-m-d'));
            }

            return 'NULL';
        }

        /**
         * @param mixed $sValue
         *
         * @return $this
         * @throws Exception
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }
            
            switch(true) {
                case $sValue === 'null':
                case $sValue === 'NULL':
                case $sValue === self::NULL_VALUE:
                    $this->sValue = null;
                    break;

                case $sValue === null:
                case $sValue instanceof PHP_DateTime:
                case $sValue instanceof DateFunction:
                    $this->sValue = $sValue;
                    break;

                case $sValue === DateFunction::FUNC_NOW:
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
                            throw $e;
                        }
                    }
                    break;
            }

            return $this;
        }

        /**
         * @param mixed $mValue
         *
         * @return bool
         * @throws Exception
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

            if (($mValue instanceof stdClass) && property_exists($mValue, 'date')) { // coming from json
                $mValue = $mValue->date;
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            }

            if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            /** @noinspection TypeUnsafeComparisonInspection */
            return (string) $this == (string) (new self($this->sTable))->setValue($mValue);
        }
    }

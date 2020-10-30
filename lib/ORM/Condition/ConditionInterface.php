<?php
    namespace Enobrev\ORM\Condition;

    interface ConditionInterface {
        public function toSQL(): string;
        public function toSQLLog(): string;
        public function toKey(): string;
        public function __clone();
    }
<?php
    namespace Enobrev\ORM\Condition;

    trait ConditionKeyTrait {
        public function toKey(): string {
            $sKey = str_replace(' ', '_', $this->toSQL());
            return preg_replace('/[^a-zA-Z0-9_=<>!]/', '-', $sKey);
        }
    }
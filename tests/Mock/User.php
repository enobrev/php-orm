<?php
    namespace Enobrev\API\Mock;

    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class User extends Table {
        protected $sTitle = 'users';

        /** @var  Field\Id */
        public $user_id;

        /** @var  Field\Text */
        public $user_name;

        /** @var  Field\Text */
        public $user_email;

        /** @var  Field\DateTime */
        public $user_date_added;

        /** @var  Field\Boolean */
        public $happy;

        protected function init() {
            $this->addPrimaries(
                new Field\Id('user_id')
            );

            $this->addFields(
                new Field\Text('user_name'),
                new Field\Text('user_email'),
                new Field\DateTime('user_date_added'),
                new Field\Boolean('happy')
            );

            $this->happy->setDefault(false);
        }
    }
<?php
    namespace Enobrev\ORM\Mock;

    use Enobrev\ORM\Field\Boolean   as F_Boolean;
    use Enobrev\ORM\Field\DateTime  as F_DateTime;
    use Enobrev\ORM\Field\Id        as F_Id;
    use Enobrev\ORM\Field\Text      as F_Text;
    use Enobrev\ORM\Table;

    class User extends Table {
        protected $sTitle = 'users';

        /** @var  F_Id */
        public $user_id;

        /** @var  F_Text */
        public $user_name;

        /** @var  F_Text */
        public $user_email;

        /** @var  F_DateTime */
        public $user_date_added;

        /** @var  F_Boolean */
        public $happy;

        protected function init() {
            $this->addPrimaries(
                new F_Id('user_id')
            );

            $this->addFields(
                new F_Text('user_name'),
                new F_Text('user_email'),
                new F_DateTime('user_date_added'),
                new F_Boolean('happy')
            );

            $this->happy->setDefault(false);
        }
    }
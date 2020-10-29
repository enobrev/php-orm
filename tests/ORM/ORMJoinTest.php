<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';


    use Enobrev\ORM\Field;

    use Enobrev\ORM\Join;
    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;


    class ORMJoinTest extends TestCase {
        public function testLefTOuterJoin(): void {
            $oUsers = new ORMJoinTestUser;
            $oPosts = new ORMJoinTestPost;

            $oJoin = Join::create($oUsers->user_id, $oPosts->user_id);
            $this->assertEquals('LEFT OUTER JOIN posts ON users.user_id = posts.user_id', $oJoin->toSQL());
        }
    }



    class ORMJoinTestUser extends Table {
        protected string $sTitle = 'users';

        public Field\Integer $user_id;

        public static function getTables() {
            // TODO: Implement getTables() method.
        }

        protected function init(): void {
            $this->addFields(
                new Field\Integer('user_id')
            );
        }
    }

    class ORMJoinTestPost extends Table {
        protected string $sTitle = 'posts';

        public Field\Integer $post_id;
        public Field\Integer $user_id;

        public static function getTables() {
            // TODO: Implement getTables() method.
        }

        protected function init(): void {
            $this->addFields(
                new Field\Integer('post_id'),
                new Field\Integer('user_id')
            );
        }
    }
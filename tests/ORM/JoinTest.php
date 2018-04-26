<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';


    use Enobrev\ORM\Field;

    use Enobrev\ORM\Join;
    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;
 
    class MySQLJoinTest extends TestCase {
        public function setUp() {
        }
        
        public function testLefTOuterJoin() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            
            $oPosts = new Table('posts');
            $oPosts->addFields(
                new Field\Integer('post_id'),
                new Field\Integer('user_id')
            );

            $oJoin = Join::create($oUsers->user_id, $oPosts->user_id);
            $this->assertEquals('LEFT OUTER JOIN posts ON users.user_id = posts.user_id', $oJoin->toSQL());
        }
    }
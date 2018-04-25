<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';


    use Enobrev\ORM\Field;
    use Enobrev\ORM\Group;
    use Enobrev\ORM\Table;
    use PHPUnit_Framework_TestCase as TestCase;
 
    class MySQLGroupTest extends TestCase {
        public function setUp() {
        }
        
        public function testGroupOneField() {
            $oPosts = new Table('posts');
            $oPosts->addFields(
                new Field\Integer('post_id'),
                new Field\Integer('user_id')
            );

            $oGroup = Group::create($oPosts->post_id);
            $this->assertEquals('posts.post_id', $oGroup->toSQL());
        }

        public function testGroupTwoFields() {
            $oPosts = new Table('posts');
            $oPosts->addFields(
                new Field\Integer('post_id'),
                new Field\Integer('user_id')
            );

            $oGroup = Group::create($oPosts->post_id, $oPosts->user_id);
            $this->assertEquals('posts.post_id, posts.user_id', $oGroup->toSQL());
        }
    }
<?php
    include __DIR__ . '/../../vendor/autoload.php';

    use Enobrev\ORM\Mock\Table\User;

    $aUsers = [];
    for ($i = 1; $i < 10000; $i++) {
        $aUsers[] = [
            'user_id'           => $i,
            'user_name'         => "test_$i",
            'user_email'        => "test_$i@test.com",
            'user_happy'        => rand(0,1) === 1,
            'user_date_added'   => (new DateTime())->setTimestamp(random_int(1, time()))->format('Y-m-d H:i:s')
        ];
    }

    foreach($aUsers as $aUser) {
        User::createFromArray($aUser);
    }

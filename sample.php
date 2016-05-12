<?php 
    require('dbconnect.php');

    // ① sql文を作成
    $sql = 'SELECT * FROM `members`'; // メンバーデータ全件取得

    // ② sql文を実行
    $members = mysqli_query($db, $sql) or die(mysqli_error($db)); // SELECT文の場合は取得したデータを変数で受け取る

    // query実行直後のデータはobject型という型になっていて、そのままでは扱うことができない
    var_dump($members);

    // ③ データを処理・表示
    // $member = mysqli_fetch_assoc($members);
    // $member = array('id' => '1', 'nick_name' => 'hogehoge', 'email' => 'hoge@gmail.com' .......);
    // var_dump($member);

    // プログラミングの重要概念
    // 変数・配列・型・条件分岐文・繰り返し構文
    while ($member = mysqli_fetch_assoc($members)) {
      // var_dump($member);
      echo 'ユーザー名 : ' . $member['nick_name'] . ' - ID : ' . $member['email'];
      echo '<br>';
    }
 ?>

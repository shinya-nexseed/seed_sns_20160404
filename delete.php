<?php 
  session_start();
  require('dbconnect.php');

  // ログイン判定
  if (isset($_SESSION['id'])) {
    
    $id = $_REQUEST['id'];

    // 投稿を検査する

    // パラメータのidの値を元に削除したいデータを取得
    $sql = sprintf('SELECT * FROM `tweets` WHERE `tweet_id`=%d',
        mysqli_real_escape_string($db, $id)
      );
    $record = mysqli_query($db, $sql) or die(mysqli_error($db));
    $table = mysqli_fetch_assoc($record);

    // 削除したい投稿データの投稿者idとログインしているユーザーのidが一致する場合のみ削除が可能
    if ($table['member_id'] == $_SESSION['id']) {
      // 削除
      $sql = sprintf('DELETE FROM `tweets` WHERE `tweet_id`=%d',
          mysqli_real_escape_string($db, $id)
        );
      mysqli_query($db, $sql) or die(mysqli_error($db));
    }
  }

  header('Location: index.php');
  exit();
?>

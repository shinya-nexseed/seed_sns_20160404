<?php 

  $db = mysqli_connect('localhost', 'root', 'mysql', 'seed_sns_20160404') or die(mysqli_connect_error());
  // mysqli_connect('DBのホスト名','DBのユーザー名','DBのパスワード','DB名')
  mysqli_set_charset($db,'utf8');

  // or die()という書き方について
  // or の左側に記述してあるコードがfalseを返すとき、右側の処理を実行する
  // die()が実行されると()内のデータを出力する。

  // mysqil_connect_error()関数は、DBとの接続時に出たエラーを取得する関数

  // PDOとmysqli関数群の違い
  // PDOはDBの種類が何であれ等しく実行できる命令文
  // mysqli関数群は、DBの種類がMySQLの場合に限り実行できる命令文

?>

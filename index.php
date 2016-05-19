<?php 
  session_start();
  require('dbconnect.php');

  // セッションにidが存在し、かつセッションのtimeと3600秒足した値が
  // 現在時刻より小さいときにログインしていると判定する
  if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // $_SESSIONに保存している時間更新
    $_SESSION['time'] = time();

    // ログインしているユーザーのデータをDBから取得 ($_SESSION['id']を使用して)
    $sql = sprintf('SELECT * FROM `members` WHERE `member_id`=%d',
      mysqli_real_escape_string($db, $_SESSION['id'])
      );

    $record = mysqli_query($db, $sql) or die (mysqli_error());
    $member = mysqli_fetch_assoc($record);
    
  } else {
    // ログインしていない場合の処理
    header('Location: login.php');
    exit();
  }

  // 投稿を記録する
  if (!empty($_POST)) {
    if ($_POST['tweet'] != '') {
      $sql = sprintf('INSERT INTO `tweets` SET `tweet`="%s", `member_id`=%d, `reply_tweet_id`=%d, `created`= now()',
        mysqli_real_escape_string($db, $_POST['tweet']),
        mysqli_real_escape_string($db, $member['member_id']),
        mysqli_real_escape_string($db, $_POST['reply_tweet_id'])
        );
      mysqli_query($db, $sql) or die(mysqli_error($db));

      header('Location: index.php');
      exit();
    }
  }

  // echo '<br>';
  // echo '<br>';
  // echo '<br>';

  // 投稿を取得する
  $page = '';
  $countNum = 3;
  // URLのパラメータにpageが存在していれば$pageに代入
  if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
    // echo 'page1 = ' . $page . '<br>';
  }
  
  if ($page == '') {
    $page = 1;
    // echo 'page2 = ' . $page . '<br>';
  }
  // 0.4などの数値をユーザーが直接URLに入れたときのため最小値の1と比較する
  $page = max($page, 1);
  // max()関数はカッコ内に与えられた数値のうち、一番大きい数値を返す関数
  // echo 'page3 = ' . $page . '<br>';

  // 最終ページを取得する
  if (!empty($_REQUEST['search_word'])) {
    // 検索ボタンを押された時
    $sql = sprintf('SELECT COUNT(*) 
                    AS cnt 
                    FROM `tweets` 
                    WHERE tweet 
                    LIKE "%%%s%%"', 
                      mysqli_real_escape_string($db, $_REQUEST['search_word'])
      );
  } else {
    $sql = 'SELECT COUNT(*) AS cnt FROM `tweets`';
  }
  

  $recordSet = mysqli_query($db, $sql);
  $table = mysqli_fetch_assoc($recordSet);
  // var_dump($table);
  // 最大ページ数をデータの数÷5で取得
  $maxPage = ceil($table['cnt'] / $countNum);
  // ceil()関数は指定した数値を切り上げて返す
  // echo 'maxPage = ' . $maxPage . '<br>';

  // パラメータに最大ページ数以上の数値が入力された場合に最大ページ数で上書きするため
  $page = min($page, $maxPage);
  // min()関数はカッコ内に与えられた数値のうち、一番小さい数値を返す関数
  // echo 'page4 = ' . $page . '<br>';

  // DB内tweetsテーブルデータの取得開始場所を指定する変数$startを定義
  $start = ($page - 1) * $countNum;
  // echo 'start1 = ' . $start . '<br>';
  $start = max(0, $start);
  // echo 'start2 = ' . $start . '<br>';

  // 検索ボタンが押された時
  if (!empty($_REQUEST['search_word'])) {
    $sql = sprintf('SELECT m.nick_name, m.picture_path, t.* 
            FROM `tweets` t, `members` m 
            WHERE t.member_id=m.member_id 
            AND t.tweet LIKE "%%%s%%"
            ORDER BY t.created DESC
            LIMIT %d,%d',
              mysqli_real_escape_string($db, $_REQUEST['search_word']),
              $start,
              $countNum
            );
  } else {
    $sql = sprintf('SELECT m.nick_name, m.picture_path, t.* 
            FROM `tweets` t, `members` m 
            WHERE t.member_id=m.member_id 
            ORDER BY t.created DESC
            LIMIT %d,%d',
              $start,
              $countNum
            );
  }

  // LIMIT句の構文
  // LIMIT データ取得開始位置, データ取得件数
  // LIMIT 0,5 ← 最初から5件取得
  // LIMIT 5,5 ← 6件目から5件取得
  // LIMIT 10,20 ← 11件目から20件取得

  $tweets = mysqli_query($db, $sql) or die(mysqli_error($db));
  // while ($tweet = mysqli_fetch_assoc($tweets)) {
  //   var_dump($tweet);
  //   echo $tweet['nick_name'];
  //   echo $tweet['tweet'];
  //   echo '<hr>';
  // }

  // 返信の場合
  if (isset($_REQUEST['res'])) {
    $sql = sprintf('SELECT m.nick_name, m.picture_path, t.* FROM `tweets` t, `members` m WHERE t.member_id = m.member_id AND t.tweet_id = %d ORDER BY t.created DESC',
      mysqli_real_escape_string($db, $_REQUEST['res'])
    );
    $record = mysqli_query($db, $sql);
    $table = mysqli_fetch_assoc($record);
    $tweet = '>> @'.$table['nick_name'].' '.$table['tweet'];
  }

  // htmlspecialcharsのショートカット
  function h($value){
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  // 実装した機能を保ちつつ、コードの可動性をあげることを「リファクタリング」と言います。
  // ①機能をどんな形でも良いので実装する
  // ②コードの可動性をあげるために修正します
  // ③修正した状態で機能がしっかりと動くか確認

  // 本文内のURLにリンクを設定します
  function makeLink($value){
    return mb_ereg_replace('(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)','<a href="\1\2" target="_blank">\1\2</a>', $value);
  }

?>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>SeedSNS</title>

    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="assets/css/form.css" rel="stylesheet">
    <link href="assets/css/timeline.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
  <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <div class="navbar-header page-scroll">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                  <span class="sr-only">Toggle navigation</span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="index.html"><span class="strong-title"><i class="fa fa-twitter-square"></i> Seed SNS</span></a>
          </div>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav navbar-right">
                <li><a href="user_edit.php">会員情報</a></li>
                <li><a href="logout.php">ログアウト</a></li>
              </ul>
          </div>
          <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-md-4 content-margin-top">
        <legend>ようこそ <?php echo h($member['nick_name']); ?>さん！</legend>
        <form method="post" action="" class="form-horizontal" role="form">
            <!-- つぶやき -->
            <div class="form-group">
              <label class="col-sm-4 control-label">つぶやき</label>
              <div class="col-sm-8">
                <?php if(isset($tweet)): ?>
                  <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"><?php echo h($tweet); ?></textarea>
                  <input type="hidden" name="reply_tweet_id" value="<?php echo h($_REQUEST['res']); ?>">
                <?php else: ?>
                  <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"></textarea>
                <?php endif; ?>
              </div>
            </div>
          <ul class="paging">
            <input type="submit" class="btn btn-info" value="つぶやく">
              
              <!-- 検索時のパラメータ文字列を作成 -->
              <?php
                $search_word = '';
                if (!empty($_REQUEST['search_word'])) {
                  $search_word = '&search_word=' . $_REQUEST['search_word'];
                }
              ?>

              &nbsp;&nbsp;&nbsp;&nbsp;
              <?php if($page > 1): ?>
                <!-- パラメータpageの値が1以上であれば「前」ボタンを表示 -->
                <li><a href="index.php?page=<?php print($page - 1); ?><?php print $search_word; ?>" class="btn btn-default">前</a></li>
              <?php else: ?>
                <!-- そうでなければ、1ページ目ということになるので「前」の文字のみ表示 -->
                <li>前</li>
              <?php endif; ?>

              &nbsp;&nbsp;|&nbsp;&nbsp;
              <?php if($page < $maxPage): ?>
                <!-- パラメータpageの値が最終ページ数以下であれば「次」ボタンを表示 -->
                <li><a href="index.php?page=<?php print($page + 1); ?><?php print $search_word; ?>" class="btn btn-default">次</a></li>
              <?php else: ?>
                <!-- そうでなければ、最終ページということになるので「次」の文字のみ表示 -->
                <li>次</li>
              <?php endif; ?>
          </ul>
        </form>
      </div>

      <div class="col-md-8 content-margin-top">
        <!-- 検索窓設置 -->
        <form method="get" action="" class="form-horizontal" role="form">
          
          <?php if(!empty($_REQUEST['search_word'])): ?>
            <input type="text" name="search_word" 
            value="<?php echo h($_REQUEST['search_word']); ?>">&nbsp;&nbsp;
          <?php else: ?>
            <input type="text" name="search_word" value="">&nbsp;&nbsp;
          <?php endif; ?>
          
          <input type="submit" class="btn btn-success btn-xs" value="検索">
        </form>
        <?php while($tweet = mysqli_fetch_assoc($tweets)): ?>
          <div class="msg">
            <img src="member_picture/<?php echo h($tweet['picture_path']); ?>" width="48" height="48">
            <p>
              <?php echo makeLink(h($tweet['tweet'])); ?>
              <span class="name">
                 (<?php echo h($tweet['nick_name']); ?>) 
               </span>
              [<a href="index.php?res=<?php echo h($tweet['tweet_id']); ?>">Re</a>]
            </p>
            <p class="day">
              <a href="view.php?id=<?php echo h($tweet['tweet_id']); ?>">
                <?php echo h($tweet['created']); ?>
              </a>
              <?php if($tweet['reply_tweet_id'] > 0): ?>
                <a href="view.php?id=<?php echo h($tweet['reply_tweet_id']); ?>"> | 返信元のつぶやき</a>
              <?php endif; ?>
              
              <?php if($member['member_id'] == $tweet['member_id']): ?>
                [<a href="edit.php?id=<?php echo h($tweet['tweet_id']); ?>" style="color: #00994C;">編集</a>]
                [<a href="delete.php?id=<?php echo h($tweet['tweet_id']); ?>" style="color: #F33;">削除</a>]
              <?php endif; ?>
            </p>
          </div>
        <?php endwhile; ?>
      </div>

    </div>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>

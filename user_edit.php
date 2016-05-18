<?php 
  // DBやセッションの設定
  require('dbconnect.php');
  session_start();

  // ログイン判定
  if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    $_SESSION['time'] = time();
    $sql = sprintf('SELECT * FROM `members` WHERE `member_id`=%d',
      mysqli_real_escape_string($db, $_SESSION['id'])
      );
    $record = mysqli_query($db, $sql) or die (mysqli_error());
    $member = mysqli_fetch_assoc($record);
  } else {
    header('Location: login.php');
    exit();
  }

  $error = Array();

  // $_POSTがある場合 (更新ボタンが押された際の処理)
  if (!empty($_POST)) {
  
    // バリデーション
    if ($_POST['nick_name'] == '') {
      $error['nick_name'] = 'blank';
    }
    if ($_POST['email'] == '') {
      $error['email'] = 'blank';
    }
    if ($_POST['password'] == '') {
      $error['password'] = 'blank';
    // DBに登録されているパスワードと入力されたパスワードが一致するかどうか
    } else if (sha1($_POST['password']) != $member['password']) {
      $error['password'] = 'incorrect';
    }

    $fileName = $_FILES['picture_path']['name'];
    if (!empty($fileName)) {
      $ext = substr($fileName, -3);
      if ($ext != 'jpg' && $ext != 'gif' && $ext != 'png') {
        $error['picture_path'] = 'type';
      }
    }

    // 重複アカウントのチェック
    if (empty($error)) {
      if ($_POST['email'] != $member['email']) {
        $sql = sprintf(
            'SELECT COUNT(*) AS cnt FROM members WHERE email="%s"',
            mysqli_real_escape_string($db,$_POST['email'])
          );
        $record = mysqli_query($db,$sql) or die(mysqli_error($db));
        $table = mysqli_fetch_assoc($record);

        if ($table['cnt'] > 0) {
          $error['email'] = 'duplicate';
        }
      }
    }

    // エラーがなければ
    if (empty($error)) {
      // 画像が選択されていれば
      if (!empty($fileName)) {
        // 画像のアップロード
        $picture = date('YmdHis') . $_FILES['picture_path']['name'];
        move_uploaded_file($_FILES['picture_path']['tmp_name'], 'member_picture/' . $picture);
      } else {
        $picture = $member['picture_path'];
      }
      // アップデート処理
      $sql = sprintf('UPDATE `members` SET `nick_name`="%s", `email`="%s", `picture_path`="%s", modified=NOW() WHERE `member_id`=%d',
          mysqli_real_escape_string($db, $_POST['nick_name']),
          mysqli_real_escape_string($db, $_POST['email']),
          mysqli_real_escape_string($db, $picture),
          mysqli_real_escape_string($db, $member['member_id'])
        );
      mysqli_query($db, $sql) or die(mysqli_error($db));
    }

  }

  // ユーザー情報の取得
  $sql = sprintf('SELECT * FROM `members` WHERE `member_id`=%d',
      mysqli_real_escape_string($db, $_SESSION['id'])
    );
  $record = mysqli_query($db, $sql) or die(mysqli_error($db));
  $member = mysqli_fetch_assoc($record);

  // htmlspecialcharsのショートカット
  function h($value){
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
    <!--
      designフォルダ内では2つパスの位置を戻ってからcssにアクセスしていることに注意！
     -->


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
              <a class="navbar-brand" href="index.php"><span class="strong-title"><i class="fa fa-twitter-square"></i> Seed SNS</span></a>
          </div>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav navbar-right">
              </ul>
          </div>
          <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-md-6 col-md-offset-3 content-margin-top">
        <legend>会員情報編集</legend>
        <form method="post" action="" class="form-horizontal" role="form" enctype="multipart/form-data">
          <!-- ニックネーム -->
          <div class="form-group">
            <label class="col-sm-4 control-label">ニックネーム</label>
            <div class="col-sm-8">
            <?php if (isset($_POST['nick_name'])): ?>
              <input type="text" name="nick_name" class="form-control" placeholder="例： Seed kun" value="<?php echo htmlspecialchars($_POST['nick_name'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
              <input type="text" name="nick_name" class="form-control" placeholder="例： Seed kun" value="<?php echo h($member['nick_name']); ?>">
            <?php endif; ?>
              <?php if (isset($error['nick_name']) && $error['nick_name'] == 'blank'): ?>
                <p class="error">* ニックネームを入力してください。</p>
              <?php endif; ?>
            </div>
          </div>
          <!-- メールアドレス -->
          <div class="form-group">
            <label class="col-sm-4 control-label">メールアドレス</label>
            <div class="col-sm-8">
            <?php if (isset($_POST['email'])): ?>
              <input type="email" name="email" class="form-control" placeholder="例： seed@nex.com" value="<?php echo htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
              <input type="email" name="email" class="form-control" placeholder="例： seed@nex.com" value="<?php echo h($member['email']); ?>">
            <?php endif; ?>
            <?php if (isset($error['email']) && $error['email'] == 'blank'): ?>
              <p class="error">* メールアドレスを入力してください。</p>
            <?php endif; ?>
            <?php if (isset($error['email']) && $error["email"] == 'duplicate'): ?>
                <p class="error">* 指定されたメールアドレスはすでに登録されています。</p>
            <?php endif; ?>
            </div>
          </div>
          <!-- パスワード -->
          <div class="form-group">
            <label class="col-sm-4 control-label">パスワード</label>
            <div class="col-sm-8">
            <?php if (isset($_POST['password'])): ?>
              <input type="password" name="password" class="form-control" placeholder="" value="<?php echo htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
              <input type="password" name="password" class="form-control" placeholder="" value="">
            <?php endif; ?>
            <?php if (isset($error['password']) && $error['password'] == 'blank'): ?>
              <p class="error">* パスワードを入力してください。</p>
            <?php endif; ?>
            <?php if (isset($error['password']) && $error['password'] == 'incorrect'): ?>
              <p class="error">* パスワードが間違っています。</p>
            <?php endif; ?>
            </div>
          </div>
          <!-- プロフィール写真 -->
          <div class="form-group">
            <label class="col-sm-4 control-label">プロフィール写真</label>
            <div class="col-sm-8">
              <img src="member_picture/<?php echo h($member['picture_path']); ?>" width="100" height="100">
              <input type="file" name="picture_path" class="form-control">
              <?php if (isset($error['picture_path']) && $error['picture_path'] == 'type'): ?>
                <p class="error">* プロフィール写真には「.gif」「.jpg」「.png」の画像を指定してください。</p>
              <?php endif; ?>
              <?php if (!empty($error)): ?>
                <p class="error">* 画像を指定していた場合は恐れ入りますが、画像を改めて指定してください。</p>
              <?php endif; ?>
            </div>
          </div>

          <input type="submit" class="btn btn-default" value="確認画面へ">
        </form>
      </div>
    </div>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
  </body>
</html>

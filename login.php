<?php
$user_name  = '';
$password   = '';
$page       = '';
$err_msg    = array();
// DB用の変数
$host     = 'localhost';
$username = 'codecamp34900';
$password = 'codecamp34900';
$dbname   = 'codecamp34900';
$charset  = 'utf8';

session_start();
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;
// DBに接続
try {
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST['user_name']) === TRUE) {
            $user_name = trim($_POST['user_name']);
        }
        if(isset($_POST['password']) === TRUE) {
            $password = trim($_POST['password']);
        }
        
        try {
            $sql = 'SELECT user_id
                    FROM ec_user
                    WHERE user_name = ?
                    AND   password = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $password,  PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetchAll();
        } catch (PDOException $e) {
            $err_msg[] = '処理失敗しました。。理由：'.$e->getMessage();
        }
// if (count($data) === 0)もOK？
        if (isset($data[0]['user_id']) !== TRUE) {
            $err_msg[] = 'ユーザー名あるいはパスワードが違います' ;
        } else if ($user_name === 'admin' && $password === 'admin') {
// セッション変数にuser_idを保存
    // $_SESSION['user_id'] = $data[0]['user_id'];
    // 管理者のuser_idの番号を文字列に変える
            $_SESSION['user_id'] = 'admin';
            header('Location: admin.php');
            exit;
        } else {
            $_SESSION['user_id'] = $data[0]['user_id'];
            header('Location: top.php');
            exit;
        }
    }
} catch (PDOException $e) {
    echo '接続できませんでした。理由:'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログインページ</title>
    <style>
        body {
            margin: 0;
        }
        a {
            text-decoration: none;
            color: black;
        }
        header {
            border-bottom: solid 1px silver;
            height: 100px;
        }
        .inner {
            width: 990px;
            margin: 0 auto;
            display: flex;
        }
        .inner ul {
            list-style-type: none;
            display: flex;
            margin-left: auto;
            margin-top: 30px;
        }
        .inner ul li a{
            display: inline-block;
            background-color: #eeeeee;
            border-radius: 20px;
            border: solid 1px #222222;
            color: #222222;
            font-weight: bold;
            font-size: 14px;
            padding: 4px 12px;
            line-height: 30px;
            height: 30px;
        }
        .inner ul li a:hover {
            background-color: #222222;
            color: white;
        }
        .inner ul li:not(:first-child) {
            margin-left: 30px;
        }
        .inner h1 {
            font-family: 'Bangers', cursive;
            line-height: 60px;
        }
        .inner h1:hover {
            opacity: 0.7;
        }
        .inner h1 span {
            color: #FF9966;
        }
/*メイン*/
        main {
            width: 990px;
            margin: 25px auto 150px;
            padding: 3px 25px 0 25px;
            height: 600px;
        }
        main section {
            display: flex;
        }
        .login {
            margin-right: 200px;
            width: 600px;
        }
        .login p {
            font-size: 13px;
        }
        span {
            color: grey;
        }
        .sign-up {
            padding-left: 45px;
            border-left: solid 1px silver;
        }
        .sign-up p {
            font-size: 13px;
        }
        .sign-up a {
            color: #222222;
            font-weight: bold;
            border-bottom: solid 1px #222222;
        }
        .sign-up a:hover {
            border-bottom: none;
        }
        .err-msg {
            list-style-type: none;
            padding-left: 0;
            color: red;
        }
/*フッター*/
        footer {
            border-top: solid 1px silver;
            background-color: #f6f6f6;
        }
        .footer-content {
            width: 990px;
            margin: 0 auto;
            background-color: #f6f6f6;
        }
        .footer-content ul {
            list-style-type: none;
            display: flex;
            font-size:10px;
        }
        .footer-content ul li {
            padding-right: 10px;
        }
        .footer-content ul li:first-child {
            margin-left: auto;
        }
        .footer-content ul li:last-child {
            border-right: 0;
        }
        .footer-content ul li:not(:first-child) {
            margin-left: 12px;
        }
        .footer-content ul li:hover {
            border-bottom: solid 1px;
        }
        .footer-content p {
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <header>
        <div class="inner">
            <h1><a href="top.php">A<span>C</span>OUSTI<span>C</span> <span>C</span>AMP</a></h1>
            <ul>
                <li><a href="login.php">ログイン</a></li>
                <li><a href="register.php">新規会員登録</a></li>
            </ul>
        </div>
    </header>
    <main>
        <h4>ログイン</h4>
        <section>
            <div class="login">
                <h4>オンラインストア会員のお客様</h4>
                <p>ご登録いただいたユーザー名とパスワードを入力してログインしてください。<span>（半角英数字）</span></p>
                <form method="post">
                    <p>ユーザー名:&emsp;<input type="text" name="user_name" placeholder="ユーザー名"></p>
                    <p>パスワード:&emsp;<input type="password" name="password" placeholder="パスワード"></p>
                    <p><input type="submit" value="ログイン"></p>
                </form>
        <?php if (count($err_msg) > 0) { ?>
                <ul>
            <?php foreach ($err_msg as $value) { ?>
                    <li class="err-msg"><?php print $value; ?></li>
            <?php } ?>
                </ul>
        <?php } ?>
            </div>
            <div class="sign-up">
                <h4>新規会員登録</h4>
                <p>当サイトをご利用いただくには、会員登録が必要となります。「会員登録する」を押して登録画面へ進んでください。</p>
                <p><a href="register.php">会員登録する</a></a></p>
            </div>
        </section>
    </main>
    <footer>
        <div class="footer-content">
            <ul>
                <li><a href="#">ご利用規定</a></li>
                <li><a href="#">特定商取引に基づく表記</a></li>
                <li><a href="#">プライバシーポリシー</a></li>
            </ul>
            <p>Copyright© acoustic All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
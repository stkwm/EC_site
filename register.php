<?php 
$user_name  = '';
$password   = '';
$err_msg    = array();
$date       = date('Y-m-d H:i:s');
$msg        = '';
$data       = array();
// DB用の変数
$host     = 'localhost';
$username = 'codecamp34900';
$password = 'codecamp34900';
$dbname   = 'codecamp34900';
$charset  = 'utf8';

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
//  エラーメッセージ
        if ($user_name === '') {
            $err_msg[] = 'ユーザー名を入力してください';
        } else if (preg_match('/^[a-zA-Z0-9]{6,}$/', $user_name) !== 1) {
            $err_msg[] = 'ユーザー名は6文字以上の文字を入力してください';
        }
        if ($password === '') {
            $err_msg[] = 'パスワードを入力してください';
        } else if (preg_match('/^[a-zA-Z0-9]{6,}$/', $password) !== 1) {
            $err_msg[] = 'パスワードは6文字以上の文字を入力してください';
        }
        
        if (count($err_msg) === 0) {
            try {
                $sql = 'SELECT user_id
                        FROM ec_user
                        WHERE user_name = ?';
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
                $stmt->execute();
                $data = $stmt->fetchAll();
            } catch (PDOException $e) {
                $err_msg[] = '処理失敗しました。理由：'.$e->getMessage();
            }
// if (isset($data[0]['user_id']) === TRUE)もいいがcountのほうが単純明快
            if (count($data) > 0) {
                    $err_msg[] = '同じユーザー名が既に登録されています' ;
            }
        }
        
        if (count($err_msg) === 0) {
            try {
                $sql = 'INSERT INTO ec_user (user_name, password, create_datetime, update_datetime)
                        VALUES (?, ?, ? ,?)';
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
                $stmt->bindvalue(2, $password, PDO::PARAM_STR);
                $stmt->bindvalue(3, $date, PDO::PARAM_STR);
                $stmt->bindvalue(4, $date, PDO::PARAM_STR);
                $stmt->execute();
                $msg = 'アカウント作成を完了しました';
            } catch (PDOException $e) {
                $err_msg[] = '処理失敗しました。理由：'.$e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    $err_msg[] = '接続できませんでした。理由：'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー登録ページ</title>
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
        main { 
            width: 990px;
            margin: 25px auto 150px;
            padding: 3px 25px 0 25px;
            height: 600px;
        }
        .submit {
            margin-top: 30px;
        }
        main input[type="submit"] {
            height: 35px;
            width: 200px;
        }
        main span {
            color: grey;
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
            height: 100px;
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
        <form method="post">
            <h3>新規会員登録</h3>
            <p>ユーザー名とパスワードを入力いただき、「ユーザーを新規作成する」を押してください。<span>(半角数字)</span></p>
            <p>ユーザー名:&emsp;<input type="text" name="user_name" placeholder="ユーザー名"></p>
            <p>パスワード:&emsp;<input type="password" name="password" placeholder="パスワード"></p>
            <p class="submit"><input type="submit" value="ユーザーを新規作成する"></p>
        </form>
<?php if (count($err_msg) > 0) { ?>
        <ul>
    <?php foreach($err_msg as $value) { ?>
            <li class="err-msg"><?php print $value; ?></li>
    <?php } ?>
        </ul>
<?php } ?>
<?php if ($msg !== '') { ?>
        <p><?php print $msg; ?></p>
        <p><a href="login.php">ログインページに移動</a></p>
<?php } ?>
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
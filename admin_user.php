<?php
$data    = array();
$err_msg = array();
// DB用の変数
$host     = 'localhost';
$username = 'codecamp34900';
$password = 'codecamp34900';
$dbname   = 'codecamp34900';
$charset  = 'utf8';

$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

// ログインされていない場合、もしくは管理者ではない場合ログイン画面にジャンプする
session_start();
if (isset($_SESSION['user_id']) !== TRUE) {
    header('Location: login.php');
    exit;
} else if ($_SESSION['user_id'] !== 'admin') {
    header('Location: login.php');
    exit;
}
// DBに接続
try {
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    try {
        $sql = 'SELECT user_name, create_datetime
                FROM ec_user';
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $data = array_reverse($data);
    } catch (PDOException $e) {
        $err_msg[] = '処理失敗しました。。理由：'.$e->getMessage();
    }
} catch (PDOException $e) {
    $err_msg[] = '接続できませんでした。理由：'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー管理ページ</title>
    <style>
    main {
        width: 990px;
        margin: 0 auto;
    }
    a {
        text-decoration: none;
        color: black;
    }
    h1 {
        font-family: 'Bangers', cursive;
        line-height: 60px;
        margin-bottom: 0;
        }
    h1:hover {
        opacity: 0.7;
    }
    h1 span {
        color: #FF9966;
    }
    .header-content {
        display: flex;
        margin-bottom: 0;
    }
    .logout {
        margin-left: auto;
        margin-right: 40px;
        line-height: 85px;
        margin-bottom: 0;
    }
    .logout a {
        background-color: #eeeeee;
        border-radius: 20px;
        border: solid 1px #222222;
        color: #222222;
        font-weight: bold;
        font-size: 14px;
        padding: 8px;
    }
    .logout a:hover {
        background-color: #222222;
        color: white;
    }
    .items-data {
        margin-bottom: 30px;
    }
    .items-data a {
        border-bottom: solid 1px;
        color: blue;
    }
    .items-data a:hover {
        border-bottom: none;
    }
        
    table {
        border-collapse: collapse;
        width: 960px;
    }
    table, tr, th, td {
        border: solid 1px;
    }
    th, td {
        text-align: center;
        height: 50px;
    }
    </style>
</head>
<body>
    <main>
        <div class="header-content">
            <h1><a href="top.php">A<span>C</span>OUSTI<span>C</span> <span>C</span>AMP</a></h1>
            <p class="logout"><a href="logout.php">ログアウト</a></p>
        </div>
        <p class="items-data"><a href="admin.php" target="_blank">商品管理ページ</a></p>
<?php if (count($err_msg) > 0) { ?>
        <ul>
    <?php foreach($err_msg as $value) { ?>
            <li><?php print $value; ?></li>
    <?php } ?>
        </ul>
<?php } ?>
        <h2>ユーザー情報一覧</h2>
        <table>
            <tr>
                <th>ユーザーID</th>
                <th>登録日</th>
            </tr>
<?php foreach ($data as $value) { ?>
            <tr>
                <td><?php print $value['user_name'] ?></td>
                <td><?php print $value['create_datetime'] ?></td>
            </tr>
<?php } ?>
        </table>
    </main>
</body>
</html>
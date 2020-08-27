<?php
$user_id        = '';
$err_msg        = '';
$data           = array();
$user_name_info = array();
$cart_num       = array();
$date           = date('Y-m-d H:i:s');
// アップロードした画像の保存ディアレクトリ
$img_dir  = './img/';
// DB用の変数
$host     = 'localhost';
$username = 'codecamp34900';
$password = 'codecamp34900';
$dbname   = 'codecamp34900';
$charset  = 'utf8';

$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

// ログインされていない場合、ログイン画面にジャンプする。
// ログインされていれば、$user_idの変数に代入してその値を取得する
session_start();
if (isset($_SESSION['user_id']) === TRUE) {
    $user_id = $_SESSION['user_id'];
} else {
    header('Location: login.php');
    exit;
}
// DBに接続
try {
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    try {
// ユーザー名を調べる
        $sql = 'SELECT user_name
                FROM ec_user
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id,   PDO::PARAM_STR);
        $stmt->execute();
        $user_name_info = $stmt->fetchAll();
        
// カートに入れた数を調べる
        $sql = 'SELECT item_id
                FROM ec_cart
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $cart_num = $stmt->fetchAll();
        
// 購入履歴のデータ
        $sql = 'SELECT name, price, img, ec_result_history.create_datetime
                FROM ec_item_master
                INNER JOIN ec_result_history
                ON ec_item_master.item_id = ec_result_history.item_id
                WHERE user_id = ?
                ORDER BY ec_result_history.update_datetime DESC';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll();
    } catch (PDOException $e) {
        $err_msg = '処理失敗しました。理由：'.$e->getMessage();
    }
} catch (PDOException $e) {
    echo '接続できませんでした。理由:'.$e->getMessage();
}
// print_r($data);
// exit;
// print_r($err_msg);
// exit;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ショッピングカート</title>
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
        }
        .inner {
            width: 990px;
            margin: 0 auto;
        }
        .inner p {
            text-align: right;
        }
/*ログアウト*/
        .inner p a {
            background-color: #eeeeee;
            border-radius: 20px;
            border: solid 1px #222222;
            color: #222222;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 8px;
        }
        .inner p a:hover {
            background-color: #222222;
            color: white;
        }
        .inner h1 {
            position: absolute; top: 10px;
            font-family: 'Bangers', cursive;
        }
        .inner h1:hover {
            opacity: 0.7;
        }
        .inner h1 span {
            color: #FF9966;
        }
        .inner ul {
            list-style-type: none;
            display: flex;
        }
        .inner ul li:first-child {
            margin-left: auto;
        }
        .inner ul li {
            margin-left: 15px;
            margin-top: 5px;
        }
        .inner li a {
            display: inline-block;
            background-color: #ffcc00;
            padding: 5px 0 5px 27px;
            background-image: url(img/shopping_cart.png);
            background-size: contain;
            background-repeat: no-repeat;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
        }
        .inner li a:hover {
            background-color: #FFFF00;
        }
        .user-name {
            font-weight: bold;
            line-height: 30px;
            margin-right: 25px;
        }
        .num {
            background-color: black;
            color: white;
            padding: 5px 10px 5px 10px;
        }
        main {
            width: 990px;
            margin: 0 auto;
            min-height: 800px;
            }
        h2 {
            margin: 40px 0;
        }
        img {
            height: 160px;
            width: 120px;
        }
        
        table {
            border-collapse: collapse;
            width: 960px;
        }
        th {
            border: solid 1px silver;
        }
        tr {
            border-bottom: solid 1px silver;
        }
        .item-title {
            height: 40px;
        }
        figure {
            display: flex;
        }
        figcaption {
            font-weight: bold;
            margin-left: 30px;
            font-weight: bold;
            margin-top: 68px;
        }
        .item-price {
            text-align: center;
        }
        .item-create-datetime {
            text-align: center;
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
            <p><a href="logout.php">ログアウト</a></p>
            <h1><a href="top.php">A<span>C</span>OUSTI<span>C</span> <span>C</span>AMP</a></h1>
            <ul>
    <?php if ($user_id === 'admin') { ?>
                <li class="user-name">ようこそadminさん</li>
    <?php } else { ?>
                <li class="user-name"><?php print 'ようこそ ' . $user_name_info[0]['user_name'] . ' さん'; ?></li>
    <?php } ?>
                <li><a href="cart2.php">ショッピングカート <span class="num"><?php print count($cart_num); ?></span></a></li>
            </ul>
        </div>
    </header>
    <main>
        <h2>購入履歴</h2>
<?php if ($err_msg !== '') { ?>
        <p><?php print $err_msg; ?></p>
<?php } else { ?>
    <?php if (count($data) === 0) { ?>
        <p>購入履歴はありません</p>
    <?php } else { ?>
        <table>
            <tr class="item-title">
                <th>商品</th>
                <th>価格</th>
                <th>購入日時</th>
            </tr>
        <?php foreach ($data as $value) { ?>
            <tr>
                <td>
                    <figure>
                        <img src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption><?php print $value['name']; ?></figcaption>
                    </figure>
                </td>
                <td class="item-price">&yen;<?php print $value['price']; ?></td>
                <td class="item-create-datetime"><?php print $value['create_datetime']; ?></td>
            </tr>
        <?php } ?>
        </table>
    <?php } ?>
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

<?php
$amount   = '';
$item_id  = '';
$msg      = '';
$date     = date('Y-m-d H:i:s');
$err_msg  = array();
$data     = array();
$user_name_info = array();
$user_id  = '';
// アップロードした画像の保存ディアレクトリ
$img_dir  = './img/';
// DB用の変数
$host     = 'localhost';
$username = 'codecamp34900';
$password = 'codecamp34900';
$dbname   = 'codecamp34900';
$charset  = 'utf8';

$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

// // ログインされていない場合、ログイン画面にジャンプする。
// // ログインされていれば、$user_idの変数に代入してその値を取得する
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
    
// POSTの値がある場合
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['get_post']) === TRUE) {
            $get_post = $_POST['get_post'];
        }
// 変更するボタンを押した場合、購入の数量を変更する
        if ($get_post === 'update_amount') {
            
            if (isset($_POST['amount']) === TRUE) {
                $amount = $_POST['amount'];
            }
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
// エラーメッセージ
            if ($amount === '') {
                $err_msg[] = '個数を入力してください';
            } else if (preg_match('/^([1-9][0-9]*|0)$/', $amount) !== 1) {
                $err_msg[] = '半角数字を入力してください';
            }
            if ($item_id === '') {
                $err_msg[] = '商品が選択されていません';
            } else if (preg_match('/^[1-9][0-9]*$/', $item_id) !== 1) {
                $err_msg[] = '商品が正しくありません';
            }
            
            if (count($err_msg) === 0) {
                try {
                    $sql = 'UPDATE ec_cart
                            SET amount = ?,
                                update_datetime = ?
                            WHERE item_id = ?
                            AND user_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $amount,    PDO::PARAM_STR);
                    $stmt->bindvalue(2, $date,    PDO::PARAM_STR);
                    $stmt->bindvalue(3, $item_id,   PDO::PARAM_STR);
                    $stmt->bindvalue(4, $user_id,   PDO::PARAM_STR);
                    $stmt->execute();
                    $msg = '更新しました';
                } catch (PDOException $e) {
                    $err_msg[] = '更新できませんでした。理由:'.$e->getMessage();
                }
            }
        }
        
// 削除ボタンを押した場合、カート一覧から商品を削除する
        if ($get_post === 'delete') {
            
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
            
            if ($item_id === '') {
                $err_msg[] = '商品が選択されていません';
            } else if (preg_match('/^[1-9][0-9]*$/', $item_id) !== 1) {
                $err_msg[] = '商品が正しくありません';
            }
            
            if (count($err_msg) === 0) {
                try {
                    $sql = 'DELETE 
                            FROM ec_cart
                            WHERE item_id = ?
                            AND user_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $item_id,   PDO::PARAM_STR);
                    $stmt->bindvalue(2, $user_id,   PDO::PARAM_STR);
                    $stmt->execute();
                    $msg = '削除しました';
                } catch (PDOException $e) {
                    $err_msg[] = '削除できませんでした。理由:'.$e->getMessage();
                }
            }
        }
    }
    
// DBを読み込む
    try {
        $sql = 'SELECT name, price, img, amount, ec_item_master.item_id
                FROM ec_item_master
                INNER JOIN ec_cart
                ON ec_item_master.item_id = ec_cart.item_id
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id,   PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll();
    
        $sql = 'SELECT user_name
                FROM ec_user
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id,   PDO::PARAM_STR);
        $stmt->execute();
        $user_name_info = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '処理失敗しました。理由:'.$e->getMessage();
    }
} catch (PDOException $e) {
    echo '接続できませんでした。理由:'.$e->getMessage();
}

$total = get_sum($data);

// function
function get_sum ($data) {
    $sum = 0;

    foreach ($data as $value) {
        $sum += $value['price'] * $value['amount'];
    }
    return $sum;
}
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
        .err-msg {
            color: red;
            list-style-type: none;
        }
        .msg {
            color: blue;
        }
        .no-item {
            color: red;
        }
        h2 {
            margin: 40px 0;
        }
        img {
            width: 120px;
            height: 160px;
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
            width: 120px;
        }
        .item-stock input[type="text"] {
            width: 90px;
            margin: 0 15px;
        }
        .item-stock input[type="submit"] {
            margin-left: 15px;
        }
        .item-delete input[type="submit"] {
            margin-left: 20px;
        }
        .total-price {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 30px 0 0;
        }
        .buy {
            margin-bottom: 80px;
            text-align: center;
        }
        .buy input[type="submit"] {
            font-size: 20px;
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
                <li><a href="cart2.php">ショッピングカート <span class="num"><?php print count($data); ?></span></a></li>
            </ul>
        </div>
    </header>
    <main>
        <h2>ショッピングカート</h2>
<!--エラーメッセージ-->
<?php if (count($err_msg) > 0) { ?>
        <ul>
    <?php foreach($err_msg as $value) { ?>
            <li class="err-msg"><?php print $value; ?></li>
    <?php } ?>
        </ul>
<?php } ?>
<?php if ($msg !== '') { ?>
    <p class="msg"><?php print $msg; ?></p>
<?php } ?>
<!--読み込んだデータが０なら、商品がない-->
<?php If (count($data) === 0) { ?>
        <p class="no-item">現在、ショッピングカートに入っている商品はありません。</p>
<?php } else { ?>
        <table>
            <tr class="item-title">
                <th>商品</th>
                <th>価格（税込）</th>
                <th>数量</th>
                <th>小計（税込）</th>
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
                <td class="item-stock">
                    <form method="post">
                        <input type="hidden" name="get_post" value="update_amount">
                        <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>">
                        <p><input type="text" name="amount" value="<?php print $value['amount']; ?>"></p>
                        <p><input type="submit" value="変更する"></p>
                    </form>
                </td>
    <!--商品の小計を出す-->
                <td class="item-price">&yen;<?php print $value['price'] * $value['amount']; ?></td>
                <td class="item-delete">
                    <form method="post">
                        <input type="hidden" name="get_post" value="delete">
                        <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>">
                        <input type="submit" value="削除">
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
        
        <div class="total-price">合計 &yen;<?php print $total; ?>（税込）</div>
        <form method="post" action="finish.php">
            <p class="buy"><input type="submit" value="購入する"></p>
        </form>
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

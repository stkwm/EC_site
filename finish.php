<?php
$user_id        = '';
$data           = array();
$err_msg        = array();
$user_name_info = array();
$cart_num       = array();
$result_history = array();
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
    
// POSTの値がある場合
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try{
            $sql = 'SELECT img, name, price, status, amount, stock, ec_item_master.item_id
                    FROM ec_item_master
                    INNER JOIN ec_item_stock
                    ON ec_item_master.item_id = ec_item_stock.item_id
                    INNER JOIN ec_cart
                    ON ec_item_master.item_id = ec_cart.item_id
                    WHERE user_id = ?
                    AND amount <> 0';
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetchAll();
// print_r($data);
// exit();
            if (count($data) === 0) {
                $err_msg[] = '購入する商品がありません';
            }
            
            foreach ($data as $value) {
                $price  = $value['price'];
                $name   = $value['name'];
                $status = $value['status'];
                $amount = $value['amount'];
                $stock  = $value['stock'];
        // エラーメッセージ
                if ($status !== 1) {
                    $err_msg[] = $name . 'の商品が見つかりません';
                }
                if ($stock <= 0) {
                    $err_msg[] = $name . 'の商品がありません';
                }
                if ($amount > $stock) {
                    $err_msg[] = $name . 'の在庫数は現在' . $stock . '個しかありません';
                }
            }
    
        // エラーメッセージが一つも無ければ$dataの商品の詳細をDBに書き込む
            
            if (count($err_msg) === 0) {
                foreach ($data as $value) {
                    $img     = $value['img'];
                    $name    = $value['name'];
                    $price   = $value['price'];
                    $amount  = $value['amount'];
                    $stock   = $value['stock'];
                    $item_id = $value['item_id'];
                  
                    try {
                        $dbh->beginTransaction();
                         
                        $sql = 'UPDATE ec_item_stock
                                SET stock = ?, update_datetime = ?
                                WHERE item_id = ?';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $stock - $amount, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $date,            PDO::PARAM_STR);
                        $stmt->bindvalue(3, $item_id,         PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $sql = 'DELETE
                                FROM ec_cart
                                WHERE user_id = ?
                                AND item_id = ?';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $item_id, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        
                        $sql = 'SELECT item_id
                                FROM ec_result_history
                                WHERE item_id = ?
                                AND user_id = ?';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $item_id, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $user_id, PDO::PARAM_STR);
                        $stmt->execute();
                        $result_history = $stmt->fetchAll();
                        
                        if (count($result_history) === 0) {
                            $sql = 'INSERT INTO ec_result_history (item_id, user_id, create_datetime, update_datetime)
                                    VALUES (?, ?, ?, ?)';
                            $stmt = $dbh->prepare($sql);
                            $stmt->bindvalue(1, $item_id, PDO::PARAM_STR);
                            $stmt->bindvalue(2, $user_id, PDO::PARAM_STR);
                            $stmt->bindvalue(3, $date,    PDO::PARAM_STR);
                            $stmt->bindvalue(4, $date,    PDO::PARAM_STR);
                            $stmt->execute();
                        } else {
                            $sql = 'UPDATE ec_result_history
                                    SET update_datetime = ?
                                    WHERE item_id = ?
                                    AND user_id = ?';
                            $stmt = $dbh->prepare($sql);
                            $stmt->bindvalue(1, $date,    PDO::PARAM_STR);
                            $stmt->bindvalue(2, $item_id, PDO::PARAM_STR);
                            $stmt->bindvalue(3, $user_id, PDO::PARAM_STR);
                            $stmt->execute();
                        }
                        
                        $dbh->commit();
                        
                    } catch (PDOException $e) {
                        $dbh->rollback();
                        $err_msg[] = '処理失敗しました。理由：'.$e->getMessage();
                    }
                }
            }
        } catch (PDOException $e) {
            $err_msg[] = 'エラーが発生しました。理由：'.$e->getMessage();;
        }
    } else {
        $err_msg[] = '不正なアクセスです';
    }
// DBを読み込む
// ユーザー情報
    try {
        $sql = 'SELECT user_name
                FROM ec_user
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $user_name_info = $stmt->fetchAll();
        
// カートに入っている数
        $sql = 'SELECT item_id
                FROM ec_cart
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $cart_num = $stmt->fetchAll();
        
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
    <title>購入完了ページ</title>
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
        .item-stock {
            
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
        .item-stock {
            text-align: center;
        }
        .total-price {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 30px 0 0;
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
            <h1><a href="top.php">A<span>C</span>OUSTIC<span>C</span> <span>C</span>AMP</a></h1>
            <ul>
    <?php if ($user_id === 'admin') { ?>
                <li>ようこそadminさん</li>
    <?php } else { ?>
                <li class="user-name"><?php print 'ようこそ' . $user_name_info[0]['user_name'] . 'さん'; ?></li>
    <?php } ?>
                <li><a href="cart2.php">ショッピングカート <span class="num"><?php print count($cart_num); ?></span></a></li>
            </ul>
        </div>
    </header>
    <main>
<?php if (count($err_msg) > 0) { ?>
        <ul>
    <?php foreach ($err_msg as $value) { ?>
            <li class="err-msg"><?php print $value; ?></li>
    <?php } ?>
        </ul>
<?php } else { ?>
        <h2>ご購入ありがとうございました</h2>
        <table>
            <tr class="item-title">
                <th>商品</th>
                <th>価格(税込)</th>
                <th>数量</th>
                <th>小計(税込)</th>
            </tr>
    <?php foreach ($data as $value) { ?>
            <tr>
                <td>
                    <figure>
                        <img src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption><?php print $value['name']; ?></figcaption>
                    </figure>
                </td>
                <td class="item-price">&yen;<?php print $value['price']; ?> (税込)</td>
                <td class="item-stock"><?php print $value['amount']; ?></td>
                <td class="item-price">&yen;<?php print $value['amount'] * $value['price']; ?> (税込)</td>
            </tr>
        </table>
    <?php } ?>
        <p class="total-price">合計 &yen;<?php print $total; ?>(税込)</p>
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
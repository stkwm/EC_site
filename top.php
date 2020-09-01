<?php
$date        = date('Y-m-d H:i:s');
$search_word = '';
$msg         = '';
$sql_kind    = '';
$item_id     = '';
$brand       = '';
$beginner    = '';
$mini_price  = '';
$max_price   = '';
$item_kind   = '';
$data           = array();
$get_amount     = array();
$cart_num       = array();
$err_msg        = array();
$search_err_msg = array();
$data_search    = array();
$price_data     = array();
$data_history   = array();
// ページングの変数　定数
define('MAX','15');
$data_sum  = '';
$max_page  = '';
// 初期化　値は１
$page      = 1;
$start     = '';
$view_page = array();
// アップロードした画像の保存ディアレクトリ
$img_dir    = './img/';
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
    
// POSTのデータが送られてきた場合
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['sql_kind']) === TRUE) {
            $sql_kind = $_POST['sql_kind'];
        }
    // 「カートに入れる」からPOSTの値が飛んできた場合
        if ($sql_kind === 'cart') {
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
// print_r($item_id);
// exit();
            if ($item_id === '') {
                $err_msg[] = '商品が見つかりません';
            } else if (preg_match('/^[1-9][0-9]*$/', $item_id) !== 1) {
                $err_msg[] = '商品が正しくありません';
            }
            if (count($err_msg) === 0) {
                try{
                    $sql = 'SELECT amount
                            FROM ec_cart
                            WHERE user_id = ?
                            AND   item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
                    $stmt->bindvalue(2, $item_id, PDO::PARAM_STR);
                    $stmt->execute();
                    $get_amount = $stmt->fetchAll();

                    if (count($get_amount) === 0) {
                        $sql = 'INSERT INTO ec_cart (user_id, item_id, amount, create_datetime, update_datetime)
                                VALUES (?, ?, ?, ?, ?)';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $item_id, PDO::PARAM_STR);
                        $stmt->bindvalue(3, 1,        PDO::PARAM_STR);
                        $stmt->bindvalue(4, $date,    PDO::PARAM_STR);
                        $stmt->bindvalue(5, $date,    PDO::PARAM_STR);
                        $stmt->execute();
                        $msg = 'カートに入れました';
                    } else {
                        $sql = 'UPDATE ec_cart
                                SET amount = ?
                                WHERE user_id = ?
                                AND   item_id = ?';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $get_amount[0]['amount'] + 1, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $user_id                    , PDO::PARAM_STR);
                        $stmt->bindvalue(3, $item_id                    , PDO::PARAM_STR);
                        $stmt->execute();
                        $msg = 'カートに入れました';
                    }
                } catch (PDOException $e) {
                   $err_msg[] = '処理失敗しました。理由:'.$e->getMessage();
                }
            }
        } 
    // 絞り込みからPOSTの値が飛んできた場合
        if ($sql_kind === 'price_search') {
            if (isset($_POST['mini_price']) === TRUE) {
                $mini_price = $_POST['mini_price'];
            }
            if (isset($_POST['max_price']) === TRUE) {
                $max_price = $_POST['max_price'];
            }
            
            if ($mini_price === '') {
                $search_err_msg[] = '下限金額を入力してください';
            } else if (preg_match('/^([1-9][0-9]*|0)$/', $mini_price) !== 1) {
                $search_err_msg[] = '半角数字で入力してください';
            }
            
            if ($max_price === '') {
                $search_err_msg[] = '上限金額を入力してください';
            } else if (preg_match('/^([1-9][0-9]*|0)$/', $max_price) !== 1) {
                $search_err_msg[] = '半角数字で入力してください';
            }
            
            if (count($search_err_msg) === 0) {
                try {
                    $sql = 'SELECT img, name, price, stock, ec_item_master.item_id
                            FROM ec_item_master
                            INNER JOIN ec_item_stock
                            ON ec_item_master.item_id = ec_item_stock.item_id
                            WHERE status = 1
                            AND   price BETWEEN ? AND ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $mini_price, PDO::PARAM_STR);
                    $stmt->bindvalue(2, $max_price,  PDO::PARAM_STR);
                    $stmt->execute();
                    $price_data = $stmt->fetchAll();
                    // var_dump($data);
                    if (count($price_data) === 0) {
                        $err_msg = 'ご指定の条件に一致するアイテムが見つかりませんでした。';
                    }
                } catch (PDOException $e) {
                   $err_msg[] = '処理失敗しました。理由:'.$e->getMessage();
                }
            }
        }
// 検索機能を利用した場合
        if ($sql_kind === 'search_word') {
            if (isset($_POST['search_word']) === TRUE) {
                $search_word = htmlspecialchars($_POST['search_word'], ENT_QUOTES, 'UTF-8');
            }
// print_r($sql_kind);
// exit();
            try {
                $sql = 'SELECT img, name, price, stock, ec_item_master.item_id
                        FROM ec_item_master
                        INNER JOIN ec_item_stock
                        ON ec_item_master.item_id = ec_item_stock.item_id
                        WHERE status = 1
                        AND   name LIKE ?';
// bindvalueは''をつけてくれる
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, '%' . $search_word . '%',  PDO::PARAM_STR);
                $stmt->execute();
                $data_search = $stmt->fetchAll();
// print_r($data_search);
// exit;
                if (count($data_search) === 0) {
                    $err_msg[] = '<p>ご指定の条件に一致するアイテムが見つかりませんでした。</p>
                                  <p>別のキーワード条件でお探しください。</p>';
                }
            } catch (PDOException $e) {
               $err_msg[] = '処理失敗しました。理由:'.$e->getMessage();
            }
        }
    }

// ユーザーのデータを読み込む
    try{
        $sql = 'SELECT user_name
                FROM ec_user
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id,   PDO::PARAM_STR);
        $stmt->execute();
        $user_name_info = $stmt->fetchAll();
// 購入履歴のデータ
        $sql = 'SELECT name, price, img
                FROM ec_item_master
                INNER JOIN ec_result_history
                ON ec_item_master.item_id = ec_result_history.item_id
                WHERE user_id = ?
                ORDER BY ec_result_history.update_datetime DESC';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id,   PDO::PARAM_STR);
        $stmt->execute();
        $data_history = $stmt->fetchAll();
    // 先頭から3つの要素のみ取得する
        $data_history = array_slice($data_history, 0, 3);
// print_r($data_history);
// exit;
// カートに入れた数を調べる
        $sql = 'SELECT item_id
                FROM ec_cart
                WHERE user_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $cart_num = $stmt->fetchAll();

// print_r($cart_num);
// exit();
// 商品の一覧

        if (isset($_GET['item_kind']) === TRUE) {
            $item_kind = $_GET['item_kind'];
        }

        $sql = 'SELECT img, name, price, stock, ec_item_master.item_id, brand, beginner_check
                FROM ec_item_master
                INNER JOIN ec_item_stock
                ON ec_item_master.item_id = ec_item_stock.item_id
                INNER JOIN ec_item_detail
                ON ec_item_master.item_id = ec_item_detail.item_id
                WHERE status = 1 ';
// 商品一覧の順番を指定する  
// ''\' . '\''　
        if ($item_kind === 'new') {
            $sql .= 'ORDER BY ec_item_master.create_datetime DESC';
        }
        if ($item_kind === 'lower') {
            $sql .= 'ORDER BY price';
        }
        if ($item_kind === 'higher') {
            $sql .= 'ORDER BY price DESC';
        }
        if ($item_kind === 'yamaha') {
            $brand = 'ヤマハ';
            $sql   .= 'AND brand = \'' . $brand . '\'';
        }
        if ($item_kind === 'martin') {
            $brand = 'マーティン';
            $sql   .= 'AND brand = \'' . $brand . '\'';
        }
        if ($item_kind === 'morris') {
            $brand = 'モーリス';
            $sql   .= 'AND brand = \'' . $brand . '\'';
        }
        if ($item_kind === 'beginner') {
            $sql .= 'AND beginner_check = 1';
        }
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        if ($sql_kind === 'search_word') {
            $data = $data_search;
        }
        if ($sql_kind === 'price_search') {
            $data = $price_data;
        }
        
// print_r($data);
// exit();
    } catch (PDOException $e) {
        $err_msg[] = '処理失敗しました。理由:'.$e->getMessage();
    }

} catch (PDOException $e) {
    echo '接続できませんでした。理由:'.$e->getMessage();
}
// print_r($data);
// exit();
// ページング
    // データの要素の総数
    $data_sum = count($data); 
    // ページの最大数
    $max_page = ceil($data_sum / MAX);
    // ページ数の設定
    if (isset($_GET['page']) === TRUE) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }
    // そのページで表示する最初のデータ（最初のvalueに対応するkey)
    $start = MAX * ($page - 1);
    // そのページ内に必要なデータの取得
    $view_page = array_slice($data, $start, MAX, true);
    
// print_r($max_page);
// exit();
// print_r($page);
// exit;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品管理</title>
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
        }
        .inner input[type="text"] {
            height: 20px;
        }
        .inner button {
            height: 25px;
        }
        .num {
            background-color: black;
            color: white;
            padding: 5px 10px 5px 10px;
        }
        .icon-search {
            height: 12px;
        }
/*メイン*/
        main {
            width: 990px;
            margin: 20px auto 90px;
            display: flex;
        }
        nav {
            margin-top: 20px;
            width: 210px;
            margin-right: 80px;
        }
        nav .begginer {
            display: inline-block;
            font-weight: bold;
            padding: 8px;
            background-color: #ffcc00;
            box-shadow: 4px 4px #555;
        }
        nav .begginer:hover {
            background-color: #FFFF00;
        }
        nav .begginer:active {
            box-shadow: none;
        }
        nav dl {
            margin-top: 35px;
        }
        nav dl dt {
            font-size: 16px;
            font-weight: bold;
            padding: 10px 8px 10px 8px;
            background-color: #eeeeee;
            border-bottom: solid 1px silver;
        }
        nav dl dd {
            padding: 8px;
            margin-left: 0;
            border-bottom: solid 1px silver;
            text-indent: 10px;
        }
        nav dl dd:last-child {
            border-bottom: none;
        }
        nav dl dd:hover {
            background-color: #eeeeee;
        }
        nav p {
            font-weight: bold;
            padding: 10px 8px 10px 8px;
            background-color: #eeeeee;
        }
        .price-search {
            margin-bottom: 15px;
        }
        nav input[type="text"] {
            height: 25px;
            width: 120px;
        }
        nav input[type="submit"] {
            height:30px;
            width: 120px;
        }
        
        
        main article {
            width: 680px;
        }
        
        .main-navi {
            display: flex;
            font-size: 13px;
            margin-bottom: 40px;
            border-bottom: solid 1px silver;
        }
        .main-navi dl {
            display: flex;
            margin: 3px 20px 0px 40px;
            padding: 8px;
        }
        .main-navi dl dt {
            font-weight: bold;
            padding: 10px;
        }
        .main-navi dl dd {
            margin: 0;
            padding: 10px;
            text-align: center;
        }
        .main-navi dl dd:hover {
            background-color: #eeeeee;
        }
        .item-num {
            font-weight: bold;
            font-size: 20px;
        }
        .items {
            display: flex;
            flex-wrap: wrap;
        }
/*floatを使う場合、clearfixを使う必要がある。形が崩れているように見える場合がある*/
        h3 {
            background-color: #eeeeee;
            padding: 10px;
        }
        figure {
            margin: 0 60px 40px 0;
            width: 160px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: solid 1px silver;
        }
        figure input[type="submit"] {
            height: 30px;
        }
        .item-img {
            height: 160px;
            width: 120px;
        }
        .item-name {
            font-weight: bold;
            font-size: 13px;
            text-align: left;
        }
        .item-price {
            color: red;
            text-align: right;
            font-size: 13px;
            margin: 5px 0px 20px 0px;
        }
        .page {
            display: flex;
            margin-left: 225px;
        }
        .now-page {
            display: block;
            margin-left: 15px;
            padding: 4px 10px 4px 10px;
            background-color: black;
            color: white;
        }
        .page a {
            background-color: #eeeeee;
            display: block;
            margin-left: 15px;
            padding: 4px 10px 4px 10px;
        }
        .page a:hover { 
            background-color: black;
            color: white;
        }
        .history-content {
            margin-top: 70px;
        }
        .history-items {
            display: flex;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        .history-items figure {
            margin-bottom: 20px;
        }
        .history-content a {
            display: inline-block;
            margin-left: 550px;
            border-bottom: none;
        }
        .history-content a:hover {
            border-bottom: solid 1px black;
        }
        .err-msg {
            color: red;
            list-style-type: none;
            padding-left: 0;
        }
        .price-search-err-msg {
            color: red;
            list-style-type: none;
            padding-left: 0;
        }
        .msg {
            color: blue;
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
                <li>
                    <form method="post">
                        <input type="text" name="search_word" value="<?php print $search_word ?>" placeholder="キーワード検索">
                        <input type="hidden" name="sql_kind" value="search_word">
                        <button type="submit">
                            <img class="icon-search" src="img/iconmonstr-magnifier-6-240.png">
                        </button>
                    </form>
                </li>
                <li><a href="cart2.php">ショッピングカート <span class="num"><?php print count($cart_num); ?></span></a></li>
            </ul>
        </div>
    </header>
    <main>
        <nav>
            <a class="begginer" href="?item_kind=beginner">初心者おすすめ一覧</a>
            <dl>
                <dt>ブランドで探す</dt>
                <dd><a href="?item_kind=yamaha">ヤマハ</a></dd>
                <dd><a href="?item_kind=martin">マーティン</a></dd>
                <dd><a href="?item_kind=morris">モーリス</a></dd>
            </dl>
            <form method="post">
                <p>価格帯を指定する</p>
<?php if (count($search_err_msg) > 0) { ?>
                <ul class="price-search-err-msg">
    <?php foreach($search_err_msg as $value) { ?>
                    <li><?php print $value ?></li>
    <?php } ?>
                </ul>
<?php } ?>
                <div class="price-search">
                    <input type="text" name="mini_price" value="<?php print $mini_price ?>" placeholder="下限"> 円～
                </div>
                <div class="price-search">
                    <input type="text" name="max_price" value="<?php print $max_price ?>" placeholder="上限"> 円
                </div>
                <div>
                    <input type="hidden" name="sql_kind" value="price_search">
                    <input type="submit" value="価格帯で絞り込む">
                </div>
            </form>
        </nav>
        <article>
            <section class="item-content">
                <h3>アコースティックギター商品リスト</h3>
<?php if (count($err_msg) > 0) { ?>
                <ul class="err-msg">
    <?php foreach ($err_msg as $value) { ?>
                    <li><?php print $value; ?></li>
    <?php } ?>
                </ul>
<?php } ?>
<?php if ($msg !== '') { ?>
                <p class="msg"><?php print $msg; ?></p>
<?php } ?>
                <div class="main-navi">
                        <p><span class="item-num"><?php print count($data); ?></span> アイテム</p>
                    <dl>
                        <dt>並び替え:</dt>
                        <dd><a href="?item_kind=new">新着順</a></dd>
                        <dd><a href="?item_kind=lower">価格が安い順</a></dd>
                        <dd><a href="?item_kind=higher">価格が高い順</a></dd>
                    </dl>
                </div>
                <div class="items">
<?php foreach($view_page as $value) { ?>
                    <figure>
                        <img class="item-img" src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption class="item-name"><?php print $value['name']; ?></figcaption>
                        <figcaption class="item-price">&yen;<?php print $value['price']; ?> (税込)</figcaption>
    <?php if ($value['stock'] > 0) { ?>
                        <figcaption>
                            <form method="post">
                                <input type="hidden" name="sql_kind" value="cart">
                                <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>">
                                <input type="submit" value="カートに入れる">
                            </form>
                        </figcaption>
    <?php } else { ?>
                        <figcaption>売り切れ</figcaption>
    <?php } ?>
                    </figure>
<?php } ?>
                </div>
<!--ページ移動-->
                <div class="page">
<?php if ($page > 1) { ?>
                    <a href="top.php?page=<?php print ($page-1); ?>">前のページへ</a>
<?php } ?>
<!--$_GET[]の値は文字列で出てくる-->
<?php for ($i = 1; $i <= $max_page; $i++) { ?> 
    <?php if ($i === (int)$page) { ?>
                    <span class="now-page"><?php print $page; ?></span>
    <?php } else { ?>
                    <a href="top.php?page=<?php print $i; ?>"><?php print $i; ?></a>
    <?php } ?>
<?php } ?>

<?php if ($page < $max_page) { ?>
                    <a href="top.php?page=<?php print ($page+1); ?>">次のページへ</a>
<?php } ?>
                </div>
            </section>
            <section class="history-content">
                <h3>最近購入した商品</h3>
                <div class="history-items">
<?php if (count($data_history) > 0) { ?>
    <?php foreach($data_history as $value) { ?>
                    <figure>
                        <img class="item-img" src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption class="item-name"><?php print $value['name']; ?></figcaption>
                        <figcaption class="item-price">&yen;<?php print $value['price']; ?> (税込)</figcaption>
                    </figure>
    <?php } ?>
                </div>
                <a href="result_history.php">[購入履歴を見る]</a>
<?php } else { ?>
                <p>購入した商品がありません</p>
<?php } ?>
            </section>
        </article>
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
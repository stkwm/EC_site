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
$data_history   = array();
// ページングの変数　定数
define('MAX','14');
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
                            WHERE status = ?
                            AND   price BETWEEN ? AND ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, 1,           PDO::PARAM_STR);
                    $stmt->bindvalue(2, $mini_price, PDO::PARAM_STR);
                    $stmt->bindvalue(3, $max_price,  PDO::PARAM_STR);
                    $stmt->execute();
                    $data = $stmt->fetchAll();
                    
                    if (count($data) === 0) {
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
                        // AND   name LIKE \'%' . $search_word . '%\'';
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, '%' . $search_word . '%',  PDO::PARAM_STR);
                // $stmt->execute(array('%' . $search_word . '%'));
                $stmt->execute();
                $data_search = $stmt->fetchAll();
// print_r($search_word);
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
    // 先頭から４つの要素のみ取得する
        $data_history = array_slice($data_history, 0, 4);
        
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品管理</title>
    <style>
        header {
            border-bottom: solid 1px;
        }
        .inner {
            width: 990px;
            margin: 0 auto;
        }
        .inner p {
            text-align: right;
        }
        .inner h1 {
            position: absolute; top: 10px;
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
/*メイン*/
        main {
            width: 990px;
            margin: 20px auto 40px;
            display: flex;
        }
        nav {
            margin-right: 40px;
        }
        nav dl dt {
            font-size: 16px;
            font-weight: bold;
        }
        .main-navi {
            display: flex;
        }
        .main-navi dl {
            display: flex;
            margin-left: 50px;
        }
        .main-navi dl dd {
            margin-left: 15px;
            padding-right: 10px;
            border-right: solid 1px;
        }
        .main-navi dl dd:last-child {
            border-right: 0;
        }
        .items {
            display: flex;
            flex-wrap: wrap;
        }
        figure {
            margin: 0 45px 25px 0;
            width: 160px;
        }
        img {
            max-height: 120px;
        }
        
        
        
/*フッター*/
        footer {
            border-top: solid 1px;
        }
        .footer-content {
            width: 990px;
            margin: 0 auto;
        }
        .footer-content ul {
            list-style-type: none;
            display: flex;
        }
        .footer-content ul li {
            border-right: solid 1px;
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
        .footer-content p {
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="inner">
            <p><a href="logout.php">ログアウト</a></p>
            <h1><a href="top.php">アコースティック専門店</a></h1>
            <ul>
    <?php if ($user_id === 'admin') { ?>
                <li>ようこそadminさん</li>
    <?php } else { ?>
                <li><?php print 'ようこそ' . $user_name_info[0]['user_name'] . 'さん'; ?></li>
    <?php } ?>
                <li>
                    <form method="post">
                        <input type="text" name="search_word" value="<?php print $search_word ?>" placeholder="商品検索">
                        <input type="hidden" name="sql_kind" value="search_word">
                        <button type="submit">
                            <img src="">
                        </button>
                    </form>
                </li>
                <li><a href="cart2.php">ショッピングカート<span><?php print count($cart_num); ?></span></a></li>
            </ul>
        </div>
    </header>
    <main>
        <nav>
            <p><a href="?item_kind=beginner">初心者おすすめ</a></p>
            <dl>
                <dt>ブランドで探す</dt>
                <dd><a href="?item_kind=yamaha">ヤマハ</a></dd>
                <dd><a href="?item_kind=martin">マーティン</a></dd>
                <dd><a href="?item_kind=morris">モーリス</a></dd>
            </dl>
            <form method="post">
                <h4>価格帯を指定する</h4>
<?php if (count($search_err_msg) > 0) { ?>
                <ul>
    <?php foreach($search_err_msg as $value) { ?>
                    <li><?php print $value ?></li>
    <?php } ?>
                </ul>
<?php } ?>
                <div>
                    <input type="text" name="mini_price" value="<?php print $mini_price ?>" placeholder="下限">円～
                </div>
                <div>
                    <input type="text" name="max_price" value="<?php print $max_price ?>" placeholder="上限">円
                </div>
                <div>
                    <input type="hidden" name="sql_kind" value="price_search">
                    <input type="submit" value="価格帯で絞り込む">
                </div>
            </form>
        </nav>
        <article>
            <section class="item-content">
                <h2>アコースティックギター商品一覧</h2>
<?php if (count($err_msg) > 0) { ?>
                <ul>
    <?php foreach ($err_msg as $value) { ?>
                    <li><?php print $value; ?></li>
    <?php } ?>
                </ul>
<?php } ?>
<?php if ($msg !== '') { ?>
                <p><?php print $msg; ?></p>
<?php } ?>
                <div class="main-navi">
                        <p><span><?php print count($data); ?></span>アイテム</p>
                    <dl>
                        <dt>並び替え:</dt>
                        <dd><a href="?item_kind=new">新着順</a></dd>
                        <dd><a href="?item_kind=lower">価格が安い</a></dd>
                        <dd><a href="?item_kind=higher">価格が高い</a></dd>
                    </dl>
                </div>
                <div class="items">
<?php foreach($view_page as $value) { ?>
                    <figure>
                        <img src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption><?php print $value['name']; ?></figcaption>
                        <figcaption>&yen;<?php print $value['price']; ?></figcaption>
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
                <div>
<?php if ($page > 1) { ?>
                    <a href="top.php?page=<?php print ($page-1); ?>">前のページへ</a>
<?php } ?>

<?php for ($i = 1; $i <= $max_page; $i++) { ?>
    <?php if ($i === $page) { ?>
                    <span><?php print $page; ?></span>
    <?php } else { ?>
                    <a href="top.php?page=<?php print $i; ?>"><?php print $i; ?></a>
    <?php } ?>
<?php } ?>

<?php if ($page < $max_page) { ?>
                    <a href="top.php?page=<?php print ($page+1); ?>">次のページへ</a>
<?php } ?>
                </div>
            </section>
<?php if (count($data_history) > 0) { ?>
    <?php foreach($data_history as $value) { ?>        
            <section class="history-content">
                <h2>最後に購入した商品履歴</h2>
                <figure class="data_history">
                    <img src="<?php print $img_dir . $value['img']; ?>">
                    <figcaption><?php print $value['name']; ?></figcaption>
                    <figcaption>&yen;<?php print $value['price']; ?></figcaption>
                </figure>
                <p><a href="result_history.php">購入履歴一覧</a></p>
            </section>
        </article>
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
<?php
$sql_kind       = '';
$name           = '';
$price          = '';
$stock          = '';
$status         = '';
$brand          = '';
$beginner_check = '';
$update_stock   = '';
$item_id        = '';
$date           = date('Y-m-d H:i:s');
$err_msg        = array();
$msg            = '';
$data           = array();
// アップロードした画像の保存ディアレクトリ
$img_dir        = './img/';
// アップロードした新しい画像ファイル名
$new_img_filename = '';
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
// POSTが送られてきた場合
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['sql_kind']) === TRUE) {
            $sql_kind = $_POST['sql_kind'];
        }
// $sql_kindの値がinsertの場合
        if ($sql_kind === 'insert') {
            if (isset($_POST['name']) === TRUE) {
                $name = trim($_POST['name']);
            }
            if (isset($_POST['price']) === TRUE) {
                $price = trim($_POST['price']);
            }
            if (isset($_POST['stock']) === TRUE) {
                $stock = trim($_POST['stock']);
            }
            if (isset($_POST['status']) === TRUE) {
                $status = $_POST['status'];
            }
            if (isset($_POST['brand']) === TRUE) {
                $brand = $_POST['brand'];
            }
            if (isset($_POST['beginner_check']) === TRUE) {
                $beginner_check = $_POST['beginner_check'];
            }
// エラーメッセージ
            if ($name === '') {
                $err_msg[] = '名前を入力してください';
            } 
            if ($price === '') {
                $err_msg[] = '値段を入力してください';
            } else if (preg_match('/^([1-9][0-9]*|0)$/',$price) !== 1) {
                $err_msg[] = '値段は半角数字を入力してください';
            }
            if ($stock === '') {
                $err_msg[] = '個数を入力してください';
            } else if (preg_match('/^([1-9][0-9]*|0)$/',$stock) !== 1) {
                $err_msg[] = '個数は半角数字を入力してください';
            }
            
            if (is_uploaded_file($_FILES['new_img']['tmp_name']) === TRUE) {
                $extension = pathinfo($_FILES['new_img']['name'], PATHINFO_EXTENSION);
                if ($extension === 'png' || $extension === 'jpeg' || $extension ==='jpg') {
                    $new_img_filename = sha1(uniqid(mt_rand(), true)). '.' . $extension;
                    
                    if (is_file($img_dir . $new_img_filename) !== TRUE) {
                        if (move_uploaded_file($_FILES['new_img']['tmp_name'], $img_dir . $new_img_filename) !== TRUE) {
                        $err_msg[] = 'ファイルアップロードに失敗しました';
                        }
                    } else {
                        $err_msg[] = 'ファイルアップロードに失敗しました。再度お試しください。';
                    }
                } else {
                    $err_msg[] = 'ファイル形式が異なります。画像ファイルはJPEGとPNGのみ利用可能です。';
                }
            } else {
                $err_msg[] = 'ファイルを選択してください';
            }
// トランザクションの開始
            $dbh->beginTransaction();
            
            if (count($err_msg) === 0) {
                try {
                    $sql = 'INSERT INTO ec_item_master (name, price, img, status, create_datetime, update_datetime)
                            VALUES (?, ?, ?, ?, ?, ?)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $name,              PDO::PARAM_STR);
                    $stmt->bindvalue(2, $price,             PDO::PARAM_STR);
                    $stmt->bindvalue(3, $new_img_filename,  PDO::PARAM_STR);
                    $stmt->bindvalue(4, $status,            PDO::PARAM_STR);
                    $stmt->bindvalue(5, $date,              PDO::PARAM_STR);
                    $stmt->bindvalue(6, $date,              PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $item_id = $dbh->lastInsertId('item_id');
                    
                    $sql = 'INSERT INTO ec_item_stock (item_id, stock, create_datetime, update_datetime)
                            VALUES (?, ?, ?, ?)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue (1, $item_id,  PDO::PARAM_STR);
                    $stmt->bindvalue (2, $stock,    PDO::PARAM_STR);
                    $stmt->bindvalue (3, $date,     PDO::PARAM_STR);
                    $stmt->bindvalue (4, $date,     PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $sql = 'INSERT INTO ec_item_detail (item_id, beginner_check, brand, create_datetime, update_datetime)
                            VALUES (?, ?, ?, ?, ?)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue (1, $item_id,        PDO::PARAM_STR);
                    $stmt->bindvalue (2, $beginner_check, PDO::PARAM_STR);
                    $stmt->bindvalue (3, $brand,          PDO::PARAM_STR);
                    $stmt->bindvalue (4, $date,           PDO::PARAM_STR);
                    $stmt->bindvalue (5, $date,           PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $dbh->commit();
                    $msg = '商品を追加登録しました';
                    
                    
                } catch (PDOException $e) {
                    $dbh->rollback();
                    $err_msg[] = '商品を登録できませんでした。理由：'.$e->getMessage();
                }
                
            }
        }
// $sql_kindの値がupdateの場合
        if ($sql_kind === 'update') {
            if (isset($_POST['update_stock']) === TRUE) {
                $update_stock = trim($_POST['update_stock']);
            }
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
// エラーメッセージ
            if ($update_stock === '') {
                $err_msg[] = '個数が入力されていません';
            } else if (preg_match('/^([1-9][0-9]*|0)$/', $update_stock) !== 1) {
                $err_msg[] = '個数は半角数字を入力してください';
            }
            if ($item_id === '') {
                $err_msg[] = '商品が選択されていません';
            } else if (preg_match('/^[1-9][0-9]*$/',$item_id) !== 1) {
                $err_msg[] = '商品が正しくありません';
            }
            
            if (count($err_msg) === 0) {
                try {
                    $sql = 'UPDATE ec_item_stock
                            SET stock = ?,
                                update_datetime = ?
                            WHERE item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $update_stock, PDO::PARAM_STR);
                    $stmt->bindvalue(2, $date        , PDO::PARAM_STR);
                    $stmt->bindvalue(3, $item_id     , PDO::PARAM_STR);
                    $stmt->execute();
                    $msg = '在庫数を変更しました';
                } catch (PDOException $e) {
                    $err_msg[] = '在庫数を変更できませんでした。理由:'.$e->getMessage();
                }
            }
        }
// $sql_kindの値がstatus_setの場合
        if ($sql_kind === 'status_set') {
            if (isset($_POST['status']) === TRUE) {
                $status = $_POST['status'];
            }
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
// print_r($status);
// exit();
// エラーメッセージ
            if ($status === '') {
                $err_msg[] = 'ステータスが選択されていません';
            } else if (preg_match('/^[01]$/', $status) !== 1) {
                $err_msg[] = 'ステータスが正しくありません';
            }
            if ($item_id === '') {
                $err_msg[] = '商品が選択されていません';
            } else if (preg_match('/^[1-9][0-9]*$/', $item_id) !== 1) {
                $err_msg[] = '商品が正しくありません';
            }
            
            if (count($err_msg) === 0) {
                if ((int)$status === 1) {
                    $status = 0;
                } else {
                    $status = 1;
                }
// print_r($status);
// exit();
                try {
                    $sql = 'UPDATE ec_item_master
                            SET status = ?,
                                update_datetime = ?
                            WHERE item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $status,    PDO::PARAM_STR);
                    $stmt->bindvalue(2, $date,      PDO::PARAM_STR);
                    $stmt->bindvalue(3, $item_id,   PDO::PARAM_STR);
                    $stmt->execute();
                    $msg = 'ステータスを変更しました';
                } catch (PDOException $e) {
                    $err_msg[] = 'ステータスを変更できませんでした。理由:'.$e->getMessage();
                }
            }
        }
// $sql_kindの値がdeleteの場合
        if ($sql_kind === 'delete') {
            if (isset($_POST['item_id']) === TRUE) {
                $item_id = $_POST['item_id'];
            }
// エラーメッセージ
            if ($item_id === '') {
                $err_msg = '商品が選択されていません';
            } else if (preg_match('/^[1-9][0-9]*$/', $item_id) !== 1) {
                $err_msg = '商品が正しくありません';
            }
            
            $dbh->beginTransaction();
            
            if (count($err_msg) === 0) {
                try {
                    $sql = 'DELETE FROM ec_item_master
                            WHERE item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $item_id, PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $sql = 'DELETE FROM ec_item_stock
                            WHERE item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $item_id, PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $sql = 'DELETE FROM ec_item_detail
                            WHERE item_id = ?';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $item_id, PDO::PARAM_STR);
                    $stmt->execute();
                    
                    $dbh->commit();
                    $msg = '商品を削除しました';
                } catch (PDOException $e) {
                    $dbh->rollback();
                    $err_msg[] = '商品を削除できませんでした。理由：'.$e->getMessage();
                }
            }
        }
    }
// SQLを読み込む
    try {
        $sql = 'SELECT ec_item_master.item_id, name, price, img, status, stock, brand, beginner_check
                FROM ec_item_master
                INNER JOIN ec_item_stock
                ON ec_item_master.item_id = ec_item_stock.item_id
                INNER JOIN ec_item_detail
                ON ec_item_master.item_id = ec_item_detail.item_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $data = array_reverse($data);
    } catch (PDOException $e) {
        throw $e;
    }
} catch (PDOException $e) {
    echo '接続できませんでした。理由:'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品管理</title>
    <style>
        header, main, footer {
            width: 990px;
            margin: 0 auto;
        }
        a {
            text-decoration: none;
            color: black;
        }
        header {
            padding-bottom: 20px;
        }
        h1 {
            font-family: 'Bangers', cursive;
            line-height: 60px;
        }
        h1:hover {
            opacity: 0.7;
        }
        h1 span {
            color: #FF9966;
        }
        .header-content {
            display: flex;
        }
        .logout {
            margin-left: auto;
            margin-right: 40px;
            line-height: 85px;
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
        .user-data a {
            border-bottom: solid 1px;
            color: blue;
        }
        .user-data a:hover {
            border-bottom: none;
        }
        
        li {
            list-style-type: none;
            color: red;
        }
        .msg {
            color: blue;
        }
        main {
            border-top:     solid 1px silver;
            border-bottom:  solid 1px silver;
            padding-bottom: 20px;
        }
        header p {
            margin-bottom: 5px;
            margin-top: 0;
        }
        form p {
            margin: 1px 0;
        }
        table {
            border-collapse: collapse;
            width: 960px;
        }
        table img {
            max-height: 120px;
        }
        table, tr, th, td {
            border: solid 1px;
        }
        caption {
            text-align: left;
        }
        .private {
            background-color: silver;
        }
        .price {
            width: 90px;
            text-align: center;
        }
        .brand {
            width: 90px;
            text-align: center;
        }
        .beginner_check {
            text-align: center;
            width: 50px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="top.php">A<span>C</span>OUSTI<span>C</span> <span>C</span>AMP</a></h1>
            <p class="logout"><a href="logout.php">ログアウト</a></p>
        </div>
        <p class="user-data"><a href="admin_user.php" target="_blank">ユーザー管理ページ</a></p>
<?php if ($msg !== '') { ?>
        <p class="msg"><?php print $msg; ?></p>
<?php } ?>
<?php if (count($err_msg) > 0) { ?>
        <ul>
    <?php foreach ($err_msg as $value) { ?>
            <li><?php print $value; ?></li>
    <?php } ?>
        </ul>
<?php } ?>
    </header>
    <main>
        <h2>商品名の登録</h2>
        <form method="post" enctype="multipart/form-data">
            <label><p>商品名:<input type="text" name="name"></p></label>
            <label><p>値　段:<input type="text" name="price"></p></label>
            <label><p>個　数:<input type="text" name="stock"></p></label>
            <p>商品画像:<input type="file" name="new_img"></p>
            <p>ステータス:
                <select name="status">
                    <option value=1>公開</option>
                    <option value=0>非公開</option>
                </select>
            </p>
            <p>ブランド:
                <select name="brand">
                    <option value='ヤマハ'>ヤマハ</option>
                    <option value='マーティン'>マーティン</option>
                    <option value='モーリス'>モーリス</option>
                </select>
            </p>
            <p>初心者推奨:<input type="checkbox" name="beginner_check" value=1></p>
            <input type="hidden" name="sql_kind" value="insert">
            <p><input type="submit" value="商品を登録する"></p>
        </form>
    </main>
    <footer>
        <h2>商品情報の一覧・変更</h2>
        <table>
            <caption>商品一覧</caption>
            <tr>
                <th>商品画像</th>
                <th>商品名</th>
                <th>価格</th>
                <th>在庫数</th>
                <th>ステータス</th>
                <th>ブランド</th>
                <th>初心者推奨</th>
                <th>操作</th>
            </tr>
<?php foreach ($data as $value) { ?>
    <?php if ($value['status'] === 1) { ?>
            <tr>
    <?php } else { ?>
            <tr class ="private">
    <?php } ?>
                <td><img src="<?php print $img_dir . $value['img']; ?>"></td>
                <td><?php print htmlspecialchars($value['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="price"><?php print $value['price']; ?>円</td>
                <td>
                    <form method="post">
                        <input type="text" name="update_stock" value="<?php print $value['stock']; ?>">個
                        <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>">
                        <input type="hidden" name="sql_kind" value="update">
                        <input type="submit" value="変更する">
                    </form>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="sql_kind" value="status_set">
                        <input type="hidden" name="item_id" value="<?php print $value['item_id']; ?>">
                        <input type="hidden" name="status" value='<?php print $value['status']; ?>'>
    <?php if ($value['status'] === 1) { ?>
                        <input type="submit" value="公開&rarr;非公開">
    <?php } else { ?>
                        <input type="submit" value="非公開&rarr;公開">
    <?php } ?>
                    </form>
                </td>
                <td class="brand"><?php print $value['brand']; ?></td>
    <?php if ($value['beginner_check'] === 1) { ?>
                <td class="beginner_check">◯</td>
    <?php } else { ?>
                <td></td>
    <?php } ?>
                <td>
                    <form method="post">
                        <input type="hidden" name="item_id" value="<?php print $value['item_id']?>">
                        <input type="hidden" name="sql_kind" value="delete">
                        <input type="submit" value="削除する">
                    </form>
                </td>
            </tr>
<?php } ?>
        </table>
    </footer>
</body>
</html>
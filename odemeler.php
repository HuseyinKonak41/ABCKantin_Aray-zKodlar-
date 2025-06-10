<?php
include 'config.php';


if (isset($_POST['ekle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_OdemeEkle(?, ?, ?, ?, ?)");
        $odeme_id = $_POST['odeme_id'];
        $musteri_id = $_POST['musteri_id'];
        $odeme_miktar = $_POST['odeme_miktar'];
        $odeme_tarih = date('Y-m-d H:i:s');
        $odeme_tip = $_POST['odeme_tip'];
        
        $stmt->execute([$odeme_id, $musteri_id, $odeme_miktar, $odeme_tarih, $odeme_tip]);
        echo "<div class='alert alert-success'>Ödeme başarıyla eklendi.</div>";
        
        
        header("Location: odemeler.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_POST['guncelle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_OdemeGuncelle(?, ?, ?, ?)");
        $stmt->execute([
            $_POST['odeme_id'],
            $_POST['musteri_id'],
            $_POST['odeme_miktar'],
            $_POST['odeme_tip']
        ]);
        echo "<div class='alert alert-success'>Ödeme başarıyla güncellendi.</div>";
        
       
        header("Location: odemeler.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_GET['sil'])) {
    try {
        $stmt = $conn->prepare("CALL sp_OdemeSil(?)");
        $stmt->execute([$_GET['sil']]);
        echo "<div class='alert alert-success'>Ödeme başarıyla silindi.</div>";
        
       
        header("Location: odemeler.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


$duzenlenecek_odeme = null;
if (isset($_GET['duzenle'])) {
    $stmt = $conn->prepare("
        SELECT o.*, 
               CONCAT(m.musteri_ad, ' ', m.musteri_soyad) as musteri_adsoyad,
               m.musteri_bakiye
        FROM abc_odemeler o
        JOIN abc_musteriler m ON o.musteri_id = m.musteri_id
        WHERE o.odeme_id = ?
    ");
    $stmt->execute([$_GET['duzenle']]);
    $duzenlenecek_odeme = $stmt->fetch();
}


if (isset($_GET['get_musteri_bakiye'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT musteri_bakiye FROM abc_musteriler WHERE musteri_id = ?");
    $stmt->execute([$_GET['get_musteri_bakiye']]);
    $bakiye = $stmt->fetchColumn();
    echo json_encode(['bakiye' => $bakiye]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Yönetimi - ABC Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ABC Kantin</a>
            <div class="navbar-nav">
                <a class="nav-link" href="musteriler.php">Müşteriler</a>
                <a class="nav-link" href="urunler.php">Ürünler</a>
                <a class="nav-link" href="satislar.php">Satışlar</a>
                <a class="nav-link active" href="odemeler.php">Ödemeler</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><?= isset($_GET['duzenle']) ? 'Ödeme Düzenle' : 'Ödeme Yönetimi' ?></h2>
        
        
        <div class="card mb-4">
            <div class="card-header">
                <h5><?= isset($_GET['duzenle']) ? 'Ödeme Düzenle' : 'Yeni Ödeme Ekle' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label>Ödeme ID</label>
                            <input type="text" name="odeme_id" class="form-control" value="<?= $duzenlenecek_odeme ? $duzenlenecek_odeme['odeme_id'] : '' ?>" <?= $duzenlenecek_odeme ? 'readonly' : '' ?> required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Müşteri</label>
                            <select name="musteri_id" class="form-control" id="musteri_select" required>
                                <?php
                                $stmt = $conn->query("
                                    SELECT m.musteri_id, 
                                           CONCAT(m.musteri_ad, ' ', m.musteri_soyad, ' (', m.musteri_bakiye, ' ₺)') as musteri_bilgi 
                                    FROM abc_musteriler m 
                                    WHERE m.musteri_bakiye > 0 OR m.musteri_id = '".$duzenlenecek_odeme['musteri_id']."'
                                    ORDER BY m.musteri_bakiye DESC
                                ");
                                while ($row = $stmt->fetch()) {
                                    $selected = ($duzenlenecek_odeme && $duzenlenecek_odeme['musteri_id'] == $row['musteri_id']) ? 'selected' : '';
                                    echo "<option value='".$row['musteri_id']."' ".$selected.">".$row['musteri_bilgi']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Miktar</label>
                            <input type="number" step="0.01" name="odeme_miktar" id="odeme_miktar" class="form-control" value="<?= $duzenlenecek_odeme ? $duzenlenecek_odeme['odeme_miktar'] : '' ?>" required>
                            <small class="text-muted">Maksimum: <span id="max_odeme">0</span> ₺</small>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Ödeme Tipi</label>
                            <select name="odeme_tip" class="form-control" required>
                                <?php
                                $odeme_tipleri = ['Nakit', 'Kart', 'Havale'];
                                foreach ($odeme_tipleri as $tip) {
                                    $selected = ($duzenlenecek_odeme && $duzenlenecek_odeme['odeme_tip'] == $tip) ? 'selected' : '';
                                    echo "<option value='".$tip."' ".$selected.">".$tip."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($duzenlenecek_odeme): ?>
                        <button type="submit" name="guncelle" class="btn btn-warning">Ödemeyi Güncelle</button>
                        <a href="odemeler.php" class="btn btn-secondary">İptal</a>
                    <?php else: ?>
                        <button type="submit" name="ekle" class="btn btn-primary">Ödeme Ekle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

       
        <div class="card">
            <div class="card-header">
                <h5>Ödeme Listesi</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ödeme ID</th>
                            <th>Müşteri</th>
                            <th>Miktar</th>
                            <th>Tip</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT o.*, CONCAT(m.musteri_ad, ' ', m.musteri_soyad) as musteri_adsoyad
                            FROM abc_odemeler o
                            JOIN abc_musteriler m ON o.musteri_id = m.musteri_id
                            ORDER BY o.odeme_tarih DESC
                        ");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>".$row['odeme_id']."</td>";
                            echo "<td>".$row['musteri_adsoyad']."</td>";
                            echo "<td>".$row['odeme_miktar']." ₺</td>";
                            echo "<td>".$row['odeme_tip']."</td>";
                            echo "<td>".$row['odeme_tarih']."</td>";
                            echo "<td>
                                    <a href='?duzenle=".$row['odeme_id']."' class='btn btn-warning btn-sm'>Düzenle</a>
                                    <a href='?sil=".$row['odeme_id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Silmek istediğinize emin misiniz?\")'>Sil</a>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        
        function updateMaxOdeme() {
            var musteri_id = $('#musteri_select').val();
            $.get('odemeler.php?get_musteri_bakiye=' + musteri_id, function(response) {
                $('#max_odeme').text(parseFloat(response.bakiye).toFixed(2));
                $('#odeme_miktar').attr('max', response.bakiye);
            }, 'json');
        }
        
        $('#musteri_select').change(updateMaxOdeme);
        
        
        if ($('#musteri_select').val()) {
            updateMaxOdeme();
        }
        
        
        $('form').submit(function(e) {
            var odeme_miktar = parseFloat($('#odeme_miktar').val());
            var max_odeme = parseFloat($('#max_odeme').text());
            
            if (odeme_miktar > max_odeme) {
                e.preventDefault();
                alert('Ödeme miktarı müşteri bakiyesinden büyük olamaz!');
            }
        });
    });
    </script>
</body>
</html> 
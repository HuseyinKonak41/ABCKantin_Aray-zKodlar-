<?php
include 'config.php';


if (isset($_POST['ekle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_SatisEkle(?, ?, ?, ?, ?, ?)");
        $satis_id = $_POST['satis_id'];
        $musteri_id = $_POST['musteri_id'];
        $urun_id = $_POST['urun_id'];
        $satis_miktar = $_POST['satis_miktar'];
        $satis_fiyat = $_POST['satis_fiyat'];
        $satis_tarih = date('Y-m-d H:i:s');
        
        $stmt->execute([$satis_id, $musteri_id, $urun_id, $satis_miktar, $satis_fiyat, $satis_tarih]);
        echo "<div class='alert alert-success'>Satış başarıyla eklendi.</div>";
        
        // Sayfayı yenile
        header("Location: satislar.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_POST['guncelle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_SatisGuncelle(?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['satis_id'],
            $_POST['musteri_id'],
            $_POST['urun_id'],
            $_POST['satis_miktar'],
            $_POST['satis_fiyat']
        ]);
        echo "<div class='alert alert-success'>Satış başarıyla güncellendi.</div>";
        
        // Sayfayı yenile
        header("Location: satislar.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_GET['sil'])) {
    try {
        $stmt = $conn->prepare("CALL sp_SatisSil(?)");
        $stmt->execute([$_GET['sil']]);
        echo "<div class='alert alert-success'>Satış başarıyla silindi.</div>";
        
        // Sayfayı yenile
        header("Location: satislar.php");
        exit;
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


$duzenlenecek_satis = null;
if (isset($_GET['duzenle'])) {
    $stmt = $conn->prepare("
        SELECT s.*, 
               CONCAT(m.musteri_ad, ' ', m.musteri_soyad) as musteri_adsoyad,
               u.urun_ad, u.urun_fiyat, u.urun_stok
        FROM abc_satislar s
        JOIN abc_musteriler m ON s.musteri_id = m.musteri_id
        JOIN abc_urunler u ON s.urun_id = u.urun_id
        WHERE s.satis_id = ?
    ");
    $stmt->execute([$_GET['duzenle']]);
    $duzenlenecek_satis = $stmt->fetch();
}


if (isset($_GET['get_urun_bilgi'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT urun_fiyat, urun_stok FROM abc_urunler WHERE urun_id = ?");
    $stmt->execute([$_GET['get_urun_bilgi']]);
    $urun = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($urun);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Satış Yönetimi - ABC Kantin</title>
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
                <a class="nav-link active" href="satislar.php">Satışlar</a>
                <a class="nav-link" href="odemeler.php">Ödemeler</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><?= isset($_GET['duzenle']) ? 'Satış Düzenle' : 'Satış Yönetimi' ?></h2>
        
        <!-- Satış Formu -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?= isset($_GET['duzenle']) ? 'Satış Düzenle' : 'Yeni Satış Ekle' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label>Satış ID</label>
                            <input type="text" name="satis_id" class="form-control" value="<?= $duzenlenecek_satis ? $duzenlenecek_satis['satis_id'] : '' ?>" <?= $duzenlenecek_satis ? 'readonly' : '' ?> required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Müşteri</label>
                            <select name="musteri_id" class="form-control" required>
                                <?php
                                $stmt = $conn->query("SELECT musteri_id, CONCAT(musteri_ad, ' ', musteri_soyad) as musteri_adsoyad FROM abc_musteriler ORDER BY musteri_ad");
                                while ($row = $stmt->fetch()) {
                                    $selected = ($duzenlenecek_satis && $duzenlenecek_satis['musteri_id'] == $row['musteri_id']) ? 'selected' : '';
                                    echo "<option value='".$row['musteri_id']."' ".$selected.">".$row['musteri_adsoyad']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Ürün</label>
                            <select name="urun_id" class="form-control" id="urun_select" required>
                                <?php
                                $stmt = $conn->query("SELECT urun_id, CONCAT(urun_ad, ' (', urun_stok, ' adet) - ', urun_fiyat, ' ₺') as urun_bilgi FROM abc_urunler WHERE urun_stok > 0 OR urun_id = '".$duzenlenecek_satis['urun_id']."' ORDER BY urun_ad");
                                while ($row = $stmt->fetch()) {
                                    $selected = ($duzenlenecek_satis && $duzenlenecek_satis['urun_id'] == $row['urun_id']) ? 'selected' : '';
                                    echo "<option value='".$row['urun_id']."' ".$selected.">".$row['urun_bilgi']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Miktar</label>
                            <input type="number" name="satis_miktar" id="satis_miktar" class="form-control" min="1" value="<?= $duzenlenecek_satis ? $duzenlenecek_satis['satis_miktar'] : '1' ?>" required>
                            <small class="text-muted">Maksimum: <span id="max_miktar">0</span> adet</small>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Fiyat</label>
                            <input type="number" step="0.01" name="satis_fiyat" id="satis_fiyat" class="form-control" value="<?= $duzenlenecek_satis ? $duzenlenecek_satis['satis_fiyat'] : '' ?>" required>
                        </div>
                    </div>
                    <?php if ($duzenlenecek_satis): ?>
                        <button type="submit" name="guncelle" class="btn btn-warning">Satışı Güncelle</button>
                        <a href="satislar.php" class="btn btn-secondary">İptal</a>
                    <?php else: ?>
                        <button type="submit" name="ekle" class="btn btn-primary">Satış Ekle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Satış Listesi -->
        <div class="card">
            <div class="card-header">
                <h5>Satış Listesi</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Satış ID</th>
                            <th>Müşteri</th>
                            <th>Ürün</th>
                            <th>Miktar</th>
                            <th>Fiyat</th>
                            <th>Toplam</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT s.*, 
                                   CONCAT(m.musteri_ad, ' ', m.musteri_soyad) as musteri_adsoyad,
                                   u.urun_ad
                            FROM abc_satislar s
                            JOIN abc_musteriler m ON s.musteri_id = m.musteri_id
                            JOIN abc_urunler u ON s.urun_id = u.urun_id
                            ORDER BY s.satis_tarih DESC
                        ");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>".$row['satis_id']."</td>";
                            echo "<td>".$row['musteri_adsoyad']."</td>";
                            echo "<td>".$row['urun_ad']."</td>";
                            echo "<td>".$row['satis_miktar']." adet</td>";
                            echo "<td>".$row['satis_fiyat']." ₺</td>";
                            echo "<td>".($row['satis_miktar'] * $row['satis_fiyat'])." ₺</td>";
                            echo "<td>".$row['satis_tarih']."</td>";
                            echo "<td>
                                    <a href='?duzenle=".$row['satis_id']."' class='btn btn-warning btn-sm'>Düzenle</a>
                                    <a href='?sil=".$row['satis_id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Silmek istediğinize emin misiniz?\")'>Sil</a>
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
        
        function updateUrunBilgi() {
            var urun_id = $('#urun_select').val();
            $.get('satislar.php?get_urun_bilgi=' + urun_id, function(response) {
                $('#satis_fiyat').val(response.urun_fiyat);
                $('#max_miktar').text(response.urun_stok);
                $('#satis_miktar').attr('max', response.urun_stok);
            }, 'json');
        }
        
        $('#urun_select').change(updateUrunBilgi);
        
        
        if ($('#urun_select').val()) {
            updateUrunBilgi();
        }
        
        
        $('form').submit(function(e) {
            var satis_miktar = parseInt($('#satis_miktar').val());
            var max_miktar = parseInt($('#max_miktar').text());
            
            if (satis_miktar > max_miktar) {
                e.preventDefault();
                alert('Satış miktarı stok miktarından büyük olamaz!');
            }
        });
    });
    </script>
</body>
</html> 
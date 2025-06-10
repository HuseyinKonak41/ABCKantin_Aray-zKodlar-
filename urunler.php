<?php
include 'config.php';


if (isset($_POST['ekle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_UrunEkle(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['urun_id'],
            $_POST['urun_ad'],
            $_POST['urun_kategori'],
            $_POST['urun_fiyat'],
            $_POST['urun_aciklama'],
            $_POST['urun_stok'],
            $_POST['urun_birim']
        ]);
        echo "<div class='alert alert-success'>Ürün başarıyla eklendi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_POST['guncelle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_UrunGuncelle(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['urun_id'],
            $_POST['urun_ad'],
            $_POST['urun_kategori'],
            $_POST['urun_fiyat'],
            $_POST['urun_aciklama'],
            $_POST['urun_stok'],
            $_POST['urun_birim']
        ]);
        echo "<div class='alert alert-success'>Ürün başarıyla güncellendi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_GET['sil'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM abc_urunler WHERE urun_id = ?");
        $stmt->execute([$_GET['sil']]);
        echo "<div class='alert alert-success'>Ürün başarıyla silindi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


$duzenlenecek_urun = null;
if (isset($_GET['duzenle'])) {
    $stmt = $conn->prepare("SELECT * FROM abc_urunler WHERE urun_id = ?");
    $stmt->execute([$_GET['duzenle']]);
    $duzenlenecek_urun = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürün Yönetimi - ABC Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ABC Kantin</a>
            <div class="navbar-nav">
                <a class="nav-link" href="musteriler.php">Müşteriler</a>
                <a class="nav-link active" href="urunler.php">Ürünler</a>
                <a class="nav-link" href="satislar.php">Satışlar</a>
                <a class="nav-link" href="odemeler.php">Ödemeler</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><?php echo $duzenlenecek_urun ? 'Ürün Düzenle' : 'Ürün Yönetimi'; ?></h2>
        
        <!-- Ürün Ekleme/Düzenleme Formu -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo $duzenlenecek_urun ? 'Ürün Düzenle' : 'Yeni Ürün Ekle'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Ürün ID</label>
                            <input type="text" name="urun_id" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_id'] : ''; ?>" <?php echo $duzenlenecek_urun ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Ürün Adı</label>
                            <input type="text" name="urun_ad" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_ad'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Kategori</label>
                            <input type="text" name="urun_kategori" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_kategori'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Fiyat</label>
                            <input type="number" step="0.01" name="urun_fiyat" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_fiyat'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Açıklama</label>
                            <input type="text" name="urun_aciklama" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_aciklama'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Stok Miktarı</label>
                            <input type="number" name="urun_stok" class="form-control" value="<?php echo $duzenlenecek_urun ? $duzenlenecek_urun['urun_stok'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Birim</label>
                            <select name="urun_birim" class="form-control" required>
                                <option value="Adet" <?php echo ($duzenlenecek_urun && $duzenlenecek_urun['urun_birim'] == 'Adet') ? 'selected' : ''; ?>>Adet</option>
                                <option value="Gram" <?php echo ($duzenlenecek_urun && $duzenlenecek_urun['urun_birim'] == 'Gram') ? 'selected' : ''; ?>>Gram</option>
                                <option value="Mililitre" <?php echo ($duzenlenecek_urun && $duzenlenecek_urun['urun_birim'] == 'Mililitre') ? 'selected' : ''; ?>>Mililitre</option>
                            </select>
                        </div>
                    </div>
                    <?php if ($duzenlenecek_urun): ?>
                        <button type="submit" name="guncelle" class="btn btn-warning">Ürün Güncelle</button>
                        <a href="urunler.php" class="btn btn-secondary">İptal</a>
                    <?php else: ?>
                        <button type="submit" name="ekle" class="btn btn-primary">Ürün Ekle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Ürün Listesi -->
        <div class="card">
            <div class="card-header">
                <h5>Ürün Listesi</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ürün Adı</th>
                            <th>Kategori</th>
                            <th>Fiyat</th>
                            <th>Açıklama</th>
                            <th>Stok</th>
                            <th>Birim</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT * FROM abc_urunler");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>".$row['urun_id']."</td>";
                            echo "<td>".$row['urun_ad']."</td>";
                            echo "<td>".$row['urun_kategori']."</td>";
                            echo "<td>".$row['urun_fiyat']." ₺</td>";
                            echo "<td>".$row['urun_aciklama']."</td>";
                            echo "<td>".$row['urun_stok']."</td>";
                            echo "<td>".$row['urun_birim']."</td>";
                            echo "<td>
                                    <a href='?duzenle=".$row['urun_id']."' class='btn btn-warning btn-sm'>Düzenle</a>
                                    <a href='?sil=".$row['urun_id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Silmek istediğinize emin misiniz?\")'>Sil</a>
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
</body>
</html> 
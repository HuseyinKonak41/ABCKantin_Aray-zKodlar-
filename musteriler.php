<?php
include 'config.php';


if (isset($_POST['ekle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_MusteriEkle(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['musteri_id'],
            $_POST['musteri_ad'],
            $_POST['musteri_soyad'],
            $_POST['musteri_tip'],
            $_POST['musteri_sinif'],
            $_POST['musteri_tel'],
            $_POST['musteri_mail'],
            $_POST['musteri_bakiye']
        ]);
        echo "<div class='alert alert-success'>Müşteri başarıyla eklendi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_POST['guncelle'])) {
    try {
        $stmt = $conn->prepare("CALL sp_MusteriGuncelle(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['musteri_id'],
            $_POST['musteri_ad'],
            $_POST['musteri_soyad'],
            $_POST['musteri_tip'],
            $_POST['musteri_sinif'],
            $_POST['musteri_tel'],
            $_POST['musteri_mail'],
            $_POST['musteri_bakiye']
        ]);
        echo "<div class='alert alert-success'>Müşteri başarıyla güncellendi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


if (isset($_GET['sil'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM abc_musteriler WHERE musteri_id = ?");
        $stmt->execute([$_GET['sil']]);
        echo "<div class='alert alert-success'>Müşteri başarıyla silindi.</div>";
    } catch(PDOException $e) {
        echo "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}


$duzenlenecek_musteri = null;
if (isset($_GET['duzenle'])) {
    $stmt = $conn->prepare("SELECT * FROM abc_musteriler WHERE musteri_id = ?");
    $stmt->execute([$_GET['duzenle']]);
    $duzenlenecek_musteri = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Yönetimi - ABC Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ABC Kantin</a>
            <div class="navbar-nav">
                <a class="nav-link active" href="musteriler.php">Müşteriler</a>
                <a class="nav-link" href="urunler.php">Ürünler</a>
                <a class="nav-link" href="satislar.php">Satışlar</a>
                <a class="nav-link" href="odemeler.php">Ödemeler</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><?php echo $duzenlenecek_musteri ? 'Müşteri Düzenle' : 'Müşteri Yönetimi'; ?></h2>
        
       
        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo $duzenlenecek_musteri ? 'Müşteri Düzenle' : 'Yeni Müşteri Ekle'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Müşteri ID</label>
                            <input type="text" name="musteri_id" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_id'] : ''; ?>" <?php echo $duzenlenecek_musteri ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Ad</label>
                            <input type="text" name="musteri_ad" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_ad'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Soyad</label>
                            <input type="text" name="musteri_soyad" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_soyad'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Tip</label>
                            <select name="musteri_tip" class="form-control" required>
                                <option value="Öğrenci" <?php echo ($duzenlenecek_musteri && $duzenlenecek_musteri['musteri_tip'] == 'Öğrenci') ? 'selected' : ''; ?>>Öğrenci</option>
                                <option value="Personel" <?php echo ($duzenlenecek_musteri && $duzenlenecek_musteri['musteri_tip'] == 'Personel') ? 'selected' : ''; ?>>Personel</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Sınıf</label>
                            <input type="text" name="musteri_sinif" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_sinif'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Telefon</label>
                            <input type="text" name="musteri_tel" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_tel'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>E-posta</label>
                            <input type="email" name="musteri_mail" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_mail'] : ''; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Bakiye</label>
                            <input type="number" step="0.01" name="musteri_bakiye" class="form-control" value="<?php echo $duzenlenecek_musteri ? $duzenlenecek_musteri['musteri_bakiye'] : '0'; ?>" required>
                        </div>
                    </div>
                    <?php if ($duzenlenecek_musteri): ?>
                        <button type="submit" name="guncelle" class="btn btn-warning">Müşteri Güncelle</button>
                        <a href="musteriler.php" class="btn btn-secondary">İptal</a>
                    <?php else: ?>
                        <button type="submit" name="ekle" class="btn btn-primary">Müşteri Ekle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

      
        <div class="card">
            <div class="card-header">
                <h5>Müşteri Listesi</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad</th>
                            <th>Soyad</th>
                            <th>Tip</th>
                            <th>Sınıf</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Bakiye</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT * FROM abc_musteriler");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>".$row['musteri_id']."</td>";
                            echo "<td>".$row['musteri_ad']."</td>";
                            echo "<td>".$row['musteri_soyad']."</td>";
                            echo "<td>".$row['musteri_tip']."</td>";
                            echo "<td>".$row['musteri_sinif']."</td>";
                            echo "<td>".$row['musteri_tel']."</td>";
                            echo "<td>".$row['musteri_mail']."</td>";
                            echo "<td>".$row['musteri_bakiye']." ₺</td>";
                            echo "<td>
                                    <a href='?duzenle=".$row['musteri_id']."' class='btn btn-warning btn-sm'>Düzenle</a>
                                    <a href='?sil=".$row['musteri_id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Silmek istediğinize emin misiniz?\")'>Sil</a>
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
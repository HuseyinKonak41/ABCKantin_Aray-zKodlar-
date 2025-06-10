<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ABC Kantin Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ABC Kantin</a>
            <div class="navbar-nav">
                <a class="nav-link" href="musteriler.php">Müşteriler</a>
                <a class="nav-link" href="urunler.php">Ürünler</a>
                <a class="nav-link" href="satislar.php">Satışlar</a>
                <a class="nav-link" href="odemeler.php">Ödemeler</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>ABC Kantin Yönetim Sistemi</h1>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Müşteriler</h5>
                        <p class="card-text">Müşteri işlemlerini yönetin</p>
                        <a href="musteriler.php" class="btn btn-primary">Müşteriler</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ürünler</h5>
                        <p class="card-text">Ürün işlemlerini yönetin</p>
                        <a href="urunler.php" class="btn btn-primary">Ürünler</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Satışlar</h5>
                        <p class="card-text">Satış işlemlerini yönetin</p>
                        <a href="satislar.php" class="btn btn-primary">Satışlar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ödemeler</h5>
                        <p class="card-text">Ödeme işlemlerini yönetin</p>
                        <a href="odemeler.php" class="btn btn-primary">Ödemeler</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
DROP DATABASE IF EXISTS abc_kantin;
CREATE DATABASE abc_kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE abc_kantin;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS abc_satislar;
DROP TABLE IF EXISTS abc_odemeler;
DROP TABLE IF EXISTS abc_urunler;
DROP TABLE IF EXISTS abc_musteriler;

DROP PROCEDURE IF EXISTS sp_SatisEkle;
DROP PROCEDURE IF EXISTS sp_OdemeEkle;
DROP PROCEDURE IF EXISTS sp_MusteriEkle;
DROP PROCEDURE IF EXISTS sp_UrunEkle;
DROP PROCEDURE IF EXISTS sp_SatisSil;
DROP PROCEDURE IF EXISTS sp_OdemeSil;
DROP PROCEDURE IF EXISTS sp_MusteriGuncelle;
DROP PROCEDURE IF EXISTS sp_UrunGuncelle;
DROP PROCEDURE IF EXISTS sp_SatisGuncelle;
DROP FUNCTION IF EXISTS fn_MusteriTamAd;
DROP TRIGGER IF EXISTS tg_SatisOncesiStokKontrol;
DROP TRIGGER IF EXISTS tg_SatisSonrasiStokDusur;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE abc_musteriler (
    musteri_id VARCHAR(20) PRIMARY KEY,
    musteri_ad VARCHAR(50),
    musteri_soyad VARCHAR(50),
    musteri_tip VARCHAR(20),
    musteri_sinif VARCHAR(20),
    musteri_tel VARCHAR(15),
    musteri_mail VARCHAR(50),
    musteri_bakiye DECIMAL(10,2) DEFAULT 0
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE abc_urunler (
    urun_id VARCHAR(20) PRIMARY KEY,
    urun_ad VARCHAR(100),
    urun_kategori VARCHAR(50),
    urun_fiyat DECIMAL(10,2),
    urun_aciklama TEXT,
    urun_stok INT DEFAULT 0,
    urun_birim VARCHAR(20)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE abc_satislar (
    satis_id VARCHAR(20) PRIMARY KEY,
    musteri_id VARCHAR(20),
    urun_id VARCHAR(20),
    satis_miktar INT,
    satis_fiyat DECIMAL(10,2),
    satis_tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES abc_musteriler(musteri_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (urun_id) REFERENCES abc_urunler(urun_id) ON DELETE CASCADE ON UPDATE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE abc_odemeler (
    odeme_id VARCHAR(20) PRIMARY KEY,
    musteri_id VARCHAR(20),
    odeme_miktar DECIMAL(10,2),
    odeme_tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    odeme_tip VARCHAR(50),
    FOREIGN KEY (musteri_id) REFERENCES abc_musteriler(musteri_id) ON DELETE CASCADE ON UPDATE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DELIMITER $$

CREATE PROCEDURE sp_SatisEkle(
    IN p_satis_id VARCHAR(20),
    IN p_musteri_id VARCHAR(20),
    IN p_urun_id VARCHAR(20),
    IN p_satis_miktar INT,
    IN p_satis_fiyat DECIMAL(10,2),
    IN p_satis_tarih DATETIME
)
BEGIN
    DECLARE v_stok INT;
    DECLARE v_urun_fiyat DECIMAL(10,2);
    
    
    SELECT urun_stok, urun_fiyat 
    INTO v_stok, v_urun_fiyat
    FROM abc_urunler 
    WHERE urun_id = p_urun_id;
    
   
    IF NOT EXISTS (SELECT 1 FROM abc_urunler WHERE urun_id = p_urun_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ürün bulunamadı!';
    ELSEIF NOT EXISTS (SELECT 1 FROM abc_musteriler WHERE musteri_id = p_musteri_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Müşteri bulunamadı!';
    ELSEIF p_satis_miktar <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Satış miktarı 0 veya negatif olamaz!';
    ELSEIF v_stok < p_satis_miktar THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Yetersiz stok!';
    ELSE
        START TRANSACTION;
        
       
        UPDATE abc_urunler 
        SET urun_stok = urun_stok - p_satis_miktar
        WHERE urun_id = p_urun_id;
        
        
        INSERT INTO abc_satislar (satis_id, musteri_id, urun_id, satis_miktar, satis_fiyat, satis_tarih)
        VALUES (p_satis_id, p_musteri_id, p_urun_id, p_satis_miktar, 
                IFNULL(p_satis_fiyat, v_urun_fiyat), 
                IFNULL(p_satis_tarih, NOW()));
        
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_OdemeEkle(
    IN p_odeme_id VARCHAR(20),
    IN p_musteri_id VARCHAR(20),
    IN p_odeme_miktar DECIMAL(10,2),
    IN p_odeme_tarih DATETIME,
    IN p_odeme_tip VARCHAR(50)
)
BEGIN
    DECLARE v_bakiye DECIMAL(10,2);
    
    
    SELECT musteri_bakiye INTO v_bakiye
    FROM abc_musteriler
    WHERE musteri_id = p_musteri_id;
    
    -- Kontroller
    IF NOT EXISTS (SELECT 1 FROM abc_musteriler WHERE musteri_id = p_musteri_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Müşteri bulunamadı!';
    ELSEIF p_odeme_miktar <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ödeme miktarı 0 veya negatif olamaz!';
    ELSEIF p_odeme_miktar > v_bakiye THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ödeme miktarı bakiyeden büyük olamaz!';
    ELSE
        START TRANSACTION;
        
        
        INSERT INTO abc_odemeler (odeme_id, musteri_id, odeme_miktar, odeme_tarih, odeme_tip)
        VALUES (p_odeme_id, p_musteri_id, p_odeme_miktar, 
                IFNULL(p_odeme_tarih, NOW()),
                IFNULL(p_odeme_tip, 'Nakit'));
        
        
        UPDATE abc_musteriler
        SET musteri_bakiye = musteri_bakiye - p_odeme_miktar
        WHERE musteri_id = p_musteri_id;
        
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_MusteriEkle(
    IN mid VARCHAR(64),
    IN ad VARCHAR(64),
    IN soyad VARCHAR(64),
    IN tip VARCHAR(64),
    IN sinif VARCHAR(25),
    IN tel VARCHAR(25),
    IN mail VARCHAR(250),
    IN bakiye FLOAT
)
BEGIN
    INSERT INTO abc_musteriler 
    (musteri_id, musteri_ad, musteri_soyad, musteri_tip, musteri_sinif, musteri_tel, musteri_mail, musteri_bakiye)
    VALUES (mid, ad, soyad, tip, sinif, tel, mail, bakiye);
END$$

CREATE PROCEDURE sp_UrunEkle(
    IN uid VARCHAR(64),
    IN ad VARCHAR(250),
    IN kategori VARCHAR(250),
    IN fiyat FLOAT,
    IN aciklama VARCHAR(250),
    IN stok FLOAT,
    IN birim VARCHAR(50)
)
BEGIN
    INSERT INTO abc_urunler 
    (urun_id, urun_ad, urun_kategori, urun_fiyat, urun_aciklama, urun_stok, urun_birim)
    VALUES (uid, ad, kategori, fiyat, aciklama, stok, birim);
END$$

CREATE PROCEDURE sp_SatisSil(
    IN p_satis_id VARCHAR(20)
)
BEGIN
    DECLARE v_musteri_id VARCHAR(20);
    DECLARE v_urun_id VARCHAR(20);
    DECLARE v_satis_miktar INT;
    DECLARE v_satis_fiyat DECIMAL(10,2);
    
    
    SELECT musteri_id, urun_id, satis_miktar, satis_fiyat
    INTO v_musteri_id, v_urun_id, v_satis_miktar, v_satis_fiyat
    FROM abc_satislar
    WHERE satis_id = p_satis_id;
    
    IF v_musteri_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Satış kaydı bulunamadı!';
    ELSE
        START TRANSACTION;
        
        
        UPDATE abc_urunler
        SET urun_stok = urun_stok + v_satis_miktar
        WHERE urun_id = v_urun_id;
        
        
        DELETE FROM abc_satislar
        WHERE satis_id = p_satis_id;
        
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_OdemeSil(
    IN p_odeme_id VARCHAR(20)
)
BEGIN
    DECLARE v_musteri_id VARCHAR(20);
    DECLARE v_odeme_miktar DECIMAL(10,2);
    
    
    SELECT musteri_id, odeme_miktar
    INTO v_musteri_id, v_odeme_miktar
    FROM abc_odemeler
    WHERE odeme_id = p_odeme_id;
    
    IF v_musteri_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ödeme kaydı bulunamadı!';
    ELSE
        START TRANSACTION;
        
       
        UPDATE abc_musteriler
        SET musteri_bakiye = musteri_bakiye + v_odeme_miktar
        WHERE musteri_id = v_musteri_id;
        
        
        DELETE FROM abc_odemeler
        WHERE odeme_id = p_odeme_id;
        
        COMMIT;
    END IF;
END$$

CREATE PROCEDURE sp_MusteriGuncelle(
    IN mid VARCHAR(64),
    IN ad VARCHAR(64),
    IN soyad VARCHAR(64),
    IN tip VARCHAR(64),
    IN sinif VARCHAR(25),
    IN tel VARCHAR(25),
    IN mail VARCHAR(250),
    IN bakiye FLOAT
)
BEGIN
    UPDATE abc_musteriler 
    SET musteri_ad = ad, musteri_soyad = soyad, musteri_tip = tip,
        musteri_sinif = sinif, musteri_tel = tel, musteri_mail = mail,
        musteri_bakiye = bakiye
    WHERE musteri_id = mid;
END$$

CREATE PROCEDURE sp_UrunGuncelle(
    IN uid VARCHAR(64),
    IN ad VARCHAR(250),
    IN kategori VARCHAR(250),
    IN fiyat FLOAT,
    IN aciklama VARCHAR(250),
    IN stok FLOAT,
    IN birim VARCHAR(50)
)
BEGIN
    UPDATE abc_urunler 
    SET urun_ad = ad, urun_kategori = kategori, urun_fiyat = fiyat, 
        urun_aciklama = aciklama, urun_stok = stok, urun_birim = birim
    WHERE urun_id = uid;
END$$

CREATE FUNCTION fn_MusteriTamAd(mid VARCHAR(64)) 
RETURNS VARCHAR(128)
READS SQL DATA
BEGIN
    DECLARE tamad VARCHAR(128);
    SELECT CONCAT(musteri_ad, ' ', musteri_soyad) INTO tamad 
    FROM abc_musteriler 
    WHERE musteri_id = mid;
    RETURN tamad;
END$$

DELIMITER ;


ALTER DATABASE abc_kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE abc_musteriler CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


 ALTER TABLE abc_urunler CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE abc_satislar CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE abc_odemeler CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


DROP PROCEDURE IF EXISTS sp_SatisGuncelle;
DELIMITER //
CREATE PROCEDURE sp_SatisGuncelle(
    IN p_satis_id VARCHAR(20),
    IN p_musteri_id VARCHAR(20),
    IN p_urun_id VARCHAR(20),
    IN p_satis_miktar INT,
    IN p_satis_fiyat DECIMAL(10,2)
)
BEGIN
    DECLARE v_eski_miktar INT;
    DECLARE v_eski_fiyat DECIMAL(10,2);
    DECLARE v_eski_musteri_id VARCHAR(20);
    DECLARE v_eski_urun_id VARCHAR(20);
    DECLARE v_stok INT;
    
    
    SELECT satis_miktar, satis_fiyat, musteri_id, urun_id
    INTO v_eski_miktar, v_eski_fiyat, v_eski_musteri_id, v_eski_urun_id
    FROM abc_satislar 
    WHERE satis_id = p_satis_id;
    
    
    SELECT urun_stok INTO v_stok 
    FROM abc_urunler 
    WHERE urun_id = p_urun_id;
    
    
    IF p_urun_id = v_eski_urun_id THEN
        SET v_stok = v_stok + v_eski_miktar;
    END IF;
    
    IF v_stok < p_satis_miktar THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Yetersiz stok!';
    ELSE
        START TRANSACTION;
        
        
        UPDATE abc_urunler 
        SET urun_stok = urun_stok + v_eski_miktar
        WHERE urun_id = v_eski_urun_id;
        
        
        UPDATE abc_satislar 
        SET 
            musteri_id = p_musteri_id,
            urun_id = p_urun_id,
            satis_miktar = p_satis_miktar,
            satis_fiyat = p_satis_fiyat
        WHERE satis_id = p_satis_id;
        
        
        UPDATE abc_urunler 
        SET urun_stok = urun_stok - p_satis_miktar
        WHERE urun_id = p_urun_id;
        
        COMMIT;
    END IF;
END//

DELIMITER ;


DROP PROCEDURE IF EXISTS sp_OdemeGuncelle;
DELIMITER //
CREATE PROCEDURE sp_OdemeGuncelle(
    IN p_odeme_id VARCHAR(20),
    IN p_musteri_id VARCHAR(20),
    IN p_odeme_miktar DECIMAL(10,2),
    IN p_odeme_tip VARCHAR(50)
)
BEGIN
    DECLARE v_eski_miktar DECIMAL(10,2);
    DECLARE v_eski_musteri_id VARCHAR(20);
    
    
    SELECT odeme_miktar, musteri_id
    INTO v_eski_miktar, v_eski_musteri_id
    FROM abc_odemeler 
    WHERE odeme_id = p_odeme_id;
    
    IF v_eski_musteri_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ödeme kaydı bulunamadı!';
    ELSE
        START TRANSACTION;
        
        
        UPDATE abc_musteriler 
        SET musteri_bakiye = musteri_bakiye + v_eski_miktar
        WHERE musteri_id = v_eski_musteri_id;
        
        
        UPDATE abc_odemeler 
        SET 
            musteri_id = p_musteri_id,
            odeme_miktar = p_odeme_miktar,
            odeme_tip = p_odeme_tip
        WHERE odeme_id = p_odeme_id;
        
        
        UPDATE abc_musteriler 
        SET musteri_bakiye = musteri_bakiye - p_odeme_miktar
        WHERE musteri_id = p_musteri_id;
        
        COMMIT;
    END IF;
END //
DELIMITER ;


DROP TRIGGER IF EXISTS tg_SatisOncesiStokKontrol;
DROP TRIGGER IF EXISTS tg_SatisSonrasiStokDusur;

DELIMITER //
CREATE TRIGGER tg_SatisOncesiStokKontrol
BEFORE INSERT ON abc_satislar
FOR EACH ROW
BEGIN
    DECLARE mevcut INT;
    SELECT urun_stok INTO mevcut FROM abc_urunler WHERE urun_id = NEW.urun_id;
    IF mevcut < NEW.satis_miktar THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stokta yeterli miktar yok';
    END IF;
END//
DELIMITER ; 
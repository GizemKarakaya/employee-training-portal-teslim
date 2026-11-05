# 2–3 Dakikalık Ekran Kaydı Akışı

## Kayıt öncesi

- Her iki uygulamada yönetici hesabı oluşturulmuş olsun.
- Personel Yönetim Sistemi ve Eğitim Yönetim Portalı ayrı terminallerde çalışsın.
- İki `.env` dosyasındaki `PERSONNEL_API_TOKEN` değerinin eşleştiğini doğrulayın; token değerini ekranda göstermeyin.
- Gerçek kişisel bilgi yerine `example.test` uzantılı e-posta ve demo amaçlı sahte personel bilgileri kullanın.
- Ekran kaydında bunun **Teknik Değerlendirme Projesi** olduğunu belirtin; resmî üretim sistemi gibi sunmayın.

## 0:00–0:15 — Personel Yönetim Sistemi

1. `http://127.0.0.1:8000/login` adresinden yönetici olarak giriş yapın.
2. Ardıç Otomotiv markasını, **Personel Yönetimi** sayfasını ve **Personel Listesi** tablosunu kısaca gösterin.

## 0:15–0:45 — Personel oluşturma ve güncelleme

1. **Yeni Personel Ekle** formunu açıp sahte bir ad, `example.test` uzantılı e-posta ve departmanla kayıt oluşturun.
2. Listede oluşan kaydı gösterin; **Düzenle** ile departmanını değiştirip kaydedin.
3. Güncelleme bildirimini ve kayıtlı değerleri gösterin.

## 0:45–1:05 — Eğitim Portalı ve API verisi

1. `http://127.0.0.1:8001/login` adresinden Portal'a giriş yapın.
2. **Genel Bakış** ekranındaki toplam eğitim, atama, **Bekleyen Eğitimler** ve **Tamamlanan Eğitimler** sayaçlarını gösterin.
3. Bir eğitimin detayını açıp yeni personelin seçim listesinde göründüğünü belirtin. Bu listenin portal veritabanından değil, Personel Yönetim Sistemi REST API'sinden geldiğini açıklayın.

## 1:05–1:40 — Eğitim oluşturma ve atama

1. **Yeni Eğitim Oluştur** ile kısa, demo amaçlı bir eğitim ekleyin.
2. Eğitim detayında **Personele Eğitim Ata** bölümünden yeni personeli seçip atayın.
3. Başarı bildirimini ve **Atanmış Eğitimler** tablosunda **Bekliyor** durumunu gösterin.

## 1:40–2:10 — Tamamlama ve kontrol paneli

1. **Tamamlandı işaretle** eylemini kullanın.
2. Durumun **Tamamlandı** olduğunu ve tamamlanma tarihinin göründüğünü doğrulayın.
3. Sayfayı yenileyip kaydın korunduğunu gösterin; **Genel Bakış**'a dönüp sayaçların güncellendiğini belirtin.

## 2:10–2:30 — Mimari özeti

Kapanışta iki uygulamanın ayrı Laravel uygulamaları ve ayrı SQLite veritabanları olduğunu; portalda personel tablosu bulunmadığını; çalışan bilgilerinin token korumalı REST API üzerinden alındığını; eğitim ve atamaların Portal veritabanında saklandığını özetleyin.

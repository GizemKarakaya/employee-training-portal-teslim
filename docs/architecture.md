# Mimari

## Servis sınırları

```text
Tarayıcı
  ├── Personnel Service (127.0.0.1:8000) ── personnel-service/database/database.sqlite
  │     ├── Blade personel yönetimi
  │     └── GET /api/employees[/{id}] — Bearer token
  │
  └── Training Portal (127.0.0.1:8001) ─── training-portal/database/database.sqlite
        ├── Dashboard, eğitimler ve atamalar
        └── PersonnelApiService ── HTTP/JSON ──> Personnel Service
```

Her uygulama kendi Laravel kurulumu, `.env`, migration'ları, modelleri, rotaları ve SQLite dosyasıyla çalışır. İki veritabanı arasında bağlantı veya çapraz sorgu yoktur.

## Veri sahipliği

- Personnel Service, `employees` tablosundaki çalışan bilgilerini yönetir.
- Training Portal yalnızca `trainings` ve `training_assignments` tablolarını yönetir.
- Atama tablosundaki `training_id` yerel foreign key'dir. `personnel_id`, harici sistemin kimliğidir; personel tablosu, yerel foreign key veya personel verisi kopyası oluşturulmaz.
- Aynı eğitim/kişi ikilisi veritabanı `unique(training_id, personnel_id)` kısıtıyla tekil tutulur.

## API akışı

1. Portal, `PersonnelApiService` üzerinden Laravel HTTP Client'ı kullanır; base URL ve Bearer token `config/services.php` üzerinden `.env` dosyasından okunur. İstek zaman aşımı beş saniyedir.
2. Atama öncesi portal `GET /api/employees/{id}` çağrısıyla kimliği doğrular. Kişi yoksa kayıt oluşturulmaz.
3. Eğitim detayında personel adları ve iletişim bilgileri güncel API yanıtından oluşturulur, yerel veritabanına yazılmaz.
4. Harici personel silinmişse tarihsel atama korunur ve kişinin artık erişilebilir olmadığı belirtilir. API erişilemiyorsa bu durum silinmiş kişiyle karıştırılmaz.
5. API bağlantı hatası, `401`, `404`, `5xx` ve biçimi geçersiz yanıtlar kontrollü şekilde ele alınır; hatalı personel bilgisi üretilmez.
6. Portalın `GET /api/assignments/{id}` uç noktası yerel atama durumunu JSON olarak döndürür; bu uç nokta demo arayüzü gibi kimlik doğrulamasızdır.

## Güvenlik

- Personnel Service token'ı `.env` üzerinden alır; middleware beklenen ve gelen token'ı `hash_equals` ile karşılaştırır.
- Boş veya yapılandırılmamış token tüm API isteklerini reddeder.
- Token HTML'e, tarayıcı JavaScript'ine veya API yanıtına konmaz; Portal'dan sunucu taraflı `withToken()` ile gönderilir.
- `.env` ve SQLite dosyaları Git dışında tutulur. Gerçek dağıtımda HTTPS kullanılmalı; yerel HTTP yalnızca demo içindir.
- Gerçek dağıtım öncesinde Training Portal kullanıcı arayüzüne ve atama durum API'sine kimlik doğrulaması/yetkilendirme eklenmelidir.

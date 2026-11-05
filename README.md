# Ardıç Otomotiv | Personel Sistemi ile Entegre Eğitim Portalı

Bu depo, Ardıç Otomotiv Full Stack Yazılım Geliştirici mini teknik çalışması için hazırlanmış bir demo uygulamadır. Amaç, personel kayıtlarını ayrı bir sistemde yönetmek ve eğitim portalında çalışanlara eğitim atayarak tamamlanma durumlarını takip etmektir. Bu proje şirketin resmî veya üretim amaçlı sistemi değildir.

## 1. Projenin amacı

Personel Yönetim Sistemi; çalışanların ad, soyad, e-posta ve departman bilgilerini yönetir ve bu kayıtları korumalı REST API üzerinden sunar. Eğitim Yönetim Portalı bu bilgileri API'den alır; eğitim ve atama kayıtlarını kendi veritabanında tutar.

## 2. Kullanılan teknolojiler

- PHP 8.3 veya üzeri ve Laravel 13
- Blade şablonları, HTML, CSS ve yalın JavaScript
- İki uygulama için ayrı SQLite veritabanları
- Composer ve PHPUnit
- Vite/npm ile statik varlıkların üretim derlemesi

PHP kurulumu SQLite/PDO SQLite, cURL, mbstring ve OpenSSL eklentilerini içermelidir. npm ve Node.js, yalnızca frontend varlıklarını derlemek için gerekir. Docker yapılandırması bu projenin kurulum yöntemi değildir.

## 3. Sistem mimarisi

```text
Tarayıcı
  ├── Personel Yönetim Sistemi (Laravel, 8000)
  │     ├── personnel-service/database/database.sqlite
  │     └── Token korumalı personel REST API
  └── Eğitim Yönetim Portalı (Laravel, 8001)
        ├── training-portal/database/database.sqlite
        └── Personel bilgilerini HTTP REST API ile alır
```

İki uygulama ayrı sorumluluklara, Laravel kurulumlarına ve SQLite dosyalarına sahiptir. Böylece Eğitim Yönetim Portalı personel veritabanına doğrudan bağlanmaz; servis sınırında HTTP API kullanılır. Portalın veritabanında personel tablosu bulunmaz.

## 4. Personel Yönetim Sistemi

Personel Yönetim Sistemi personel ekleme, listeleme, düzenleme ve silme işlemlerini sağlar. Bilgiler `personnel-service/database/database.sqlite` dosyasındaki `employees` tablosunda tutulur. E-posta adresi benzersizdir; zorunlu alanlar, e-posta biçimi ve alan uzunlukları sunucu tarafında doğrulanır.

Yönetim ekranları oturum açmış yönetici gerektirir. Personel kayıt API'si ayrı olarak Bearer token ile korunur.

## 5. Eğitim Yönetim Portalı

Eğitim Yönetim Portalı eğitim oluşturma ve listeleme, çalışanlara eğitim atama, bekleyen/tamamlanan durumları ve tamamlanma tarihini gösterme işlevlerini sunar. Eğitimler `trainings` tablosunda; atamalar `training_assignments` tablosunda saklanır.

Atamada personel adı/e-postası kopyalanmaz; harici personel kimliği `personnel_id` olarak tutulur. Atama listesi ekrana hazırlanırken çalışan ayrıntıları Personel Yönetim Sistemi API'sinden alınır. Aynı çalışana aynı eğitimin ikinci kez atanması uygulama kontrolü ve veritabanındaki benzersiz `(training_id, personnel_id)` kısıtıyla engellenir.

Personel servisine erişilemezse portal eğitim ve mevcut atama geçmişini göstermeyi sürdürür; kişi ayrıntısının geçici olarak alınamadığını bildirir ve yeni atama işlemini engeller.

## 6. REST API entegrasyonu

Personnel Service uç noktaları:

| Metot | Uç nokta | Erişim | Açıklama |
|---|---|---|---|
| `GET` | `/api/employees` | Bearer token | Personelleri JSON listesi olarak döndürür |
| `GET` | `/api/employees/{employee}` | Bearer token | Bir personel kaydını döndürür |

Yanıtlar `data` alanı içinde personel kimliği, ad, soyad, e-posta ve departman bilgilerini taşır. Eksik/yanlış token `401`, bulunmayan personel `404` döndürür. API dakikada 120 istekle sınırlandırılmıştır.

Portal, personel listesi için `GET /api/employees`; atama öncesi personel doğrulaması için `GET /api/employees/{id}` çağrısını sunucu tarafındaki [PersonnelApiService](./training-portal/app/Services/PersonnelApiService.php) üzerinden yapar. Servis URL'si ve ortak token, uygulama ortam değişkenlerinden yüklenir:

```dotenv
# personnel-service/.env
PERSONNEL_API_TOKEN=<güçlü ve rastgele ortak token>

# training-portal/.env
PERSONNEL_SERVICE_URL=http://127.0.0.1:8000
PERSONNEL_API_TOKEN=<Personnel Service ile aynı token>
```

Token kaynak koda veya tarayıcıya verilmez. API atama durumu `GET /api/assignments/{assignment}` adresindedir ve Portal'da oturum açmış yönetici gerektirir.

## 7. Güvenlik yaklaşımı

- Her uygulamanın yönetim arayüzü kendi yönetici oturumu ile korunur; hesaplar `php artisan admin:create` komutuyla oluşturulur, parolalar hash'lenir.
- Personel API'si Bearer token'ı sabit zamanlı `hash_equals` karşılaştırmasıyla doğrular. Token yalnızca sunucu tarafındaki `.env` yapılandırmasında bulunmalıdır.
- Login denemeleri sınırlandırılır; Personnel API için makul istek sınırı uygulanır.
- Formlar CSRF korumalıdır. Blade, kullanıcıdan veya API'den gelen değerleri HTML metni olarak kaçışlayarak işler.
- CORS için wildcard origin tanımlı değildir; servisler arası sunucu isteği CORS gerektirmez.
- `.env`, gerçek token/parolalar, SQLite dosyaları, `vendor`, `node_modules`, loglar ve derlenmiş varlıklar Git dışında tutulur.
- Örnek `.env` dosyalarındaki token metinleri yalnızca yer tutucudur; gerçek kimlik bilgisi değildir.

## 8. Kurulum

Ön koşullar: PHP 8.3+, Composer 2, SQLite/PDO SQLite ve gerekli PHP eklentileri. Vite derlemesi için Node.js/npm gereklidir. macOS'ta PHP/Composer Homebrew üzerinden kurulabilir; diğer işletim sistemlerinde uygun resmî paket yöneticisi kullanılabilir.

Projeyi indirdikten sonra iki uygulamayı ayrı ayrı hazırlayın. Komutlar depo kök dizininden çalıştırılır:

```bash
# Personel uygulaması
cd personnel-service
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
npm ci
npm run build
```

`personnel-service/.env` içinde `DB_CONNECTION=sqlite`, `APP_URL=http://127.0.0.1:8000` ve `APP_DEBUG=false` değerlerini doğrulayın. `PERSONNEL_API_TOKEN` için terminalde güçlü bir değer üretin; örneğin `openssl rand -hex 32`. Değerin kendisini paylaşmayın veya depoya eklemeyin.

```bash
# Depo kök dizinine dönüp Portal'ı hazırlayın
cd ../training-portal
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
npm ci
npm run build
```

`training-portal/.env` içinde aşağıdaki değerleri ayarlayın:

```dotenv
APP_URL=http://127.0.0.1:8001
APP_DEBUG=false
DB_CONNECTION=sqlite
PERSONNEL_SERVICE_URL=http://127.0.0.1:8000
PERSONNEL_API_TOKEN=<Personnel Service ile aynı güçlü token>
```

Her uygulama için kendi dizininde ilk kurulum migration'larını ve demo seed verilerini çalıştırın:

```bash
php artisan migrate --seed
```

Bu adımı önce `personnel-service`, ardından `training-portal` dizininde uygulayın. Yeni/boş kurulumda seeder'lar örnek personel, eğitim ve atama kayıtlarını oluşturur. Seed işlemi demo verilerini ekler/günceller; kişisel test kayıtları için `example.test` uzantılı sahte değerler kullanın. Mevcut veriyi korumak için `migrate:fresh` veya veritabanı silme komutlarını kullanmayın.

Her uygulama için ayrı bir yönetici hesabı oluşturun. Komut parola ve tekrarını terminalde gizli olarak ister:

```bash
# Birinci terminalde, personnel-service dizininde
php artisan admin:create

# İkinci terminalde, training-portal dizininde
php artisan admin:create
```

Her iki uygulamanın `users` tablosu ve yönetici hesabı birbirinden bağımsızdır.

## 9. Veritabanı yapısı

| Uygulama | Tablolar | İçerik |
|---|---|---|
| Personel Yönetim Sistemi | `employees`, `users` ve Laravel altyapı tabloları | Personel kayıtları ve bu uygulamanın yönetici hesapları |
| Eğitim Yönetim Portalı | `trainings`, `training_assignments`, `users` ve Laravel altyapı tabloları | Eğitimler, harici personel kimlikleriyle atamalar ve bu uygulamanın yöneticileri |

İki SQLite dosyası birbirinden bağımsızdır. Portalın `personnel_id` alanı Personel API'sindeki kimliği gösterir; iki veritabanı arasında foreign key veya JOIN yoktur.

SQLite bu mini çalışma için sunucu kurulumu gerektirmeyen, kolay taşınabilir ve yerel demo/test kullanımına uygun olduğundan tercih edilmiştir. Eşzamanlı yazma yükü, yüksek kullanılabilirlik veya merkezi operasyon ihtiyacı olan üretim ortamında yönetilen bir ilişkisel veritabanı (ör. PostgreSQL), yedekleme ve izleme çözümü değerlendirilebilir.

## 10. Uygulamaları çalıştırma ve testler

Uygulamaları ayrı terminallerde başlatın:

```bash
# personnel-service dizininde
php artisan serve --host=127.0.0.1 --port=8000

# training-portal dizininde
php artisan serve --host=127.0.0.1 --port=8001
```

- Personel Yönetim Sistemi: <http://127.0.0.1:8000/login>
- Eğitim Yönetim Portalı: <http://127.0.0.1:8001/login>

Her uygulama dizininde PHPUnit testlerini çalıştırın:

```bash
php artisan test
```

Testler izole test veritabanı kullanır; portalın birim/feature testlerinde dış HTTP servisi uygun senaryolarda taklit edilir. Test ortamı gerçek demo SQLite dosyalarını sıfırlamak için kullanılmamalıdır.

## 11. Demo kullanım senaryosu

1. Personel Yönetim Sistemi'ne giriş yapıp yeni bir test personeli oluşturun ve bilgilerini güncelleyin.
2. Eğitim Yönetim Portalı'na geçin; eğitim detayındaki çalışan seçim listesini yenileyerek yeni personelin API'den geldiğini gösterin.
3. Bir eğitim oluşturun veya mevcut bir eğitimi açın; personeli seçip atayın.
4. Atamanın önce “Bekliyor” olduğunu, sonra tamamlandı olarak işaretlendiğini ve tarihin kaydedildiğini gösterin.
5. Genel Bakış'a dönüp toplam eğitim, atama, bekleyen ve tamamlanan sayaçlarını gösterin.


## 12. Teknik kararlar ve sonraki geliştirme olanakları

- **İki Laravel uygulaması:** Personel yönetimi ile eğitim takibi farklı sorumluluklar ve veri sahipliği taşır. Ayrı uygulamalar bu sınırı görünür kılar ve API sözleşmesini test edilebilir hale getirir.
- **İki SQLite veritabanı:** Bir servisin diğerinin tablolarına bağımlı olmasını önler; yerel kurulum ve mini demo sürecini basitleştirir.
- **REST API:** Portal, personel verisini kendi kopyasında tutmak yerine ihtiyaç anında servis üzerinden alır. API token'ı sunucu-sunucu iletişimini sınırlar.
- **Tekrarlı atama koruması:** Uygulama kontrolü kullanıcıya anlaşılır hata verir; veritabanı benzersiz kısıtı eşzamanlı tekrarları da engeller.
- **Servis kesintisi:** Portal mevcut atama kayıtlarını göstermeye devam eder, dış personel ayrıntısını erişilemez olarak belirtir ve yeni personel doğrulaması gerektiren atamayı kabul etmez.

Üretim ortamına geçiş ayrı bir dağıtım ve güvenlik çalışması gerektirir: HTTPS/TLS arkasında `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true` ve `SESSION_SAME_SITE=lax` ayarlarını kullanın. Yerel HTTP demoda `SESSION_SECURE_COOKIE=false` kalabilir; HTTPS olmayan ortamda bunu `true` yapmak oturum çerezinin gönderilmesini engelleyebilir. Ayrıca secret manager ile anahtar/token yönetimi, merkezi veritabanı ve yedekleme, log/izleme, erişim politikaları ve dağıtım altyapısına uygun oran sınırlama planlanmalıdır. Bu depodaki demo, üretim ortamında çalıştığı iddiasında değildir.

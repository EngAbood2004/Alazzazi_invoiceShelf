# Alazzazi InvoiceShelf

نظام إدارة منشأة ومخزون مبني باستخدام Laravel 13 ويدعم العربية والإنجليزية والفرنسية والإسبانية والألمانية والتركية والصينية والهولندية البلجيكية.

## الإضافات الحالية
- صفحة التقارير: ملخص الدفعات وحركات المخزون والحضور ضمن نطاق تاريخي مع إمكانية الطباعة.
- مركز الاتصالات: محادثات داخلية وبريد إلكتروني وWhatsApp Business Cloud API وSMS عبر Twilio، مع حفظ الردود الواردة في لوحة المحادثات.
- تحديد الموقع: طلب إذن المتصفح لتحديد إحداثيات GPS، حفظها بموافقة المستخدم، وفتحها على Google Maps. المستخدم العادي يرى موقعه فقط، والمدير يمكنه الاطلاع على المواقع المحفوظة للمستخدمين.
- إضافة ترجمات لواجهات الصفحات الجديدة في ملفات `resources/lang/*.json`.
- إعداد MySQL الافتراضي في `.env.example` باسم قاعدة البيانات `AlazzaziINfoiceShelf`.

## متطلبات التشغيل
- PHP 8.3 أو أحدث مع امتداد `pdo_mysql`
- Composer
- Node.js و npm
- MySQL / Laragon

## تشغيل المشروع محليًا على Windows / Laragon
1. فك الضغط عن المشروع وافتح Terminal داخل مجلد المشروع.
2. أنشئ قاعدة بيانات باسم `AlazzaziINfoiceShelf` من HeidiSQL أو phpMyAdmin.
3. انسخ `.env.example` إلى `.env` إذا لم يكن ملف `.env` موجودًا.
4. اضبط إعدادات الاتصال بقاعدة البيانات في `.env`:

```dotenv
APP_NAME="Alazzazi InvoiceShelf"
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=AlazzaziINfoiceShelf
DB_USERNAME=root
DB_PASSWORD=
```

5. نفّذ الأوامر التالية:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan optimize:clear
php artisan serve
```

لا ينشئ الـ seeder بيانات تجريبية أو كلمة مرور افتراضية. لإنشاء حساب مدير أول مرة، أضف هذه القيم إلى `.env` قبل `php artisan migrate --seed` (كلمة مرور قوية بطول 12 محرفًا على الأقل)، ثم احتفظ بالسر ولا تشاركه:

```dotenv
BOOTSTRAP_ADMIN_NAME="اسم المدير"
BOOTSTRAP_ADMIN_EMAIL="admin@example.com"
BOOTSTRAP_ADMIN_PASSWORD="ضع-كلمة-مرور-قوية-وفريدة-هنا"
```

إذا لم تضبط القيم، لن يُنشأ حساب مدير تلقائيًا. بعد ضبطها شغّل `php artisan db:seed`. لا يغير الـ seeder حسابًا موجودًا ولا ينشئ بيانات تجريبية عند تشغيله.

افتح العنوان الذي يظهره `php artisan serve`. تحديد الموقع يعمل على `localhost` أو عبر HTTPS، ويتطلب السماح بإذن الموقع في المتصفح.

### إعداد إرسال البريد الإلكتروني

تُرسل الرسائل الداخلية إشعارًا إلى البريد المسجّل للمستلم. اضبط إعدادات SMTP في `.env` قبل الإرسال. عند استخدام Gmail، فعّل التحقق بخطوتين وأنشئ **App Password** مخصصًا؛ كلمة مرور تسجيل الدخول العادية إلى Gmail لن تعمل عادةً:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-16-character-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

بعد تغيير إعدادات البريد شغّل `php artisan optimize:clear`. لا تشارك ملف `.env` أو كلمة مرور التطبيق.

### إعداد مركز الاتصالات

يستخدم مركز الاتصالات جدولًا جديدًا `communication_messages`؛ لا يعيد إنشاء جدول `customer_messages` ولا يحذف أو ينقل الرسائل الداخلية القديمة. قبل التشغيل افحص الملفات والمخطط والعدد الحالي للرسائل من PowerShell:

```powershell
Set-Location 'E:\laragon\www\Alazzazi_invoiceShelf\laraventry'
Get-Content .\app\Http\Controllers\CustomerMessageController.php
Get-Content .\app\Models\CustomerMessage.php
Get-Content .\database\migrations\2026_10_19_140000_create_customer_messages_table.php
Get-Content .\resources\views\messages\index.blade.php
php artisan db:table customer_messages --json
php artisan tinker --execute='dump(App\Models\CustomerMessage::count());'
```

أضف متغيرات القنوات إلى `.env` **من دون مشاركة الملف أو وضع الأسرار في Git**. أكمل إعداد Gmail SMTP أعلاه، واستعمل App Password نفسه لـ IMAP أو قيمة منفصلة:

```dotenv
MAIL_IMAP_ENABLED=true
MAIL_IMAP_HOST=imap.gmail.com
MAIL_IMAP_PORT=993
MAIL_IMAP_MAILBOX=INBOX
MAIL_IMAP_USERNAME=your-address@gmail.com
MAIL_IMAP_PASSWORD=your-gmail-app-password

# ID مستخدم موجود ستُسند إليه الرسائل الواردة، تحقّق منه في جدول users
COMMUNICATIONS_INBOX_USER_ID=1

# WhatsApp Business Platform / Cloud API الرسمي من Meta
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_APP_SECRET=
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_API_VERSION=v24.0

# Twilio Programmable Messaging
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM=+15551234567
TWILIO_WEBHOOK_URL=https://your-public-domain.example/api/webhooks/twilio/inbound
TWILIO_STATUS_CALLBACK_URL=https://your-public-domain.example/api/webhooks/twilio/status
```

أوامر التحقق والتشغيل الآمن على Windows (تطبّق migration الإضافية فقط؛ **لا تستخدم** `migrate:fresh`):

```powershell
php artisan migrate --pretend
php artisan migrate
php artisan optimize:clear
php artisan route:list --path=webhooks
php artisan route:list --path=messages
php artisan test tests/Feature/CustomerMessageTest.php tests/Feature/CommunicationCenterTest.php
php artisan serve
```

للاستقبال، فعّل IMAP في حساب Gmail وثبّت امتداد PHP IMAP في **نفس PHP CLI** الذي يعرضه `php --ini` (`php -m` يجب أن يعرض `imap`). الفحص الحالي لهذا المشروع لم يجد الامتداد؛ أمر السحب سيفشل برسالة واضحة إلى أن يتفعّل. شغّل `php artisan schedule:work` أثناء التطوير، أو أضف مجدول Laravel إلى Windows Task Scheduler ليعمل كل دقيقة. Gmail يُفحص دوريًا، بينما Twilio وMeta يرسلان Webhooks فور وصول الرد.

أدخل روابط HTTPS عامة في لوحتي Twilio وMeta: `https://your-public-domain.example/api/webhooks/twilio/inbound` لاستقبال SMS، ومسار `/api/webhooks/whatsapp` للتحقق واستقبال WhatsApp؛ عيّن Callback status الخاص بـ Twilio إلى `/api/webhooks/twilio/status`. لا يمكن للخدمات الخارجية الوصول إلى `127.0.0.1`، لذا يحتاج التطوير المحلي إلى نفق HTTPS عام أو بيئة منشورة. تتطلب WhatsApp رسائل قالب معتمدة خارج نافذة خدمة العملاء ذات 24 ساعة؛ الإرسال الحر من الواجهة يعرض فشل API بوضوح إذا كانت النافذة مغلقة. تأكد من موافقات المستلمين وسياسات Meta/Twilio قبل مراسلتهم.

> **مهم:** لا تشغّل `php artisan migrate:fresh` على قاعدة بيانات فيها بيانات تريد الاحتفاظ بها، لأنه يحذف جميع الجداول ويعيد إنشائها. استخدم `php artisan migrate` لتطبيق migrations الجديدة.

## الفوترة والتحليلات الذكية (الإضافة الجديدة)

بعد سحب التعديلات شغّل `php artisan migrate` (بدون fresh). القائمة الجانبية فيها قسم **الفوترة** للمدير والمحاسب:

| الوحدة | ماذا تفعل |
|---|---|
| العملاء | بيانات العميل، رقم واتساب مع رمز الدولة، العملة، الرصيد المستحق |
| الفواتير | بنود مرتبطة بالمنتجات، خصم وضرائب، ترقيم تلقائي `INV-000001`، عند الإرسال يُخصم المخزون من المستودع المختار ويُعاد عند الإلغاء، دفعات جزئية، رابط عام للعميل `/i/{token}` للعرض والتنزيل والدفع |
| عروض الأسعار | تُحوّل إلى فاتورة بضغطة |
| الفواتير المتكررة | أسبوعي/شهري/ربع سنوي/سنوي، تُنشأ يوميًا 06:00 |
| المصروفات | فئات وإيصالات (صورة/PDF) وتدخل في صافي التدفق |
| الرؤى الذكية | مؤشرات الشهر مقارنة بالشهر السابق، أعمار الذمم، أهم العملاء والمنتجات، توقع 3 أشهر |
| إعدادات الفوترة | بيانات الشركة، البادئات، مدة الاستحقاق، أيام وقنوات التذكير، الضرائب، حالة التكاملات |

### التوقع
`App\Services\Analytics\Forecaster`: مع أقل من 24 شهرًا من البيانات يستخدم اتجاهًا خطيًا مع متوسط الأشهر الأخيرة، وبعد تراكم سنتين يتحول تلقائيًا إلى نموذج **Holt-Winters** موسمي (m=12) يختار معاملات α/β/γ بالبحث الشبكي ويعرض نسبة الخطأ MAPE ومستوى الثقة.

### شرح الأسباب بالذكاء الاصطناعي
ضع `OPENAI_API_KEY` (والنموذج `OPENAI_MODEL`، الافتراضي `gpt-4o-mini`). تُرسل أرقام مجمّعة فقط بدون أسماء أو بيانات تواصل العملاء، والنتيجة تُخزّن 6 ساعات. بدون مفتاح أو عند فشل الاتصال تظهر رؤى مبنية على القواعد.

### التذكير بعد تاريخ الاستحقاق (بريد + WhatsApp)
- يحتاج مجدول Laravel: على السيرفر يعمل داخل حاوية Docker تلقائيًا؛ محليًا شغّل `php artisan schedule:work`.
- الأمر `php artisan invoices:send-reminders` يرسل يوميًا 09:00 حسب الأيام في الإعدادات (افتراضيًا 1,7,14,30)، كل مرحلة مرة لكل قناة.
- WhatsApp خارج نافذة 24 ساعة يحتاج **قالبًا معتمدًا** من Meta: اسمه في `WHATSAPP_REMINDER_TEMPLATE`، ومتغيراته `{{1}}` اسم العميل، `{{2}}` رقم الفاتورة، `{{3}}` المبلغ، `{{4}}` أيام التأخير، `{{5}}` رابط الدفع.

### PDF عبر Gotenberg
ضع `GOTENBERG_URL` (في docker-compose هو `http://gotenberg:3000`). بدونه يفتح زر PDF نسخة قابلة للطباعة/الحفظ PDF من المتصفح. محليًا: `docker run --rm -p 3000:3000 gotenberg/gotenberg:8` ثم `GOTENBERG_URL=http://127.0.0.1:3000`.

### الدفع الإلكتروني – لحظة (Lahza)
بوابة فلسطينية تدعم ILS/JOD/USD (بطاقات، Apple Pay، محافظ محلية). ضع `LAHZA_SECRET_KEY` و`LAHZA_PUBLIC_KEY` من لوحة لحظة، وعيّن Webhook إلى `https://نطاقك/api/webhooks/lahza` (يُتحقق من التوقيع `x-lahza-signature`). يظهر زر "ادفع إلكترونيًا" في رابط العميل، وتُسجّل الدفعة تلقائيًا بعد التحقق من العملية ولا تتكرر.

### واجهة API لتطبيق الجوال
- `POST /api/v1/auth/login` (email, password, device_name) يعيد `token` صالح 90 يومًا؛ أرسله في `Authorization: Bearer <token>`.
- `GET /api/v1/auth/me`، `POST /api/v1/auth/logout`
- للمدير/المحاسب: `GET /api/v1/dashboard`، `GET /api/v1/lookups`، `GET|POST /api/v1/customers`، `GET /api/v1/customers/{id}`، `GET|POST /api/v1/invoices`، `GET /api/v1/invoices/{id}`، `POST /api/v1/invoices/{id}/send|payments|remind`

تطبيق Flutter في `mobile/` يستخدم هذه الواجهة (شاشات الرؤى الذكية والفواتير والعملاء وإنشاء فاتورة):
```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=https://نطاقك
```
على المحاكي Android الافتراضي هو `http://10.0.2.2:8000` (أي `php artisan serve` على جهازك)، ويمكن تغيير عنوان الخادم من شاشة الدخول.

### النشر على خادم سحابي بـ Docker
```bash
cp .env.example .env      # عدّل APP_URL و APP_KEY و DB_USERNAME (ليس root) و DB_PASSWORD والبريد والمفاتيح
docker compose up -d --build
docker compose exec app php artisan key:generate --force   # مرة واحدة إن كان APP_KEY فارغًا
```
يشغّل التطبيق (Nginx + PHP-FPM + المجدول) وMySQL 8.4 وGotenberg 8. التطبيق على المنفذ 10000؛ ضع أمامه وكيل HTTPS (Caddy أو Nginx) لأن بوابة الدفع وتطبيق الجوال يحتاجان HTTPS.

## فحص المشروع
```bash
php artisan route:list
php artisan test
```

تم فحص صياغة ملفات PHP وJSON ضمن حزمة التعديل. يجب تشغيل اختبارات Laravel وتجربة الصفحات على جهازك بعد تثبيت الاعتماديات والاتصال بقاعدة MySQL؛ لا يمكن ضمان عمل البيئة المحلية قبل هذا الاختبار.

## رفع التعديلات إلى GitHub
إذا كان المشروع موجودًا أصلًا على جهازك، انسخ الملفات المعدلة من هذه الحزمة إلى مجلد مشروعك مع الحفاظ على `.env` وبيانات قاعدة البيانات. ثم افتح Terminal داخل مجلد المشروع ونفّذ:

```bash
git status
git add .
git commit -m "Add reports messaging GPS and translations"
git push origin main
```

إذا كان الفرع لديك ليس `main`، تحقق منه باستخدام `git branch --show-current` واستخدم اسم الفرع الصحيح في أمر `git push`.

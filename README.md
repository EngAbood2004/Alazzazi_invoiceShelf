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

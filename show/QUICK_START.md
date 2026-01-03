# 🚀 راهنمای سریع اجرا

## روش 1: PHP Built-in Server (ساده‌ترین روش)

```bash
cd show
php -S localhost:8000
```

سپس در مرورگر به آدرس `http://localhost:8000` بروید.

## روش 2: XAMPP

1. فایل‌های را در `C:\xampp\htdocs\show` کپی کنید
2. XAMPP را اجرا کنید و Apache را Start کنید
3. در مرورگر به `http://localhost/show` بروید

## روش 3: WAMP

1. فایل‌ها را در `C:\wamp64\www\show` کپی کنید
2. WAMP را اجرا کنید
3. در مرورگر به `http://localhost/show` بروید

## ✨ نحوه استفاده

1. **افزودن نمودار**: روی دکمه "افزودن نمودار" کلیک کنید
2. **انتخاب گزینه‌ها**:
   - نوع نمودار: Accuracy یا Loss
   - نوع داده: Train یا Test
   - Model, Validation, Run, Subject (در صورت نیاز), Fold
   - نام منحنی و رنگ
3. **افزودن**: روی "افزودن به نمودار" کلیک کنید
4. **تکرار**: می‌توانید چندین منحنی اضافه کنید

## 🎯 مثال

### مقایسه Train و Test Accuracy:
1. افزودن نمودار → Accuracy → Train → انتخاب‌ها → نام: "Train" → افزودن
2. افزودن نمودار → Accuracy → Test → همان انتخاب‌ها → نام: "Test" → افزودن

### مقایسه چند Subject:
1. افزودن نمودار → Accuracy → Test → Subject 0 → نام: "Subject 0"
2. افزودن نمودار → Accuracy → Test → Subject 1 → نام: "Subject 1"
3. و غیره...

## ⚙️ تنظیم مسیر Results

اگر پوشه `result` پیدا نشد، مسیر را در فایل `api.php` در تابع `findResultsPath()` تغییر دهید.



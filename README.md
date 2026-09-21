<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/Filament-4.0-FBBF24?style=for-the-badge" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white" />
</p>

<h1 align="center">🖥️ SMS Gateway Backend</h1>

<p align="center">
  سيرفر Laravel يدير إرسال رسائل SMS والتحقق من OTP عبر بوابات Android حقيقية.
  <br/>
  Laravel backend that manages SMS delivery and OTP verification through real Android phone gateways.
</p>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Models](#-models)
- [API Reference](#-api-reference)
- [Admin Panel](#-admin-panel-filament)
- [Getting Started](#-getting-started)
- [Security](#-security)
- [Related Repositories](#-related-repositories)

---

## 🔍 Overview

هذا الريبو هو **الجزء الخلفي (Backend)** من نظام SMS Gateway المتكامل. يعمل كمحور مركزي بين:

- **تطبيقات العملاء** → تطلب إرسال OTP وتتحقق من الأكواد
- **بوابات SMS** (أجهزة Android) → تسحب الرسائل المعلقة وترسلها فعلياً عبر شريحة SIM

يوفر السيرفر:
- ✅ REST API لتطبيقات العملاء (Silent Pairing + OTP)
- ✅ REST API لبوابات SMS (Registration + Polling + Heartbeat)
- ✅ لوحة تحكم إدارية (Filament Admin Panel)
- ✅ نظام Queue لمعالجة الرسائل بشكل غير متزامن
- ✅ كشف تلقائي للرسائل العالقة (Stuck Jobs)

---

## 🏗 Architecture

```
┌─────────────────┐         ┌──────────────────────┐         ┌─────────────────┐
│  Client App     │────────▶│   This Backend       │◀────────│ SMS Gateway App │
│  (Any App)      │  API    │   (Laravel 12)       │  API    │ (Android Phone) │
│                 │         │                      │         │                 │
│ • Connect       │         │ • ClientController   │         │ • Register      │
│ • Request OTP   │         │ • GatewayController  │         │ • Poll Jobs     │
│ • Verify OTP    │         │ • OtpService         │         │ • Send via SIM  │
│                 │         │ • SmsService         │         │ • Report Result │
│                 │         │ • GatewayService     │         │ • Heartbeat     │
│                 │         │ • Filament Admin     │         │                 │
└─────────────────┘         └──────────┬───────────┘         └─────────────────┘
                                       │
                                ┌──────┴──────┐
                                │  SQLite DB  │
                                │  + Queue    │
                                └─────────────┘
```

### Flow

1. **Client** يتصل بالسيرفر عبر `/client/connect` ويحصل على `API Key`
2. **Client** يطلب إرسال OTP عبر `/client/otp/request`
3. **Backend** يولّد كود OTP مُشفّر ويُنشئ رسالة SMS بحالة `pending`
4. **Gateway** يسحب الرسائل المعلقة عبر `/gateway/jobs/next` (Atomic Polling)
5. **Gateway** يُرسل SMS فعلياً عبر شريحة SIM في الجهاز
6. **Gateway** يُبلّغ السيرفر بالنتيجة عبر `/gateway/jobs/{id}/result`
7. **Client** يتحقق من الكود عبر `/client/otp/verify`

---

## 🛠 Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 8.2+ | Runtime |
| **Laravel** | 12.x | API Framework |
| **Filament** | 4.0 | Admin Panel (CRUD + Dashboard) |
| **SQLite** | — | Database (default, يمكن تغييره لـ MySQL/PostgreSQL) |
| **Queue (Database)** | — | Async SMS job processing |
| **bcrypt** | — | Hashing for API keys, tokens, OTP codes |

---

## 📁 Project Structure

```
sms_backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ClientController.php          # Silent Pairing + OTP endpoints
│   │   │   └── GatewayController.php         # Gateway registration + job management
│   │   ├── Middleware/
│   │   │   ├── ClientApiKeyMiddleware.php     # X-API-KEY authentication
│   │   │   └── GatewayTokenMiddleware.php     # Bearer Token authentication
│   │   ├── Requests/                         # Form Request validation classes
│   │   └── Resources/                        # API Resources (JSON transformers)
│   ├── Filament/
│   │   ├── Resources/                        # Admin CRUD panels
│   │   │   ├── ClientAppResource.php         # إدارة التطبيقات
│   │   │   ├── SmsGatewayResource.php        # إدارة البوابات
│   │   │   ├── SmsMessageResource.php        # إدارة الرسائل
│   │   │   └── OtpCodeResource.php           # إدارة أكواد OTP
│   │   └── Widgets/                          # Dashboard statistics
│   ├── Jobs/
│   │   └── SendSmsJob.php                    # Queued SMS dispatch job
│   ├── Models/
│   │   ├── ClientApp.php                     # Client apps with hashed API keys
│   │   ├── SmsGateway.php                    # Gateway devices with token auth
│   │   ├── SmsMessage.php                    # SMS queue (pending→processing→sent/failed)
│   │   ├── OtpCode.php                       # OTP codes with bcrypt + expiry
│   │   └── GatewayLog.php                    # Gateway activity logs
│   └── Services/
│       ├── OtpService.php                    # OTP generation & verification logic
│       ├── SmsService.php                    # SMS queuing & dispatch
│       └── GatewayService.php                # Gateway lifecycle management
├── database/
│   └── migrations/                           # Database schema (8 migration files)
├── routes/
│   └── api.php                               # All API route definitions
└── config/
```

---

## 📊 Models

| Model | الوصف | الحقول الرئيسية |
|---|---|---|
| `ClientApp` | التطبيقات المُسجلة | `app_id`, `api_key_hash`, `api_key_prefix`, `status`, `rate_limit_per_minute` |
| `SmsGateway` | أجهزة Android المُسجلة كبوابات | `device_id`, `token_hash`, `status`, `sim_slot`, `last_seen_at` |
| `SmsMessage` | قائمة الرسائل مع تتبع الحالة | `phone`, `message`, `type`, `status`, `attempts`, `locked_at` |
| `OtpCode` | أكواد OTP المُشفّرة | `phone`, `code_hash`, `expires_at`, `verified_at`, `attempts` |
| `GatewayLog` | سجلات نشاط البوابات | `gateway_id`, `action`, `details` |

### SMS Message Status Flow

```
pending → processing → sent ✅
                    → failed ❌ → pending (retry if attempts < max)
```

---

## 📡 API Reference

### Base URL
```
http://your-server/api/v1
```

### Client API

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/client/connect` | ❌ | Silent Pairing — تسجيل تطبيق والحصول على API Key |
| `POST` | `/client/otp/request` | 🔑 `X-API-KEY` | طلب إرسال OTP لرقم هاتف |
| `POST` | `/client/otp/verify` | 🔑 `X-API-KEY` | التحقق من كود OTP |

### Gateway API

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/gateway/register` | ❌ | تسجيل بوابة جديدة والحصول على Token |
| `POST` | `/gateway/authenticate` | 🔑 `Bearer Token` | التحقق من صلاحية التوكن |
| `POST` | `/gateway/heartbeat` | 🔑 `Bearer Token` | إرسال نبضة حياة (battery, signal) |
| `POST` | `/gateway/jobs/next` | 🔑 `Bearer Token` | سحب الرسالة المعلقة التالية (atomic) |
| `POST` | `/gateway/jobs/{id}/result` | 🔑 `Bearer Token` | تحديث نتيجة الإرسال (sent/failed) |

### Example Usage

```bash
# 1. Connect & get API key
curl -X POST http://localhost:8000/api/v1/client/connect \
  -H "Content-Type: application/json" \
  -d '{"app_name": "MyApp", "app_id": "com.example.myapp"}'

# Response: { "status": "success", "data": { "api_key": "sms_xxxxx..." } }

# 2. Send OTP
curl -X POST http://localhost:8000/api/v1/client/otp/request \
  -H "X-API-KEY: sms_xxxxx..." \
  -H "Content-Type: application/json" \
  -d '{"phone_number": "+966500000000"}'

# 3. Verify OTP
curl -X POST http://localhost:8000/api/v1/client/otp/verify \
  -H "X-API-KEY: sms_xxxxx..." \
  -H "Content-Type: application/json" \
  -d '{"phone_number": "+966500000000", "code": "123456"}'
```

---

## 🎛 Admin Panel (Filament)

لوحة تحكم كاملة متاحة على `/admin`:

- 📱 **Client Apps** — إدارة التطبيقات المُسجلة وحالاتها
- 📡 **SMS Gateways** — مراقبة البوابات (الحالة، آخر اتصال، شريحة SIM)
- 💬 **SMS Messages** — تتبع الرسائل وفلترة حسب الحالة
- 🔐 **OTP Codes** — عرض أكواد OTP وحالة التحقق

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm

### Installation

```bash
# Clone the repository
git clone https://github.com/alzoodiali/smsGatewaybackend.git
cd smsGatewaybackend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user for Filament panel
php artisan make:filament-user

# Start development (server + queue + logs + vite)
composer dev
```

> 💡 `composer dev` يشغّل السيرفر + Queue Worker + Log Viewer + Vite في نفس الوقت.

### Access Points

| URL | الوصف |
|---|---|
| `http://localhost:8000` | Application |
| `http://localhost:8000/admin` | Filament Admin Panel |
| `http://localhost:8000/api/v1/*` | REST API |

---

## 🔐 Security

| Feature | Implementation |
|---|---|
| **API Keys** | Hashed (bcrypt) + prefix-based lookup for performance |
| **Gateway Tokens** | Hashed (bcrypt), never stored in plain text |
| **OTP Codes** | Hashed (bcrypt), time-limited expiry |
| **Rate Limiting** | Configurable per-client `rate_limit_per_minute` |
| **Max Attempts** | OTP verification limited to prevent brute force |
| **Stuck Job Detection** | Auto-detect jobs stuck in `processing` state |
| **Heartbeat Timeout** | Gateway auto-marked offline if heartbeat exceeds threshold |
| **Atomic Polling** | Jobs locked atomically to prevent duplicate sends |

---

## 🔗 Related Repositories

| Repository | Description |
|---|---|
| [**sms_gateway_app**](https://github.com/alzoodiali/sms_gateway_app) | تطبيق Flutter/Android — يعمل كبوابة SMS حقيقية تُرسل الرسائل عبر شريحة SIM |

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

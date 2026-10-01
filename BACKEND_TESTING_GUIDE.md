# KharchDaan.Com — Backend Testing & Setup Guide

This package contains the complete **Laravel 12 / PHP 8.4 Backend** for **KharchDaan.Com**, featuring:
- 👑 **Super Admin Master Portal**
- 👤 **Sub-Admin Staff Portal with Granular Permissions Matrix**
- 🌳 **Direct Selling (MLM) 1:3 Physical Placement Matrix Engine**
- 💰 **100% Cashback Settlement Engine & Weekly Payout Automation**
- 📦 **E-Commerce Products, Variations, Orders, Inventory & Invoicing**

---

## 🔑 Login Credentials

| Role | Portal URL | Login ID / Email | Password | Access Level |
| :--- | :--- | :--- | :--- | :--- |
| 👑 **Super Admin** | [http://127.0.0.1:8000/super-admin/dashboard](http://127.0.0.1:8000/super-admin/dashboard) | `admin@example.com` | `password` | **Full Master Authority** (All modules + Sub-Admin passwords directory) |
| 👤 **Sub-Admin** | [http://127.0.0.1:8000/sub-admin/dashboard](http://127.0.0.1:8000/sub-admin/dashboard) | `subadmin@example.com` *(or `SUB-1001`)* | `password` | **Staff Restricted** (Only authorized modules assigned by Super Admin) |
| 🔑 **Unified Login** | [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login) | *(Enter ID/Email)* | *(Enter Password)* | Automatically routes to Super Admin or Sub-Admin portal |

---

## 🚀 Setup & Execution (3 Commands)

1. **Install Dependencies:**
   ```bash
   composer install
   ```

2. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate --force
   ```

3. **Start the Backend Server:**
   ```bash
   php artisan serve --port=8000
   ```

> The backend portal will be accessible at: **http://127.0.0.1:8000**

---

## 🧪 Key Backend Features to Test:

1. **👑 Super Admin Master Dashboard:**
   - **URL:** `http://127.0.0.1:8000/super-admin/dashboard`
   - **Staff & Password Management (`/super-admin/users`):** Create new sub-admins with custom module permissions; view, reveal (👁️), and copy staff plain passwords and IDs.
   - **Direct Selling (MLM) Network:** Member registrations, 1:3 tree visualization, 20-level PV distribution engine.
   - **Weekly Settlements & Payouts:** Calculation engine, audit proof upload, and payout disbursements.
   - **100% Cashback Engine:** Declare company profit distributions and process customer cashback queues.

2. **👤 Sub-Admin Staff Dashboard:**
   - **URL:** `http://127.0.0.1:8000/sub-admin/dashboard`
   - **Strict Permission Guarding:** Sub-Admins cannot access `/super-admin/*` routes. If accessed, they are automatically redirected with a restricted notice.
   - Administrative settings, staff password manager, and database backups are completely hidden from staff.

3. **🛡️ URL Separation Enforcement:**
   - All admin links dynamically resolve to `/super-admin/...` or `/sub-admin/...`.
   - Legacy `/admin/*` routes are intercepted and redirected to the appropriate portal.

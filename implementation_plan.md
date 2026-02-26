# New Pages Implementation Plan

## Completed
- ✅ Moved 17 legacy files to `old-views/`
- ✅ Unified dashboard with tabs (Aggregators | Clients | Transactions)
- ✅ Client Portal

## New Pages to Build

### 1. Sticker / Client ID Card Printing — [sticker.php](file:///c:/xampp/htdocs/pos_p/sticker.php)

User provided the design template. Need to make it **data-driven**:

| Feature | Detail |
|---|---|
| **Select clients** | Aggregator sees their clients; Admin/Area Office can pick any |
| **Batch print** | Select multiple clients → render 4 stickers per A4 page |
| **QR code** | Generate real QR from client_number (links to `client-portal.php?c=AMMBAN-NS-1001`) |
| **Data** | Enterprise name = `business_name`, Unique ID = `client_number` |

**Files:**
- `[MODIFY] sticker.php` — Add client picker UI + data-driven card generation
- `[NEW] assets/js/app/sticker.js` — Client selection + QR generation (using qrcode.js CDN)
- Backend: reuse `handle-dashboard.php?action=clients`

---

### 2. Split Configuration — `split-config.php`

Admin-only page to manage how revenue is split between participants.

| Feature | Detail |
|---|---|
| **List configs** | Show all `split_configs` grouped by `revenue_type` |
| **Edit rules** | Add/edit/delete `split_rules` per config (participant type, %, flat, remainder) |
| **Activate** | Toggle which config is active per revenue type |
| **Preview** | Show a visual breakdown of a sample ₦10,000 payment |

**DB Tables:** `split_configs`, `split_rules`, `revenue_types`, `participant_types`
**Existing repo:** [SplitConfigRepository](file:///c:/xampp/htdocs/pos_p/src/Repositories/SplitConfigRepository.php#4-130) (needs CRUD methods added)

**Files:**
- `[NEW] split-config.php` — View
- `[NEW] assets/js/app/split-config.js`
- `[NEW] handles/handle-split-config.php` — CRUD for configs + rules

---

### 3. Wallets & Ledger — `wallets.php`

View participant balances and ledger history. Scoped via [DataScope](file:///c:/xampp/htdocs/pos_p/src/Utils/DataScope.php#14-124).

| Feature | Detail |
|---|---|
| **Balance cards** | Each participant's balance (credits − debits from ledger) |
| **Ledger table** | Transaction-level breakdown: credit, debit, type, reference, date |
| **Scope** | Admin sees all participants; Area Office sees their aggregators; Aggregator sees own |

**DB Tables:** `ledger`, `participants`
**Existing repo:** `SplitConfigRepository.getParticipantBalance()`, [getLedgerByParticipant()](file:///c:/xampp/htdocs/pos_p/src/Repositories/SplitConfigRepository.php#58-80)

**Files:**
- `[NEW] wallets.php` — View
- `[NEW] assets/js/app/wallets.js`
- `[NEW] handles/handle-wallets.php` — List balances + ledger entries (auto-scoped via DataScope)

---

## Active Pages After Build

| Page | Role |
|---|---|
| [dashboard.php](file:///c:/xampp/htdocs/pos_p/dashboard.php) | All roles (unified, scoped) |
| [sticker.php](file:///c:/xampp/htdocs/pos_p/sticker.php) | Aggregator, Area Office, Admin |
| `split-config.php` | Admin only |
| `wallets.php` | All roles (scoped) |
| [client-portal.php](file:///c:/xampp/htdocs/pos_p/client-portal.php) | Public (POS agents) |
| [client-list.php](file:///c:/xampp/htdocs/pos_p/client-list.php) | Admin |
| [client-detail.php](file:///c:/xampp/htdocs/pos_p/client-detail.php) | Admin |
| `user-list/detail.php` | Admin |
| [role-list.php](file:///c:/xampp/htdocs/pos_p/role-list.php) / [permission-list.php](file:///c:/xampp/htdocs/pos_p/permission-list.php) | Admin |
| [audit-logs.php](file:///c:/xampp/htdocs/pos_p/audit-logs.php) / [finance-audit.php](file:///c:/xampp/htdocs/pos_p/finance-audit.php) | Admin |
| [profile.php](file:///c:/xampp/htdocs/pos_p/profile.php) | All |
| [membership-payment.php](file:///c:/xampp/htdocs/pos_p/membership-payment.php) | Payment flow |
| Auth pages | Public |

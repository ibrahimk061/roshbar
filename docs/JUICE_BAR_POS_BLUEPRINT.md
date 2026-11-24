# JUICE BAR POS & BUSINESS MANAGEMENT SYSTEM

## Complete Claude Code Development Blueprint

**Document type:** Master Project Specification + Claude Code Prompt
Pack\
**Project:** Juice Bar\
**Order Prefix:** RB\
**Business Model:** Single Branch\
**Country:** Bangladesh\
**Currency:** BDT (৳)\
**Timezone:** Asia/Dhaka\
**Target POS Hardware:** SUNMI V2s\
**Development Strategy:** Web POS first → Native Android POS second

------------------------------------------------------------------------

# 1. Project Overview

Build a professional, reliable POS and Business Management System for a
single-branch juice bar. The system must manage sales, orders, products,
recipes, ingredients, inventory, purchases, suppliers, expenses, cash
sessions, staff, reports, and offline operations.

The Web POS is the primary business management system. A separate Native
Android application will run on the SUNMI V2s terminal and use the same
Laravel backend and business rules.

## 1.1 Confirmed Business Decisions

  Setting            Value
  ------------------ -----------------------------------------
  Business name      Juice Bar
  Order prefix       RB
  Branch model       Single branch
  Offline support    Web and Android
  Currency           BDT
  Timezone           Asia/Dhaka
  Date display       DD-MM-YYYY
  Android language   Java
  Android target     SUNMI V2s
  Inventory model    Recipe-based ingredient inventory
  Costing            Ingredient and recipe costing
  Cash management    Daily opening and closing
  Staff management   Role-based access and activity tracking

## 1.2 Main Objectives

1.  Make billing fast and easy for counter staff.
2.  Automatically deduct ingredients when a sale is accepted.
3.  Calculate recipe cost and product gross contribution.
4.  Track purchases, stock, wastage, and supplier dues.
5.  Track expenses and cash movement.
6.  Reconcile expected cash against actual cash at closing.
7.  Provide reliable daily, weekly, and monthly reports.
8.  Support offline order creation on Web PWA and Android.
9.  Print receipts using the SUNMI V2s built-in thermal printer.
10. Keep a complete audit trail for important business actions.

------------------------------------------------------------------------

# 2. Required Technology Stack

Use compatible, stable versions available at implementation time. Before
installing dependencies, inspect the environment and document selected
versions.

## 2.1 Web and Backend

-   Backend: Laravel
-   Language: PHP
-   Database: MySQL
-   Web UI: Laravel Blade
-   CSS/UI: Bootstrap with a consistent custom design system
-   API: Versioned REST API (`/api/v1`)
-   Authentication: Laravel Sanctum for API tokens; secure web session
    authentication
-   Authorization: Role-based permissions and Laravel Policies
-   Queue: Laravel Queue where appropriate
-   Scheduler: Laravel Scheduler
-   Testing: PHPUnit or Pest, selected consistently
-   Export: PDF and Excel libraries selected after compatibility review

## 2.2 Web Offline

-   PWA manifest
-   Service Worker
-   IndexedDB
-   Local operation queue
-   Retry and conflict handling
-   Manual sync fallback
-   Do not assume Background Sync is supported by every browser

## 2.3 Android

-   Native Android application
-   Java
-   Android Studio
-   Retrofit and OkHttp
-   Room / SQLite
-   WorkManager
-   SUNMI official printer SDK or supported built-in printer service
-   Device-specific compatibility verification before printer
    implementation

## 2.4 Architecture Principles

-   API-first backend.
-   Web and Android must use the same server-side business rules.
-   Keep controllers thin.
-   Put business logic in Services or Actions.
-   Use Form Requests for validation.
-   Use Policies and permissions for authorization.
-   Use database transactions for critical workflows.
-   Use decimal database types for money.
-   Never use floating-point arithmetic for financial calculations.
-   Maintain stock and financial ledgers.
-   Keep historical snapshots on order lines.
-   Make sync endpoints idempotent.
-   Use UUIDs for client-created offline records.
-   Design for a single branch while avoiding unnecessary assumptions
    that prevent future expansion.
-   Do not introduce microservices for this project unless a
    demonstrated requirement justifies them.

------------------------------------------------------------------------

# 3. Product Scope and Modules

## 3.1 Main Navigation

1.  Dashboard
2.  POS / New Sale
3.  Orders
4.  Products & Categories
5.  Product Variations & Add-ons
6.  Ingredients
7.  Recipes
8.  Inventory / Stock
9.  Purchases
10. Suppliers
11. Wastage
12. Expenses
13. Cash Management
14. Customers
15. Staff & Roles
16. Reports
17. Settings
18. Audit Logs
19. Device & Sync Management

Menu visibility and access must depend on user permissions.

## 3.2 Dashboard

Show date-filtered business KPIs:

-   Gross sales
-   Discounts
-   Refunds
-   Net sales
-   Number of orders
-   Average order value
-   Recipe COGS
-   Gross profit
-   Operating expenses
-   Estimated net profit
-   Cash collection
-   Digital payment collection
-   Pending orders
-   Low-stock ingredients
-   Wastage value

Charts and widgets:

-   Daily sales trend
-   Sales by payment method
-   Top-selling products
-   Category-wise sales
-   Hourly sales
-   Expense category breakdown
-   Ingredient consumption
-   Low-stock alerts
-   Recent orders
-   Recent expenses

Date filters: Today, Yesterday, Last 7 Days, This Month, Last Month, and
Custom Range.

Do not calculate dashboard values using inconsistent formulas. Reuse
tested reporting services.

## 3.3 Product and Menu Management

Product fields:

-   Product name
-   SKU / internal code
-   Category
-   Description
-   Image
-   Active / inactive
-   Available / unavailable
-   Display order
-   Tax configuration where applicable

Product variations:

-   Regular
-   Medium
-   Large
-   Custom variation names

Each variation can have its own selling price and recipe.

Add-ons:

-   Extra milk
-   Honey
-   Ice cream
-   Extra fruit
-   Other configurable additions

Each add-on must support price and optional ingredient consumption.

Customization:

-   Sugar: 0%, 25%, 50%, 75%, 100%
-   Ice: No Ice, Less Ice, Regular
-   Special instructions

Product examples for seed data only:

-   Mango Juice
-   Orange Juice
-   Lemon Juice
-   Watermelon Juice
-   Mixed Fruit Juice
-   Smoothie
-   Milkshake
-   Lemonade
-   Coffee
-   Special Drinks

Do not assume these are the final menu. The owner must be able to
create, edit, deactivate, and reorder menu items.

## 3.4 Recipe Management

A recipe belongs to a product variation. A product may have multiple
variations, each with its own recipe.

Recipe features:

-   Recipe name and version
-   Product and variation
-   Ingredient list
-   Ingredient quantity
-   Measurement unit
-   Preparation notes
-   Effective date
-   Active/inactive status
-   Add-on ingredient rules
-   Recipe cost snapshot

Example recipe (illustrative only):

  Ingredient     Quantity
  ------------ ----------
  Mango             150 g
  Sugar              15 g
  Milk              50 ml
  Cup               1 pcs
  Straw             1 pcs

The application must calculate the recipe cost from the selected
ingredient costing method.

Recipe versioning is required. A historical order must retain the recipe
and cost snapshot used at the time of sale. Editing a recipe later must
not rewrite old orders or old cost reports.

## 3.5 Ingredient and Unit Management

Ingredient fields:

-   Name
-   SKU
-   Ingredient category
-   Base unit
-   Purchase unit
-   Conversion factor
-   Minimum stock
-   Reorder level
-   Storage notes
-   Active/inactive

Support suitable units, including:

-   kg and g
-   litre and ml
-   pcs
-   bottle
-   packet
-   box

Use explicit unit conversion rules. Do not silently convert incompatible
units.

## 3.6 Inventory Management

Inventory types:

-   Raw ingredients
-   Packaging materials
-   Other stock-controlled consumables

Inventory operations:

-   Opening stock
-   Purchase receipt
-   Recipe-based consumption
-   Add-on consumption
-   Wastage / spoilage
-   Supplier return
-   Manual adjustment
-   Physical stock count
-   Reconciliation

Requirements:

-   Stock movement ledger
-   Batch tracking where needed
-   Purchase date
-   Expiry date where relevant
-   FIFO consumption for batch-tracked perishable stock
-   Low-stock alert
-   Out-of-stock alert
-   Stock valuation report
-   Negative stock prevention by default
-   Authorized override with reason and audit log

Do not directly edit a stock balance without creating a corresponding
stock movement. A cached balance may be maintained for performance, but
it must reconcile to the ledger.

## 3.7 Purchase and Supplier Management

Supplier fields:

-   Supplier name
-   Contact person
-   Phone
-   Address
-   Notes
-   Active/inactive

Purchase header:

-   Purchase reference
-   Supplier
-   Purchase date
-   Invoice number
-   Items
-   Subtotal
-   Discount
-   Other charges
-   Grand total
-   Paid amount
-   Due amount
-   Payment method
-   Notes
-   Created by

Purchase item:

-   Ingredient
-   Quantity
-   Purchase unit
-   Conversion factor
-   Unit cost
-   Line total
-   Batch and expiry data where relevant

Support supplier payment history, due tracking, purchase return, and
supplier ledger.

A stock purchase is inventory acquisition. It must not be deducted again
as an operating expense in the profit and loss report.

## 3.8 POS Billing

The POS must be optimized for fast touchscreen operation.

Layout:

-   Category selector
-   Product search
-   Product grid
-   Product image
-   Variation selection
-   Add-on selection
-   Sugar and ice selection
-   Cart
-   Quantity controls
-   Item notes
-   Discount
-   Tax
-   Payment
-   Checkout

Order types:

-   Takeaway
-   Dine-in
-   Delivery (configurable; may be disabled initially)

Payment methods:

-   Cash
-   bKash
-   Nagad
-   Card
-   Bank
-   Other configurable method

Support:

-   Split payments
-   Partial payments
-   Due tracking if enabled by owner
-   Hold / resume order
-   Cancel order with permission and reason
-   Refund with permission and reason
-   Reprint receipt
-   Search by order number
-   Customer attachment (optional)

All prices, discounts, tax, payment totals, and stock checks must be
recalculated and validated on the server.

## 3.9 Order Management

Order statuses:

-   New
-   Preparing
-   Ready
-   Completed
-   Cancelled
-   Refunded (or refund state tracked separately where partial refunds
    are supported)

Keep order status history with user, timestamp, previous status, new
status, and note.

Order details must preserve:

-   Product name snapshot
-   Variation snapshot
-   Add-on snapshot
-   Quantity
-   Unit price
-   Discount
-   Tax
-   Line total
-   Recipe cost snapshot
-   Customization
-   Notes

Support order list filters by date, order number, status, payment
method, staff, and order type.

## 3.10 Order Number Generator

Required format:

`RB-DD-MM-YY-A0001`

Examples:

-   `RB-24-11-26-A0001`
-   `RB-24-11-26-A0002`
-   `RB-24-11-26-A0100`
-   `RB-24-11-26-B0001`
-   `RB-24-11-26-B0002`

Rules:

1.  Prefix is configurable but defaults to `RB`.
2.  Date is the business-local date in `DD-MM-YY`.
3.  Sequence resets for each new business date.
4.  A0001 through A0100 represent the first 100 orders.
5.  B0001 through B0100 represent the next 100.
6.  Continue through Z, then AA, AB, AC, and so on.
7.  Cancelled orders never release their number.
8.  Order numbers must be unique.
9.  Generate the official number on the server inside a database
    transaction.
10. Use row locking or another concurrency-safe atomic mechanism.
11. Do not use a browser counter or an in-memory PHP counter.
12. Offline orders use a local UUID and receive the official RB number
    only after server acceptance.
13. Document business-date boundary behavior and test it around midnight
    in Asia/Dhaka.

## 3.11 Payment Management

Store payments separately from orders.

Payment fields:

-   Order
-   Payment method
-   Amount
-   Reference / transaction number
-   Payment status
-   Received by
-   Timestamp
-   Device / source
-   Refund linkage where applicable

Support multiple payment records per order. Payment total must reconcile
with the order's paid amount.

Do not mark a third-party digital payment as successful while offline.
An offline entry may be recorded as pending/manual confirmation
according to business settings.

## 3.12 Expense Management

Expense categories should be configurable.

Examples:

-   Rent
-   Electricity
-   Water
-   Salary
-   Transport
-   Maintenance
-   Marketing
-   Cleaning
-   Other operating expense

Expense fields:

-   Expense date
-   Category
-   Amount
-   Payment method
-   Paid from cash session or account
-   Description
-   Receipt attachment
-   Created by
-   Approval status if configured

Inventory purchases must remain separate from operating expenses to
prevent double counting.

## 3.13 Cash Management and Daily Closing

Cash session workflow:

1.  Cashier opens a session.
2.  Enters opening cash.
3.  System records cash movements during the session.
4.  Cashier counts actual closing cash.
5.  System calculates expected cash.
6.  System calculates shortage or excess.
7.  Manager/Owner reviews and approves if required.
8.  Closed session becomes immutable for ordinary staff.

Formula:

`Expected Cash = Opening Cash + Cash In - Cash Out`

Cash In and Cash Out must be derived from clearly classified cash ledger
entries. Cash sales, cash receipts, cash expenses, refunds, withdrawals,
and adjustments must not be double-counted.

`Difference = Actual Cash - Expected Cash`

Store closing note, counted amount, expected amount, difference, closed
by, approved by, and timestamps.

## 3.14 Staff and Access Control

Roles:

-   Owner
-   Admin
-   Manager
-   Cashier
-   Inventory Staff

Staff features:

-   Staff profile
-   Login account
-   PIN login where suitable
-   Role and permissions
-   Active/inactive status
-   Shift
-   Attendance (optional)
-   Sales performance
-   Cash session history
-   Activity history
-   Login history

Sensitive actions require explicit permissions:

-   Discount override
-   Price override
-   Cancel order
-   Refund
-   Stock adjustment
-   Negative stock override
-   Expense approval
-   Cash closing adjustment
-   Report access
-   User and permission management

## 3.15 Customer Management

Optional customer profile:

-   Name
-   Phone
-   Notes
-   Order history
-   Total purchase
-   Last order date

Customer details should not be mandatory for every walk-in order.

Loyalty points and QR ordering may be future enhancements, not blockers
for the first release.

------------------------------------------------------------------------

# 4. Reports and Financial Definitions

## 4.1 Required Reports

1.  Daily sales
2.  Weekly sales
3.  Monthly sales
4.  Yearly sales
5.  Custom date sales
6.  Product-wise sales
7.  Category-wise sales
8.  Payment method report
9.  Discount report
10. Refund report
11. Cancelled order report
12. Recipe COGS report
13. Gross profit report
14. Profit and loss report
15. Expense report
16. Purchase report
17. Supplier due report
18. Ingredient stock report
19. Stock valuation
20. Ingredient consumption
21. Wastage report
22. Staff-wise sales
23. Cash session report
24. Cash shortage/excess report

Export to PDF and Excel where appropriate.

## 4.2 Financial Formulas

Use documented, tested formulas and show report definitions in the
UI/help documentation.

`Net Sales = Gross Sales - Discounts - Refunds`

`COGS = Cost of ingredients and packaging consumed by sold items`

`Gross Profit = Net Sales - COGS`

`Net Profit = Gross Profit - Operating Expenses`

Important accounting rules:

-   Do not deduct purchase payments as operating expenses if the
    purchased items are inventory.
-   Use recipe cost snapshots for historical sales.
-   Define how tax is treated and displayed; do not silently include or
    exclude it.
-   Keep discounts, refunds, taxes, and payment fees separately
    identifiable.
-   Unpaid supplier dues are liabilities, not sales expenses.
-   Cash flow and profit are different reports.
-   Clearly label estimated profit if some costs are not yet entered.

------------------------------------------------------------------------

# 5. Database Design

Claude Code must produce and maintain a detailed ERD and schema document
before implementing all migrations.

## 5.1 Proposed Core Tables

### Identity and Configuration

-   users
-   roles
-   permissions
-   role_permissions
-   model_has_roles / permission pivot tables if using a supported RBAC
    package
-   staff_profiles
-   settings
-   devices
-   audit_logs

### Product and Recipe

-   categories
-   products
-   product_variants
-   addons
-   product_addons
-   ingredients
-   units
-   unit_conversions
-   recipes
-   recipe_items
-   recipe_versions

### Inventory and Purchasing

-   suppliers
-   purchases
-   purchase_items
-   supplier_payments
-   stock_batches
-   stock_movements
-   stock_balances (optional cached balance)
-   wastage_entries
-   stock_adjustments
-   stock_counts
-   stock_count_items

### Sales and Payments

-   customers
-   orders
-   order_items
-   order_item_addons
-   order_status_histories
-   order_sequences
-   order_payments
-   refunds
-   refund_items

### Expenses and Cash

-   expense_categories
-   expenses
-   cash_sessions
-   cash_movements
-   cash_adjustments

### Offline Sync

-   sync_devices
-   sync_operations
-   sync_cursors or change logs
-   idempotency_records

Add or remove tables only after documenting the reason. Avoid creating
duplicate concepts under different names.

## 5.2 Data Rules

-   Use `DECIMAL(12,2)` or a documented suitable precision for money.
-   Use suitable decimal precision for ingredient quantities and unit
    costs.
-   Use foreign keys and indexes.
-   Use soft deletes only where they do not undermine financial or
    inventory history.
-   Prefer inactive/archive flags for products and ingredients
    referenced by historical transactions.
-   Store timestamps consistently in UTC; calculate business dates in
    `Asia/Dhaka`.
-   Store order, product, variation, and recipe snapshots.
-   Keep ledgers append-only; corrections should use reversal or
    adjustment entries.
-   Use unique constraints for order numbers, UUIDs, device operation
    IDs, and idempotency keys.
-   Ensure all transactional workflows are safe under concurrent
    requests.

------------------------------------------------------------------------

# 6. System Architecture

## 6.1 High-Level Flow

``` text
WEB BROWSER / PWA
        |
        v
LARAVEL WEB UI
        |
        v
LARAVEL APPLICATION SERVICES
        |
        +--------------------+
        |                    |
        v                    v
 REST API              MYSQL DATABASE
        ^
        |
        v
SUNMI ANDROID APP
        |
        +--> Room / SQLite
        |
        +--> SUNMI Printer SDK
        |
        +--> WorkManager Sync
```

## 6.2 Backend Layers

Suggested responsibilities:

-   Controllers: HTTP request/response only.
-   Form Requests: validation.
-   Policies: authorization.
-   Services / Actions: business workflows.
-   Models: relationships and simple model behavior.
-   Repositories: use only if they solve a real complexity; do not add
    abstraction without value.
-   Jobs: background tasks.
-   Events: meaningful domain events.
-   Listeners: side effects.
-   Resources: consistent API response serialization.

Suggested service classes:

-   OrderService
-   OrderNumberService
-   PaymentService
-   RecipeCostingService
-   InventoryService
-   StockLedgerService
-   PurchaseService
-   ExpenseService
-   CashSessionService
-   RefundService
-   ReportService
-   SyncService
-   AuditLogService

## 6.3 Critical Order Transaction

When a server receives an order:

1.  Authenticate user and registered device.
2.  Validate request and idempotency key.
3.  Check whether the local UUID has already been accepted.
4.  Validate current products, variations, add-ons, and prices.
5.  Validate order/payment data.
6.  Validate stock policy.
7.  Lock relevant stock balances/batches.
8.  Generate official RB order number.
9.  Create order and immutable item snapshots.
10. Record payment transactions.
11. Create recipe-based stock movements.
12. Create cash movements where applicable.
13. Record status history.
14. Record audit event.
15. Store idempotency result.
16. Commit transaction.
17. Return server order ID, official order number, and sync
    confirmation.

If any critical operation fails, rollback the transaction. A retry must
return the original result rather than creating a duplicate order.

------------------------------------------------------------------------

# 7. Offline Architecture

Offline support must be implemented separately for Web and Android,
using a common server sync contract.

## 7.1 Web PWA Offline

Technology:

-   Service Worker
-   IndexedDB
-   Local operation queue
-   Cached application shell
-   Cached products, categories, variations, add-ons, and settings
-   Sync status display
-   Retry mechanism
-   Manual sync fallback

Offline operations may include:

-   Create order
-   Record cash payment
-   Record expense
-   Record permitted cash movement

Only cache the data required for POS operation. Protect sensitive
information and do not cache privileged reports unnecessarily.

## 7.2 Android Offline

Technology:

-   Room / SQLite
-   Retrofit
-   WorkManager
-   Local order queue
-   Local sync operation queue
-   Cached product catalogue
-   Device registration

Store at least:

-   local_uuid
-   device_id
-   local_created_at
-   operation_type
-   payload
-   sync_status
-   retry_count
-   last_error
-   server_record_id
-   server_order_number

## 7.3 Sync Protocol

States:

-   Pending
-   Syncing
-   Synced
-   Failed
-   Conflict

Requirements:

-   UUID for every client-created operation.
-   Idempotent server endpoints.
-   Retry with exponential backoff.
-   Preserve local timestamps and device identity.
-   Do not silently discard unsynced data.
-   Provide manual retry and error visibility.
-   Server is the source of truth for official order numbers.
-   Stock must be revalidated on the server.
-   A failed operation must remain available for review.
-   Syncing the same operation repeatedly must not create duplicate
    orders, payments, or stock movements.

## 7.4 Offline Stock Warning

Offline stock is only a last-synchronized snapshot. If multiple devices
are operating offline, they cannot guarantee a shared real-time stock
balance. Show this limitation clearly and revalidate stock on the server
during sync.

## 7.5 Offline Printing

Android can print a locally saved order using the supported SUNMI
printer integration. The printed receipt should clearly distinguish:

-   Local pending order reference
-   Official server order number after sync

Do not print a fake official RB order number before the server assigns
it.

Web browser printing is separate and must not be assumed to control the
SUNMI built-in printer.

------------------------------------------------------------------------

# 8. API Blueprint

Base URL:

`https://YOUR-DOMAIN/api/v1`

Proposed endpoints (adjust based on the final resource design):

  Method   Endpoint                    Purpose
  -------- --------------------------- ---------------------------
  POST     /auth/login                 Login
  POST     /auth/logout                Logout
  GET      /me                         Current user
  POST     /devices/register           Register device
  GET      /dashboard                  Dashboard summary
  GET      /categories                 Categories
  GET      /products                   Product catalogue
  GET      /ingredients                Ingredient data
  GET      /recipes                    Recipes
  POST     /orders                     Create order
  GET      /orders                     Order list
  GET      /orders/{id}                Order detail
  PATCH    /orders/{id}/status         Change status
  POST     /orders/{id}/refunds        Create refund
  GET      /cash-sessions/current      Current session
  POST     /cash-sessions/open         Open cash session
  POST     /cash-sessions/{id}/close   Close cash session
  POST     /expenses                   Create expense
  GET      /reports/sales              Sales report
  GET      /reports/profit-loss        Profit and loss
  GET      /sync/bootstrap             Initial offline data
  GET      /sync/changes               Incremental updates
  POST     /sync/operations            Submit offline operations
  GET      /sync/status                Device sync status

API requirements:

-   Sanctum token authentication.
-   Device registration and revocation.
-   Role and permission checks.
-   Rate limiting.
-   Request validation.
-   Consistent JSON response format.
-   Pagination.
-   Idempotency key support.
-   API versioning.
-   Error code documentation.
-   Automated feature tests.

------------------------------------------------------------------------

# 9. SUNMI V2s Android Application Blueprint

Build the Android application only after the Web backend and API
contracts are stable.

## 9.1 Target Device

Target: SUNMI V2s.

Do not assume all V2s hardware variants use the same Android version,
firmware, printer service, or SDK. First inspect the actual physical
device:

1.  Model number.
2.  Android version.
3.  Firmware / SUNMI OS version.
4.  Screen resolution and density.
5.  Printer service availability.
6.  Printer SDK compatibility.
7.  USB debugging and development access.
8.  Network behavior.

Use the official SUNMI documentation and SDK compatible with the actual
hardware/firmware. Do not invent SDK class names or use unverified
sample code as production implementation.

## 9.2 Android Screens

1.  Splash / initialization
2.  Staff login
3.  PIN login if enabled
4.  Device registration
5.  Home dashboard
6.  New order / POS
7.  Product categories
8.  Product customization
9.  Cart
10. Checkout
11. Receipt preview
12. Order history
13. Order detail
14. Pending sync
15. Cash session
16. Quick expense
17. Printer test
18. Sync status
19. Settings
20. Logout

## 9.3 Bottom Navigation

-   Home
-   Orders
-   Cash
-   More

Keep the UI optimized for a 5.5-inch portrait touchscreen. Use large
touch targets, readable text, minimal navigation, and fast checkout.

## 9.4 Android Components

Suggested packages:

``` text
com.juicebar.pos
├── activities
├── fragments
├── adapters
├── models
├── api
├── database
├── dao
├── repositories
├── sync
├── printer
├── auth
├── permissions
├── utils
└── workers
```

Use a maintainable architecture and avoid putting all logic in
Activities.

## 9.5 SUNMI Printer

Create a dedicated PrinterManager abstraction.

Responsibilities:

-   Initialize supported printer service.
-   Check printer availability.
-   Print receipt header.
-   Print order number.
-   Print items and totals.
-   Print discounts and payment method.
-   Print footer.
-   Print QR code if supported.
-   Reprint receipt.
-   Handle printer errors and paper issues.

Requirements:

-   58mm paper layout where supported by the actual printer.
-   Test character encoding for English and Bangla.
-   Do not assume Bangla glyph printing is supported natively; test
    font/raster printing if required.
-   Test long product names and wrapped lines.
-   Test receipt cut/feed behavior supported by the device.
-   Never report successful printing unless the SDK/service confirms
    success.

------------------------------------------------------------------------

# 10. UI/UX Design System

Brand direction: Fresh, natural, modern, clean, professional.

Suggested palette:

  Token           Color
  --------------- ---------
  Primary Green   #16834A
  Fresh Lime      #A7D948
  Orange          #FF9F43
  Background      #F7F9F6
  Main Text       #222B26
  White           #FFFFFF

Use these as initial design tokens; allow future brand changes through
centralized CSS variables/theme configuration.

Design requirements:

-   Responsive web admin.
-   Touch-friendly POS.
-   Compact SUNMI interface.
-   Clear typography.
-   Consistent buttons, forms, tables, badges, and modals.
-   Readable financial totals.
-   Empty states and loading states.
-   Validation feedback.
-   Error messages in plain language.
-   Avoid unnecessary animation.
-   Confirm destructive actions.

------------------------------------------------------------------------

# 11. Suggested Repository Structure

Keep Web and Android as separate applications. A monorepo may be used
for shared documentation.

``` text
juice-bar/
├── README.md
├── CLAUDE.md
├── PROJECT_PLAN.md
├── ARCHITECTURE.md
├── DATABASE_DESIGN.md
├── API_DESIGN.md
├── API_SYNC_PROTOCOL.md
├── DEVELOPMENT_ROADMAP.md
├── DEVELOPMENT_LOG.md
├── TEST_PLAN.md
├── SECURITY.md
├── DEPLOYMENT.md
├── USER_MANUAL.md
│
├── juice-bar-web/
│   ├── app/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Actions/
│   │   ├── Policies/
│   │   ├── Jobs/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   └── Http/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       └── Resources/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── resources/
│   │   ├── views/
│   │   ├── css/
│   │   └── js/
│   ├── routes/
│   ├── tests/
│   └── public/
│
├── juice-bar-android/
│   ├── app/
│   │   └── src/main/
│   │       ├── java/com/juicebar/pos/
│   │       │   ├── activities/
│   │       │   ├── fragments/
│   │       │   ├── adapters/
│   │       │   ├── models/
│   │       │   ├── api/
│   │       │   ├── database/
│   │       │   ├── dao/
│   │       │   ├── repositories/
│   │       │   ├── sync/
│   │       │   ├── printer/
│   │       │   ├── auth/
│   │       │   ├── workers/
│   │       │   └── utils/
│   │       └── res/
│   └── build.gradle
│
└── docs/
    ├── diagrams/
    ├── api-examples/
    ├── screenshots/
    └── user-guides/
```

Claude Code may adjust the exact folder structure to match the selected
Laravel and Android versions, but it must document any significant
deviation.

------------------------------------------------------------------------

# 12. Claude Code Development Workflow

## 12.1 Mandatory Rules

Claude Code must:

1.  Inspect the repository before modifying files.
2.  Read `CLAUDE.md` and all relevant project documents.
3.  Identify whether the project is new or existing.
4.  Never overwrite existing code blindly.
5.  Present a plan before a major implementation.
6.  Implement only the approved phase.
7.  Run migrations and automated tests.
8.  Report tests that failed or were not run.
9.  Keep documentation updated.
10. Never invent successful test results.
11. Never leave fake production functionality.
12. Never hardcode credentials, passwords, tokens, or production
    secrets.
13. Never use real customer data in demo seeders.
14. Never delete user data or production data without explicit approval.
15. Ask before destructive database operations.
16. Keep a clear list of known limitations.

## 12.2 Phase Sequence

  -----------------------------------------------------------------------
  Phase                   Implementation          Approval Gate
  ----------------------- ----------------------- -----------------------
  0                       Repository inspection   Approve architecture
                          and documentation       

  1                       Laravel setup, auth,    Tests pass
                          roles, schema           
                          foundation              

  2                       Products, ingredients,  Costing and stock tests
                          recipes, inventory      pass

  3                       POS, orders, order      Billing tests pass
                          numbers, payments       

  4                       Expenses, cash          Cash and permission
                          sessions, staff         tests pass

  5                       Dashboard and reports   Financial
                                                  reconciliation passes

  6                       Web PWA offline and     Offline/retry tests
                          sync                    pass

  7                       API finalization and    API tests pass
                          documentation           

  8                       SUNMI Android app and   Physical device tests
                          printer                 pass

  9                       Security, deployment,   Production approval
                          backup, final audit     
  -----------------------------------------------------------------------

Do not skip approval gates.

------------------------------------------------------------------------

# 13. Claude Code Prompt Pack

Use the prompts below one at a time. Do not paste every phase prompt
into one request and ask Claude to implement everything simultaneously.

## PROMPT 0 --- Master Project Setup

``` text
You are the lead software architect and senior engineering
team for the JUICE BAR POS & BUSINESS MANAGEMENT SYSTEM.

Read the complete project specification in the root
JUICE_BAR_POS_BLUEPRINT.md file.

Business configuration:
- Name: Juice Bar
- Prefix: RB
- Single branch
- Bangladesh
- BDT currency
- Asia/Dhaka timezone
- Web POS first
- Native Android Java second
- Target hardware: SUNMI V2s
- Offline support: Web PWA and Android
- Recipe-based inventory and ingredient costing
- Daily cash opening and closing
- Staff and role management

Your first task is NOT to implement the whole system.

STEP 1:
Inspect the repository, runtime, PHP, Composer, Node,
database availability, Android tooling if available,
and existing project files.

STEP 2:
Identify whether this is a new project or an existing
application. Do not overwrite existing work.

STEP 3:
Create or update:
- CLAUDE.md
- README.md
- PROJECT_PLAN.md
- ARCHITECTURE.md
- DATABASE_DESIGN.md
- API_DESIGN.md
- API_SYNC_PROTOCOL.md
- DEVELOPMENT_ROADMAP.md
- DEVELOPMENT_LOG.md
- TEST_PLAN.md
- SECURITY.md
- DEPLOYMENT.md

STEP 4:
Propose the Laravel and Android technology versions
based on actual compatibility.

STEP 5:
Prepare the database ERD and business workflow.

STEP 6:
Identify unresolved decisions and risks.

Rules:
- Follow the attached project blueprint.
- Do not silently change business requirements.
- Do not create fake features.
- Do not hardcode secrets.
- Use decimal money types.
- Use transactions for critical operations.
- Keep stock and financial ledgers auditable.
- Use idempotent offline synchronization.
- Use server-assigned RB order numbers.
- Write automated tests for critical business logic.
- Keep web and Android business rules consistent.

Present the proposed architecture, folder structure,
database plan, implementation sequence and risks.

WAIT for approval before implementing Phase 1.
```

## PROMPT 1 --- Laravel Foundation

``` text
Implement Phase 1 only.

Read CLAUDE.md, PROJECT_PLAN.md, ARCHITECTURE.md,
DATABASE_DESIGN.md and JUICE_BAR_POS_BLUEPRINT.md.

Tasks:
1. Set up compatible Laravel application.
2. Configure MySQL.
3. Configure Asia/Dhaka timezone.
4. Configure BDT currency and date display.
5. Install and configure authentication.
6. Configure Laravel Sanctum.
7. Implement roles and permissions.
8. Implement users and staff profile foundation.
9. Implement settings.
10. Implement device registry foundation.
11. Implement audit log foundation.
12. Create approved core migrations.
13. Add relationships, indexes and foreign keys.
14. Create factories and safe development seeders.
15. Create automated tests.

Requirements:
- Use DECIMAL for money.
- Use UUID fields where required.
- Never store passwords in plain text.
- Never hardcode production credentials.
- Use policies and permission checks.
- Avoid unnecessary dependencies.
- Do not implement POS, recipe, or inventory workflows yet.

Run migrations and tests in the local development
environment. Report actual results.

Update documentation and DEVELOPMENT_LOG.md.
Stop after Phase 1.
```

## PROMPT 2 --- Product, Recipe and Inventory

``` text
Implement Phase 2 only.

Read the approved schema and business requirements.

Build:
- Categories
- Products
- Product variations
- Add-ons
- Ingredients
- Units
- Unit conversions
- Recipes
- Recipe items
- Recipe versions
- Suppliers
- Purchases
- Purchase items
- Stock batches
- Stock movements
- Wastage
- Stock adjustments
- Stock reports foundation

Business requirements:
- Each product variation may have its own recipe.
- Add-ons may consume extra ingredients.
- Recipe cost must be calculated from ingredient cost.
- Recipe changes must not rewrite historical sales.
- Purchase receipt must increase stock.
- Wastage must reduce stock with reason and audit trail.
- Use FIFO where batch tracking applies.
- Do not directly edit stock without ledger entries.
- Prevent negative stock by default.
- Allow authorized override with audit reason.

Create UI, controllers, requests, policies, services,
models, migrations and automated tests.

Test unit conversion, costing, purchase, stock deduction,
wastage, concurrency and historical recipe snapshots.

Do not implement sales checkout yet.
Stop after Phase 2.
```

## PROMPT 3 --- POS, Orders and Payments

``` text
Implement Phase 3 only.

Build responsive POS and order management.

Features:
- Product/category grid
- Search
- Variations
- Sugar and ice options
- Add-ons
- Cart
- Quantity
- Discount
- Configurable tax
- Cash, bKash, Nagad, Card, Bank, Other
- Split payments
- Partial payment if enabled
- Hold and resume
- Takeaway, dine-in, delivery
- Order history
- Order status
- Invoice and reprint
- Refund with permission

Order number:
RB-DD-MM-YY-A0001

Generate server-side using an atomic,
concurrency-safe database mechanism.

Sequence:
- Reset for each business date.
- 100 orders per letter.
- A0001-A0100, B0001-B0100, etc.
- After Z continue AA, AB, AC.
- Never reuse cancelled numbers.
- Use Asia/Dhaka business date.

Offline client orders use UUID. Assign official
RB number only after server acceptance.

Order creation must be transactional:
validate, price snapshot, sequence, order, items,
payment, recipe stock deduction, cash movement,
status history, audit log and idempotency record.

Create complete automated tests including concurrent
order creation and duplicate request handling.

Stop after Phase 3.
```

## PROMPT 4 --- Expenses, Cash and Staff

``` text
Implement Phase 4 only.

Build:
- Expense categories
- Expense entries
- Receipt attachments
- Payment methods
- Approval rules
- Staff profiles
- Roles and permissions
- PIN login where suitable
- Shift and attendance foundation
- Cash session opening
- Cash movement ledger
- Cash session closing
- Expected vs actual cash
- Shortage/excess
- Manager approval
- Cash reports
- Staff sales reports
- Audit history

Cash formula:
Expected Cash = Opening Cash + Cash In - Cash Out

Difference = Actual Cash - Expected Cash

Ensure each cash movement is classified and counted
exactly once.

Ordinary staff cannot edit closed sessions.
Adjustments must create auditable records.

Do not count inventory purchases as operating
expenses in Profit and Loss.

Write tests for permissions, cash reconciliation,
expense handling and closing workflow.

Stop after Phase 4.
```

## PROMPT 5 --- Dashboard and Reports

``` text
Implement Phase 5 only.

Build dashboard KPIs and all required reports
from JUICE_BAR_POS_BLUEPRINT.md.

Include:
- Daily, weekly, monthly, yearly sales
- Custom date filters
- Product/category sales
- Payment reports
- Discounts and refunds
- Recipe COGS
- Gross profit
- Profit and Loss
- Expenses
- Purchases
- Supplier dues
- Stock valuation
- Ingredient consumption
- Wastage
- Staff performance
- Cash sessions
- Order cancellations

Financial formulas:
Net Sales = Gross Sales - Discounts - Refunds
Gross Profit = Net Sales - COGS
Net Profit = Gross Profit - Operating Expenses

Keep tax and payment fees identifiable.
Do not double-count inventory purchases.
Use historical order cost snapshots.

Add PDF/Excel exports where appropriate.
Optimize queries and paginate large datasets.

Write report reconciliation tests.
Stop after Phase 5.
```

## PROMPT 6 --- Web PWA Offline

``` text
Implement Phase 6 only: Web PWA offline support.

Use:
- Web app manifest
- Service Worker
- IndexedDB
- Local operation queue
- Cached POS catalogue
- Sync status
- Retry with exponential backoff
- Manual sync fallback

Support offline:
- Order creation
- Cash payment recording
- Expense recording
- Permitted cash movements

Use UUID for every local operation.

Requirements:
- Do not lose pending data after refresh/restart.
- Do not duplicate orders or payments.
- Do not deduct stock twice.
- Official RB number comes from server.
- Display pending/synced/failed/conflict status.
- Revalidate stock on server.
- Show last sync time.
- Show offline stock limitation.
- Do not cache sensitive reports unnecessarily.

Test offline, reconnect, duplicate retry, failed sync,
browser restart and conflict scenarios.

Document browser limitations.
Stop after Phase 6.
```

## PROMPT 7 --- API Finalization

``` text
Implement Phase 7 only.

Finalize REST API under /api/v1.

Include:
- Authentication
- User profile
- Device registration
- Product catalogue
- Variations and add-ons
- Ingredients and recipes
- Orders
- Payments
- Refunds
- Cash sessions
- Expenses
- Sync bootstrap
- Incremental sync
- Offline operation submission
- Sync status
- Reports where permitted

Requirements:
- Sanctum
- Device authorization
- Role permissions
- Idempotency
- UUID mapping
- Validation
- Pagination
- Consistent JSON
- Documented errors
- Rate limiting
- API versioning

Create and maintain:
- API_ENDPOINTS.md
- API_AUTHENTICATION.md
- API_SYNC_PROTOCOL.md
- API_ERROR_CODES.md
- API_EXAMPLES.md

Write API feature tests.

Do not start Android development until API
contracts and tests are approved.
Stop after Phase 7.
```

## PROMPT 8 --- SUNMI V2s Android Application

``` text
Implement Phase 8: Native Android Java POS.

Read the approved Android and API documents.

Before coding:
1. Inspect actual SUNMI V2s device information.
2. Verify Android version and firmware.
3. Verify printer SDK compatibility.
4. Confirm screen resolution and density.
5. Obtain official compatible SUNMI SDK.

Do not invent SDK classes or printer methods.

Build:
- Login
- PIN authentication if enabled
- Device registration
- Home dashboard
- POS product grid
- Search and categories
- Variations
- Sugar and ice selection
- Add-ons
- Cart
- Checkout
- Multiple payment methods
- Orders
- Order details
- Receipt preview
- Reprint
- Cash opening/closing
- Quick expense
- Offline queue
- Sync status
- Settings
- Printer test

Technology:
- Java
- Retrofit
- OkHttp
- Room/SQLite
- WorkManager
- Official SUNMI printer integration

Optimize for the 5.5-inch portrait display.

Implement:
- Local UUID
- Device ID
- Local timestamp
- Sync state
- Server order ID
- Official RB number
- Retry and duplicate protection

Create PrinterManager abstraction.

Test actual built-in printer:
- Receipt
- 58mm layout where supported
- Bangla character support
- Long item names
- Reprint
- Paper errors
- Printer unavailable state

Do not report printing success without confirmation.

Test on the actual device, not only emulator.

Deliver source code, Gradle configuration,
APK build instructions and device test report.

Stop after Phase 8.
```

## PROMPT 9 --- Production Audit and Deployment

``` text
Perform Phase 9 production readiness audit.

Do not add features before auditing.

Audit:
- Authentication
- Permissions
- POS
- Order number concurrency
- Payments
- Refunds
- Recipe costing
- Inventory ledger
- Purchases
- Expenses
- Cash closing
- Reports
- PWA offline
- Android offline
- Sync idempotency
- SUNMI printer
- Data backup
- Restore process
- API security
- Database performance

Verify financial reconciliation:
- Order totals match payment records.
- Stock movements match recipe consumption.
- No duplicate stock deduction.
- No duplicate payments.
- Purchase is not double-counted as expense.
- Cash session matches cash movements.
- Reports match source transactions.

Security:
- HTTPS
- Secure session/token handling
- Rate limits
- Validation
- Authorization
- Audit logs
- Secret management
- Database backups
- Restore test

Deployment:
- Production environment
- MySQL
- Queue worker
- Scheduler
- Storage permissions
- PWA configuration
- Android APK signing
- Error monitoring

Deliver:
- Final test report
- Deployment guide
- Backup/restore guide
- Admin manual
- Cashier manual
- Android installation guide
- Printer troubleshooting
- Known limitations

Do not claim any test passed unless executed.
Stop and present the final release assessment.
```

------------------------------------------------------------------------

# 14. Acceptance Criteria

The project is not ready for production until all relevant items below
pass.

## Web POS

-   [ ] Owner can log in.
-   [ ] Roles and permissions work.
-   [ ] Products and variations can be managed.
-   [ ] Recipes can be created and costed.
-   [ ] Purchase increases ingredient stock.
-   [ ] Completed sale deducts recipe ingredients.
-   [ ] Add-ons deduct their ingredients.
-   [ ] Order number follows RB format.
-   [ ] Concurrent orders do not duplicate numbers.
-   [ ] Multiple payment methods work.
-   [ ] Refund and cancellation are auditable.
-   [ ] Expenses are recorded correctly.
-   [ ] Cash opening and closing reconcile.
-   [ ] Reports reconcile with source transactions.
-   [ ] Offline orders survive browser restart.
-   [ ] Sync retries do not duplicate operations.

## Android / SUNMI

-   [ ] Staff can log in.
-   [ ] Product catalogue loads.
-   [ ] POS checkout works.
-   [ ] Offline orders persist after app restart.
-   [ ] Pending orders sync after internet returns.
-   [ ] Official RB number is assigned by server.
-   [ ] Built-in printer prints a correct receipt.
-   [ ] Reprint works.
-   [ ] Printer errors are handled.
-   [ ] Cash session works.
-   [ ] Duplicate sync is prevented.
-   [ ] Actual SUNMI V2s device testing is completed.

## Financial Integrity

-   [ ] Order total reconciles with payments.
-   [ ] Refunds are represented correctly.
-   [ ] COGS uses historical cost snapshots.
-   [ ] Inventory purchase is not double-counted.
-   [ ] Stock ledger is auditable.
-   [ ] Cash movement is counted exactly once.
-   [ ] Profit reports use documented formulas.

------------------------------------------------------------------------

# 15. First Action --- Start Claude Code

1.  Create a folder named `juice-bar`.
2.  Save this document as `JUICE_BAR_POS_BLUEPRINT.md` in the root.
3.  Open that folder in VS Code or your terminal.
4.  Start Claude Code.
5.  Give it **PROMPT 0 --- Master Project Setup** from this document.
6.  Review all generated documentation.
7.  Approve Phase 1 only after reviewing the architecture and database
    plan.

Suggested terminal workflow:

``` bash
mkdir juice-bar
cd juice-bar

# Copy JUICE_BAR_POS_BLUEPRINT.md into this folder

claude
```

Then ask Claude Code to read `JUICE_BAR_POS_BLUEPRINT.md` and execute
Prompt 0.

------------------------------------------------------------------------

# 16. Final Engineering Decisions

These decisions should remain consistent throughout the project:

1.  Laravel is the central backend.
2.  MySQL is the central online database.
3.  Web POS is developed and tested first.
4.  Android is a separate Native Java application.
5.  Both clients use the same API and server-side business rules.
6.  Recipe-based inventory is mandatory.
7.  Historical order and recipe snapshots are mandatory.
8.  Stock and cash ledgers must be auditable.
9.  Official RB order numbers are generated by the server.
10. Offline operations use UUIDs until server synchronization.
11. SUNMI printer integration must use verified device-compatible
    official support.
12. No production feature is considered complete without testing.

**Project principle: Build a reliable business system, not just a
billing screen.**

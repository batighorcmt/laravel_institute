# Institute Fees Management System Design

## Overview
A comprehensive fees management subsystem for an educational institute built in Laravel. Supports class-wise fee structures, common fees, monthly and one-time charges, discounts (scholarships/waivers), optional fines, partial payments, receipts, offline collections by class teachers and cashier, headmaster override, detailed reports, and future online payments (bKash/Nagad). Fee revisions apply from an effective date without retroactive impact.

## Core Requirements
- Roles: Admin, Headmaster, Cashier, Class Teacher, Auditor (read-only), Student/Guardian (future portal).
- Fee Types: class-specific tuition; institute-wide common fees (e.g., exam, club, tutorial); one-time and recurring (monthly/termly/annual).
- Discounts: percentage or fixed, per-student, per-fee or global; time-bounded; approval workflow.
- Fines: optional per-day or fixed; waiver capability; justification note.
- Collections: partial payments allowed; per-student ledger; receipts with unique numbers.
- Authority: Class Teacher collects for own class; Cashier aggregates deposits; Headmaster can collect and override.
- Reports: daily, date-range, monthly collections; dues/arrears; fee-wise breakdown; cashier deposit reconciliation; student statements; export to Excel/PDF.
- Future Online Payments: gateway integration; reconciliation; duplicate prevention; webhook handling.
- Fee Revision: new rates from a given effective month; past records remain unchanged.
- Audit/Compliance: immutable ledger entries, approvals, activity log, idempotency for payment events.

## Architecture (Laravel)
- Domain modules:
  - Billing: fee definitions, schedules, revisions, discounts, fines.
  - Collection: receipts, payments, cashier deposits, reconciliation.
  - Student: enrollment, class/section mapping, academic year.
  - Reporting: aggregated queries, exports.
  - Payments: gateway abstraction, webhooks, callbacks.
- Layers:
  - Controllers (HTTP/API) -> Services (business rules) -> Repositories (Eloquent) -> Models.
  - Jobs/Events: async webhook processing, receipt PDF generation, export.
  - Policies/Guards: role-based access for actions.

## Data Model
- Students
  - id, student_code, name, class_id, section_id, academic_year_id, status
  - guardian contacts
- Classes
  - id, name, level, sectioning
- FeeCategory
  - id, name (Tuition, Exam, Tutorial, Club, Admission, etc.), is_common (bool), frequency (monthly|one_time|termly|annual)
- FeeStructure
  - id, class_id (nullable if common), fee_category_id, amount, currency, effective_from (date), effective_to (nullable), active (bool)
  - Unique: (class_id, fee_category_id, effective_from)
  - Business: multiple rows per category over time to support revisions
- Discount
  - id, student_id, fee_category_id (nullable for global), type (percent|fixed), value, start_month, end_month, approved_by, reason
- FineRule
  - id, fee_category_id (nullable/global), type (per_day|fixed), rate, max_cap (nullable), active
- FineWaiver
  - id, student_id, applied_payment_id (nullable), amount, reason, approved_by
- BillingPeriod
  - id, month (YYYY-MM), academic_year_id, status (open|closed)
- Invoice (logical statement for a period; optional for monthly tuition)
  - id, student_id, billing_period_id, total_due, generated_at
- Payment (immutable ledger entry)
  - id, student_id, fee_category_id, invoice_id (nullable), amount_paid, discount_applied, fine_applied, payment_method (cash|bkash|nagad|bank), collected_by_user_id, role (teacher|cashier|headmaster|online), status (pending|settled|reversed), received_at
  - receipt_id (nullable until settled), external_txn_id (for gateways), idempotency_key
- Receipt
  - id, receipt_number (sequenced per-year), student_id, total_amount, printed_at, issued_by_user_id
- CashierDeposit
  - id, cashier_user_id, date, total_amount, note
- PaymentDepositMap
  - payment_id, cashier_deposit_id
- ActivityLog
  - id, actor_user_id, action, entity_type, entity_id, metadata, created_at

## Key Business Rules
- Fee Revision: selecting applicable `FeeStructure` by `effective_from <= period < effective_to/null` ensures past months unaffected.
- Partial Payments: multiple `Payment` rows can map to one invoice/period; `status` transitions to `settled` when fully covered.
- Discounts: compute per fee line; apply cap for percent; approval required for large waivers.
- Fines: calculated on outstanding days using `FineRule`; can be waived by authorized roles creating `FineWaiver` rows.
- Authority:
  - Class Teacher: create payments for students of their class; cannot close billing period or issue deposit unless cashier.
  - Cashier: accept deposits from teachers; link payments to deposits; produce reconciliation.
  - Headmaster: collect any payment; approve discounts/fine waivers; override within policy.
  - Admin: manage fee structures, rules, open/close periods.
- Receipts: generated only for `settled` payments (or partial receipt per payment item); unique `receipt_number` sequence per academic year.
- Online Payments: payments start as `pending` on gateway init; switch to `settled` on successful webhook with idempotency; reconciliation job maps to invoices.

## Workflows
1) Monthly Tuition Collection (Teacher/Cashier)
   - Open BillingPeriod for month.
   - System computes dues per student using active `FeeStructure` + discounts + prior arrears.
   - Teacher records payment(s); partial allowed; fines auto-added if rule active.
   - Cashier receives deposit; links payments to `CashierDeposit` and prints receipts.

2) One-Time/Common Fees
   - Create invoice for target students (all or filtered); frequency `one_time`.
   - Collect via teacher/cashier; headmaster can collect any student.

3) Fine Waiver
   - Teacher requests waiver; Headmaster/Admin approves -> `FineWaiver` entry reduces fine for target payment/invoice.

4) Online Payment (Future)
   - Initiate via `PaymentIntent` service -> gateway (bKash/Nagad) returns `external_txn_id`.
   - Record `Payment` with `pending` and `idempotency_key`.
   - Webhook receives confirmation -> verify signature -> mark `settled`, assign `receipt`, enqueue statement update.
   - Retry-safe and idempotent.

## Permissions & Policies
- Role-based policies in Laravel:
  - payment.create: Teacher (own class), Cashier, Headmaster, Admin
  - payment.approve_discount/fine_waiver: Headmaster, Admin
  - deposit.create: Cashier
  - report.view: Headmaster, Admin, Auditor (read-only)
  - fees.manage: Admin

## Reporting
- Collections:
  - Daily and date-range by collector, method, fee category.
  - Monthly with period close status.
- Dues/Arrears:
  - Per student statement; aging buckets (0-30, 31-60, 61-90+ days).
- Reconciliation:
  - Teacher collections vs cashier deposits; unmatched payments.
- Breakdown:
  - Fee-wise totals, discounts given, fines imposed/waived.
- Exports: Excel/PDF via `maatwebsite/excel` and `dompdf`.

## API & UI Outline
- Routes (sample):
  - `POST /billing/periods` open/close
  - `GET /students/{id}/statement`
  - `POST /payments` create (teacher/cashier/headmaster)
  - `POST /payments/{id}/waive-fine`
  - `POST /discounts` create/approve
  - `POST /deposits` create/link payments
  - `GET /reports/collections`
  - `GET /reports/dues`
  - `POST /payments/online/intents` (future)
  - `POST /webhooks/bkash` / `POST /webhooks/nagad`
- UI Pages:
  - Fee Structure management
  - Billing Period dashboard
  - Class Teacher collection screen
  - Cashier deposit & receipt printing
  - Headmaster approvals
  - Reporting hub

## Migrations (planned)
1. students, classes, sections (existing in repo likely)
2. fee_categories
3. fee_structures (effective_from/to)
4. discounts, fine_rules, fine_waivers
5. billing_periods
6. invoices
7. payments (immutable ledger)
8. receipts (sequencer)
9. cashier_deposits, payment_deposit_map
10. activity_logs

## Extensibility & Online Payments
- Gateway Abstraction: `PaymentGatewayInterface` with drivers for bKash/Nagad.
- Webhooks: signed, idempotent, retry-tolerant; store raw payload for audits.
- Reconciliation: scheduled job compares gateway settlements with local ledger.
- Idempotency: `idempotency_key` on payment intents to avoid duplicates.

## Implementation Notes
- Ensure calculations use decimal with appropriate precision.
- Use `effective_from` versioning for fee changes; never update past payments.
- Use Policies and middleware for role enforcement.
- Generate receipts with PDF templates; include QR/barcode of `receipt_number`.
- Add seeders for sample fee structures and rules.

## Next Steps
- Approve model list and columns.
- Draft migrations and Eloquent models.
- Implement services for calculation and collection.
- Build teacher/cashier UI and reports.
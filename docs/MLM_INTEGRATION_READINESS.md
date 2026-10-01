# MLM / E-commerce Integration Readiness

Status: Path 1 integration, cashback eligibility, and Admin cashback payout foundation implemented; automatic payout and customer cashback dashboard remain out of scope.

## Existing architecture

- Laravel `^13.8`, PHP `^8.4`, Blade views, Vite, and Tailwind CSS.
- Session authentication uses the Eloquent `App\Models\User` provider.
- Admin access is protected by the existing `admin` middleware and granular `permission:*` middleware.
- Customer API authentication uses the existing Sanctum-compatible token trait and API-token middleware.
- There is one shared `users` table for Super Admin, Admin, and Customer roles. No second customer or member table was introduced.
- The existing admin UI is Blade-based under `resources/views/admin`; no new UI route or framework was added.

## Existing customer-to-member mapping

- `users.id` remains the canonical local customer identity.
- Customer accounts use the existing `Customer` role.
- `users.woocommerce_customer_id` is the existing unique WooCommerce customer mapping.
- `orders.user_id` points to the local user and is nullable for guest WooCommerce orders.
- The new nullable unique `users.mlm_member_id` is the future MLM identity. It is not assigned automatically and does not alter existing customer records.

## Approved qualification and amount policy

- Local `orders.status = delivered` is the current completed-delivery state. WooCommerce `completed` maps to it, while `processing` maps to `processing`.
- `orders.pay_status = paid` and the existing payment record are available as payment evidence.
- An order qualifies only when its local status is `delivered`, its existing payment record is `paid`, and the seven-day return period from the delivered status-history event has elapsed. The boundary is inclusive at seven days.
- The exact eligible amount is `subtotal - discount - approved refund`. GST and shipping are not included. Approved and completed refund rows are deducted; pending and rejected refunds are not.
- Guest orders without a local user/member mapping are not eligible. The existing customer-to-member mapping remains explicit through `users.mlm_member_id` and the `mlm_members.user_id` relationship.

## Refund, cancellation, and payment sources

- Cancellation and full lifecycle states are recorded on `orders.status`, including `cancelled` and `refunded`.
- Refunds are recorded in `refunds`; `RefundService` approves refunds transactionally and changes the order to refunded when the approved total reaches the order total.
- Returns have a separate `order_returns` workflow and must not be treated as an MLM reversal without an approved rule.
- Admin payment verification is already handled by the existing order/payment service and route; this integration does not change it.

## Existing duplicate protection

- WooCommerce orders use unique `orders.woocommerce_id` when supplied.
- WooCommerce customers use unique `users.woocommerce_customer_id` when supplied.
- Webhook receipts use the unique `provider + delivery_id` key in `webhook_logs`, with signature verification and duplicate detection in the existing webhook service.
- The new nullable unique `orders.mlm_integration_reference` is populated as `order:{id}:mlm:v1` when eligible processing begins.
- `mlm_calculation_runs.order_id` is nullable for manual calculations and unique when supplied, enforcing one order-to-calculation ownership. The existing calculation idempotency key remains active as a second protection.

## Additive fields staged

| Table | Field | Purpose | Current behavior |
| --- | --- | --- | --- |
| `users` | `mlm_member_id` | Optional local MLM identity | Nullable, unique, manually/explicitly assigned later |
| `orders` | `mlm_processing_status` | Order-level MLM processing state | Tracks waiting, processing, completed, failed, not-eligible, and reversed states |
| `orders` | `mlm_eligible_amount` | Approved eligible amount snapshot | Stores exact source amount at two-decimal order precision |
| `orders` | `mlm_pv_processing_status` | PV processing state | Follows calculation/reversal processing |
| `orders` | `mlm_formula_version` | Calculation-rule snapshot reference | Stores the applied rule version |
| `orders` | `mlm_integration_reference` | Idempotency/integration reference | Nullable, unique; populated for order-backed processing |
| `orders` | `mlm_reversal_status` | Reversal boundary | Remains neutral until an approved refund/cancellation reverses a run |
| `orders` | `mlm_error_message` | Reconciliation failure detail | Stores a bounded processing error for Admin retry |
| `orders` | `mlm_processed_at` | Processing timestamp | Set when processing reaches a terminal state |
| `mlm_calculation_runs` | `order_id` | Source order mapping | Nullable for manual runs; unique for order-backed runs |
| `mlm_calculation_runs` | processing/error fields | Operational snapshot | Stores processing start/end, duration, and failure detail |

The fields are nullable or have neutral states, so existing orders and customers remain valid and existing order/payment/product/invoice behavior is unchanged.

## Migration and UI conflict review

- No existing `mlm_*` tables, columns, routes, models, or services were found before this change.
- The migration uses additive column/index creation, explicit short index names, and guards for existing columns/indexes. Its `down()` is intentionally non-destructive.
- Existing admin navigation already has Customers, Orders, Payments, and Invoices. The integration adds a small permission-protected MLM Reconciliation screen and retry action; no existing e-commerce screen was redesigned.
- Existing WooCommerce import code currently converts external numeric amounts to PHP floats for order snapshots. A future exact-decimal MLM service must consume normalized decimal strings/snapshots and must not reuse display-rounded values.
- Guest orders have no local customer/member mapping; this remains an explicit integration decision.

## Integration behavior

- Order saves/status/payment changes dispatch a unique `ProcessMlmEligibleOrder` job. The service is idempotent and can also be invoked by the Admin reconciliation retry action.
- A successful order event stores the eligible amount, rule version, exact PV/income totals, placement path snapshot, source order, processing timing, and income ledger lines through the existing calculation engine.
- A changed placement tree does not alter an existing order calculation. A later approved refund or cancellation creates immutable reversal ledger entries; it does not recalculate the original run.
- Cashback eligibility is recorded separately from MLM income. It uses the final MLM eligible amount, a ₹200 exact threshold, and a maximum conditional amount equal to that eligible amount. It does not create PV or income.
- Cashback now supports Admin company-profit-pool declarations, filtered selection across pagination, transactional payout batches, manual payment reference/proof, holds, reversals, and role permissions. No automatic payout API, customer cashback dashboard, product logic, invoice logic, order-status logic, or live e-commerce formula changes were added.

## Cashback eligibility foundation

- Each order-backed MLM calculation can create one cashback eligibility record using an order/calculation idempotency key.
- `₹199.99` is not eligible; exactly `₹200` is eligible; `₹500` has a maximum conditional cashback amount of `₹500`.
- Eligibility starts as `eligible_awaiting_company_profit`; it is never marked paid automatically.
- Refund/cancellation processing changes an existing record to `cancelled_due_to_refund` and writes one immutable signed refund-recovery adjustment.
- GST is explicitly excluded from the eligibility snapshot.

## Remaining runtime check

- The application queue worker/scheduler must be operated in the deployment environment for queued order events to be processed asynchronously. The Admin reconciliation screen exposes waiting, completed, failed, and reversed records and permits authorized retry.
- No existing order, payment, refund, product, customer, or invoice logic was rewritten.

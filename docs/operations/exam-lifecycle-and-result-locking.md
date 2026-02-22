# Exam Lifecycle & Result Locking

This document defines the production lifecycle and legal-grade result controls for VoraCBT.

## Lifecycle States

Exams move through the following states:

1. `draft`
2. `scheduled`
3. `open`
4. `closed`
5. `under_review`
6. `finalized`
7. `archived`

### Allowed transitions

- `draft` -> `scheduled`, `archived`
- `scheduled` -> `open`, `archived`
- `open` -> `closed`
- `closed` -> `under_review`, `archived`
- `under_review` -> `finalized`, `archived`
- `finalized` -> `archived`

Any other transition is rejected.

## Edit restrictions

Exam templates are editable only in:

- `draft`
- `scheduled`

Edits are blocked when an exam is `open`, `closed`, `under_review`, `finalized`, or `archived`.

## Audit trails

All state transitions are written to `exam_lifecycle_logs` with:

- old state
- new state
- actor user ID
- timestamp
- optional reason

## Result Finalization

After review, `exam_results` can be finalized (locked):

- `is_locked = true`
- `locked_at` timestamp
- `locked_by` approver ID
- `lock_reason`

### Locked result behavior

- Publishing changes is blocked for locked results.
- Manual grading updates are blocked for locked results.
- Aggregation updates skip locked results.

## Admin Override

Admins can unlock results using explicit override APIs/services. Every override writes an immutable `result_lock_audit_logs` entry with:

- actor
- action (`ADMIN_OVERRIDE_UNLOCK`)
- reason
- timestamp


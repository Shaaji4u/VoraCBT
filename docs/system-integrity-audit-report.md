# CBT System Integrity Auditor Report

## Scope & Method
- Reviewed identity, import/export, proctoring, grading, integration, routing, middleware, and schema codepaths.
- Focused on the requested controls: authentication parity, CSV/password handling, AI integration controls, academic context binding, RBAC, performance, and security.
- Evidence source: repository static audit only (no running app integration tests).

## 1) Full System Audit Checklist

| Area | Control | Status | Notes |
|---|---|---|---|
| Authentication | OAuth + password map to same user record | ⚠️ Partial | `sms_oauth_id` is stored on `users` with unique index and linker checks, but no end-to-end password/OAuth login controller flow is present for complete parity validation. |
| Authentication | No duplicate accounts | ✅ Implemented | Duplicate checks at import-time + DB unique constraints for `email`/`admission_number`/`staff_id`/`sms_oauth_id`. |
| Authentication | OAuth returns/propagates Name/Class/Term/Session/Role | ❌ Not enforced | Linker only writes `academic_session` and `term` context; no class/role propagation contract is enforced in code. |
| Authentication | Staff login works both methods | ❌ Not verifiable | Staff import/linking exists, but no password auth endpoint or OAuth callback flow found in this repo. |
| Authentication | Password reset toggle behavior | ✅ Partial | Import validation checks email requirement when `AUTH_EMAIL_RESET_ENABLED=true`; reset workflow not found. |
| Authentication | Forced password change on import | ❌ Missing | No `must_change_password` field/logic found in users schema/services. |
| CSV + Passwords | Secure random password generation | ⚠️ Partial | Uses `random_int` for character picks; then uses `shuffle()` (non-crypto PRNG) for ordering. |
| CSV + Passwords | Password hashing | ✅ Implemented | Uses `password_hash(..., PASSWORD_ARGON2ID)`. |
| CSV + Passwords | Group print does not expose hashes | ✅ Implemented | Export outputs temporary plaintext cards, CSV redacts password field, never exposes hashes. |
| CSV + Passwords | No plaintext stored after generation | ❌ Fails requirement | Plaintext is stored in `user_credentials_buffer` up to 24h before export/expiry handling. |
| CSV + Passwords | Duplicate import prevention | ✅ Implemented | File-level and DB-level duplicate checks present. |
| AI integration | Response normalization + safe write path | ❌ Missing | No AI grading/proctoring provider integration code found. |
| AI integration | Provider/task/timestamp/student/exam logging | ❌ Missing | No AI-call audit schema/service found. |
| AI integration | Timeout/retry/manual fallback | ❌ Missing | No AI client/service exists. |
| AI integration | AI cannot modify exam questions | ⚠️ Indirect only | No AI question mutation path because AI module absent. |
| AI integration | Teacher approval for AI grading | ❌ Missing | No AI grading approval workflow present. |
| AI integration | AI proctoring cannot auto-punish | ✅ Current behavior safe | Proctoring flags sessions and logs events; no auto-punishment action found. |
| Academic context | Validate class/session/subject binding on OAuth | ❌ Missing | No validator enforcing class/session/subject membership at OAuth/link/session start. |
| RBAC | Student/Teacher/Proctor/Admin role boundaries | ⚠️ Partial | Role middleware exists, but routes shown are not protected by middleware chain in entrypoint/router wiring. |
| Performance | Async external calls | ✅ Partial | Queue usage exists for analytics/result push and retry handling for result push jobs. |
| Performance | Non-blocking grading UI | ⚠️ Not verifiable | Backend queue exists but no UI flow evaluated in this static audit. |
| Performance | Cache non-sensitive analytics | ⚠️ Limited evidence | File cache abstraction exists; no explicit analytics caching policy found. |
| Security | HTTPS enforced | ❌ Missing | No middleware or bootstrap enforcement of HTTPS detected. |
| Security | CSRF protection | ❌ Missing | No CSRF middleware/token validation found in this router/controller stack. |
| Security | Login rate limiting | ⚠️ Partial | Generic rate-limit middleware exists, but not wired to explicit auth/login routes in current code. |
| Security | Secure API key storage | ✅ Baseline | Env-based secret usage pattern exists for integration/JWT config. |
| Security | SQL injection protection | ✅ Mostly | Predominantly parameterized DBAL queries. |
| Security | Log tamper protection | ❌ Missing | Audit/integration logs are plain DB/file writes with no immutability/signature controls. |

---

## 2) Authentication Flow Validation Report

### What is implemented
- OAuth identity linkage is anchored to a single `users` record via `sms_oauth_id`, with anti-collision checks before linking.
- Import flows (student/staff) support insertion of OAuth ID, class, session, term fields.
- Duplicate prevention exists both in CSV pre-validation and DB lookups.

### Gaps / Non-compliance
1. **No complete auth flow parity evidence (OAuth vs password)**
   - Repository includes identity linking/import mechanisms, but lacks a visible login controller for credential verification and OAuth callback/session issuance.
2. **OAuth payload contract is incomplete**
   - Required fields (name/class/term/session/role) are not validated/required centrally.
3. **Forced password change on first login missing**
   - No schema flag nor post-login enforcement logic found.
4. **Password reset behavior only partially represented**
   - Toggle is used for import-time email requirement; reset execution path is absent.

### Risk Rating
- **High** for operational correctness if dual-auth parity is required in production policy.

---

## 3) AI Integration Compliance Report

### Result
- **AI module is effectively absent** in audited code paths.

### Compliance outcomes against requested controls
- Response normalization before DB write: **Not implemented**.
- No direct DB write from raw AI response: **Not implemented (no AI path)**.
- AI call logs (provider/task/timestamp/student/exam): **Not implemented**.
- Timeout/retry/manual fallback for AI tasks: **Not implemented**.
- AI grading requires teacher approval: **Not implemented**.
- AI cannot mutate exam questions: **Currently true by absence**, but should be codified by policy guardrails when AI is introduced.
- AI proctoring no auto-punishment: **Currently true** (only logging/flagging behavior exists).

### Risk Rating
- **High** for feature-completeness against stated mission requirements.

---

## 4) RBAC Verification Matrix

| Role | Expected Permission | Observed State | Verdict |
|---|---|---|---|
| Student | Take exam only | Role middleware available but route-layer enforcement is not evident in `public/index.php` + `routes/api.php`. | ⚠️ Partial |
| Teacher | Grade + AI suggestions | Grading services exist; AI suggestions pipeline absent; teacher-only gate not demonstrated. | ⚠️ Partial |
| Proctor | View live sessions | Proctoring event endpoints exist; explicit proctor-only view/access controls not shown. | ⚠️ Partial |
| Admin | Configure AI/auth/imports | Admin identity endpoints parse JWT for `sub`, but do not verify role claim in controller; no AI config module found. | ⚠️ Partial |

### Privilege Escalation Assessment
- **Potential risk**: relying on token presence without strict role assertion in admin controllers can allow over-broad access if JWT issuance is weak/misconfigured.

---

## 5) Performance Impact Report

### Positive findings
- Queue abstractions and job patterns exist (e.g., result push retry/backoff logic), reducing synchronous external-call pressure.
- Proctoring and exam session writes are mostly bounded and indexed by identifiers.

### Performance concerns
- Import duplicate checks currently run per-row DB existence queries, which may degrade large CSV throughput.
- No clear batching strategy for duplicate detection against pre-fetched keysets.
- No explicit analytics caching strategy found for high-traffic reporting paths.

### Hosting compatibility
- Project structure and docs indicate shared-hosting aware operations guidance, but runtime performance on shared hosting will depend heavily on cron worker cadence.

---

## 6) Security Vulnerability List

### High
1. **Plaintext temporary password persistence in DB buffer** (24h window) increases blast radius on DB compromise.
2. **Missing CSRF protection** for state-changing routes.
3. **No explicit HTTPS enforcement** in request handling.
4. **Missing OAuth academic context validation** (class/session/subject) can allow context mismatch.

### Medium
5. **Admin route protection appears token-based without explicit role assertion** in controller path.
6. **Rate limiting not demonstrably applied to login/auth routes** in current router wiring.
7. **Password shuffling uses `shuffle()`** (non-cryptographic) after cryptographically secure char selection.

### Low
8. **No tamper-evident audit logging** (hash chains/WORM/signatures absent).

---

## Recommended Remediation Plan (Prioritized)
1. Implement a centralized Auth service/controller pair covering password + OAuth callback with strict account-link parity tests.
2. Add `must_change_password` (or equivalent) on import/regeneration and enforce at first authenticated action.
3. Remove plaintext storage pattern (or encrypt at rest with short-lived decryption keys and one-time retrieval semantics).
4. Enforce role checks at route middleware level for every admin/proctor/teacher endpoint.
5. Add academic context guard service: validate class + session + subject registration before session start.
6. Add CSRF middleware (for browser sessions) and HTTPS redirect/HSTS enforcement.
7. Introduce AI orchestration module with: normalized DTOs, strict allow-list writes, audit logs, retries/timeouts, and teacher-approval gating.
8. Add immutable/tamper-evident security logging strategy.

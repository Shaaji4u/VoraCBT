# CBT UI/UX Architecture Blueprint

This document defines exam-safe, teacher-friendly UI flows for VoraCBT with support for dual authentication, AI-assisted grading, AI proctoring alerts, CSV onboarding, and admin controls.

## 1) Wireframes

## 1.1 Login Screen (Dual Authentication)

```text
+--------------------------------------------------------------------------------+
| VoraCBT                                                                        |
| Minimal logo, uptime badge, help                                               |
+--------------------------------------+-----------------------------------------+
| Sign in                              | Why this portal                         |
|--------------------------------------|-----------------------------------------|
| [ Username or Email                ] | - Secure CBT exams                      |
| [ Password                         ] | - Staff grading and analytics           |
| [ ] Remember me                      | - Student dashboard                      |
| (Sign In)                            |                                         |
|                                      | System notices (optional)               |
| --- OR ----------------------------  |                                         |
| (Sign in with SMS Verification)      |                                         |
|                                      |                                         |
| Password reset link (if enabled)     |                                         |
|                                      |                                         |
| If SMS success:                      |                                         |
|  ✓ Signed in via SMS Verification    |                                         |
|  Name: <from SMS profile>            |                                         |
|  Class / Term / Session / Role       |                                         |
+--------------------------------------+-----------------------------------------+
```

### Login behavior rules
- Show both local login and SMS OAuth in a single card to avoid account-path confusion.
- If password reset is disabled in Admin, hide link and replace with helper text: “Contact your administrator.”
- After SMS OAuth callback, match account by normalized unique identity key (phone/student ID/email mapping policy) and only create linkage once.
- Show immutable notice: **“Signed in via SMS Verification.”**
- Read-only profile chips (Name, Class, Term, Session, Role) appear before redirect so user confirms identity context.

---

## 1.2 Student Exam Screen (Distraction-Free)

```text
+--------------------------------------------------------------------------------+
| Exam: Biology Midterm              [Timer 00:42:11] [Save status: Synced]     |
+--------------------------------------------------------------------------------+
| Q12 of 50 | Section B | Mark for review [ ] | Full-screen [toggle]            |
+------------------------------+-------------------------------------------------+
| Question navigator           | Question panel                                  |
| [01][02][03][04][05]         | ------------------------------------------------|
| [06][07][08][09][10]         | Prompt text, figure, and options                |
| [11][12][13][14][15]         |                                                 |
| ...                          | (A) ....                                        |
| Legend:                      | (B) ....                                        |
| • answered                   | (C) ....                                        |
| • unanswered                 | (D) ....                                        |
| • marked                     |                                                 |
+------------------------------+-------------------------------------------------+
| [Previous] [Save & Next] [Submit Exam]                                         |
+--------------------------------------------------------------------------------+
| No AI hints, no adaptive prompts, no confidence nudges during exam runtime.    |
+--------------------------------------------------------------------------------+
```

### Student exam behavior rules
- Offer optional full-screen mode, with keyboard-accessible toggle.
- Timer is always visible, high-contrast, and never hidden behind menus.
- No animations beyond subtle state change feedback (save/sync).
- AI must not suggest question difficulty, alter question text, or interact with student during attempt.
- If proctoring enabled, monitor silently; show no indicator unless session is flagged.
- If flagged, show non-blocking banner: “Your session is being reviewed. Please continue your exam.”

---

## 1.3 Teacher Grading Screen (AI-Assisted, Human-Controlled)

```text
+--------------------------------------------------------------------------------+
| Essay Grading | Exam: Civic Studies | Student: Amina Yusuf | Q3               |
+--------------------------------------+-----------------------------------------+
| Student Response                     | AI Suggestion                           |
|--------------------------------------|-----------------------------------------|
| [Scrollable answer body]             | Proposed Score: 14/20                   |
|                                      | Confidence: 0.82 (Medium-High)          |
| Highlight rubric evidence            | Feedback draft:                          |
|                                      | - Strong thesis                          |
|                                      | - Needs clearer evidence                 |
|                                      | [Rubric alignment checklist]             |
+--------------------------------------+-----------------------------------------+
| Teacher decision: [Approve] [Edit + Save] [Reject] [Next Student]             |
+--------------------------------------------------------------------------------+
| Rule: AI suggestions never auto-commit. Teacher action is required every time. |
+--------------------------------------------------------------------------------+
```

### Grading behavior rules
- Split layout: student answer left, AI recommendation right.
- Always show confidence level with plain-language interpretation.
- Require explicit teacher decision (Approve/Edit/Reject).
- Record AI suggestion + final teacher decision for audit.

---

## 1.4 Proctor Dashboard (Real-Time Oversight)

```text
+--------------------------------------------------------------------------------+
| Proctor Dashboard | Active Exam Sessions | Auto-refresh: ON (15s)              |
+--------------------------------------------------------------------------------+
| Filters: [Exam] [Class] [Severity] [Flag Type] [Search student]               |
+--------------------------------------------------------------------------------+
| Student           | Status      | Flags                    | Time Remaining    |
|-------------------+-------------+--------------------------+-------------------|
| Chinedu Okafor    | In Progress | Tab switch (2)           | 00:37:02          |
| Grace Muli        | Flagged     | Timing anomaly (High)    | 00:35:40          |
| Fatima Bello      | In Progress | None                     | 00:33:12          |
| Kelvin Obasi      | Flagged     | Webcam anomaly (Medium)  | 00:30:55          |
+--------------------------------------------------------------------------------+
| Row click => Session Drill-down                                                |
| Timeline | Events | Device fingerprint | Evidence snapshots | Supervisor notes |
+--------------------------------------------------------------------------------+
```

### Proctoring behavior rules
- Use severity badges (Low/Medium/High/Critical) with color + icon + text labels.
- Support live refresh and manual refresh fallback for low bandwidth.
- Drill-down includes full event timeline and moderation actions.

---

## 1.5 Admin AI + Auth + Import Settings

```text
+--------------------------------------------------------------------------------+
| Admin Settings                                                                  |
+------------------------------+-------------------------------------------------+
| Tabs                         | Panel                                           |
| - AI Configuration           | AI Provider: [OpenAI v]                         |
| - Authentication             | API Key: [••••••••••••] [Test Connection]       |
| - CSV Import                 | Enable features:                                |
| - Credential Printing        | [x] Grading  [x] Proctoring  [ ] Analytics      |
|                              |                                                 |
|                              | Authentication                                  |
|                              | [x] Enable SMS OAuth                            |
|                              | [ ] Enable Email Password Reset                 |
|                              | [x] Force password change on import             |
|                              |                                                 |
|                              | CSV Import                                      |
|                              | [Upload CSV] [Download Template]                |
|                              | Column mapping: Name -> full_name               |
|                              |                 Class -> class_name             |
|                              |                 Phone -> phone                  |
|                              | [Generate Passwords] [Preview] [Run Import]     |
|                              | Print group by: (Class) (Session) (Term)        |
+------------------------------+-------------------------------------------------+
```

### Admin behavior rules
- Per-feature AI toggles enforced server-side and reflected in UI state.
- Auth config changes require confirmation and write audit logs.
- CSV mapping screen must validate required columns before import execution.
- Credential generation screen supports grouped printable sheets by class/session/term.

---

## 2) UX Flow Documentation

## 2.1 Authentication flow (password + SMS OAuth)
1. User opens `/login`.
2. User selects either password sign-in or SMS OAuth.
3. For SMS OAuth callback:
   - System resolves identity link.
   - If existing linked account: sign in and hydrate context (name/class/term/session/role).
   - If matching local account but unlinked: prompt “Link this SMS identity?” once.
   - If no match: route to controlled account-claim flow (admin policy).
4. Confirmation state shows “Signed in via SMS Verification.”
5. Redirect by role:
   - Student → student dashboard.
   - Staff → grading/dashboard.
   - Admin → admin dashboard.

## 2.2 Student exam flow
1. Student starts exam from dashboard.
2. Pre-flight checks: network, time sync, browser support, optional full-screen prompt.
3. In-exam loop: answer → save → navigate.
4. Background proctoring runs silently.
5. If flagged, student sees non-blocking warning only.
6. Submit confirmation (safe, no modal spam) and final lock.

## 2.3 Teacher AI grading flow
1. Teacher opens pending essays queue.
2. Select response → split view loads answer + AI suggestion.
3. Teacher chooses Approve/Edit/Reject.
4. Save commits teacher decision and logs AI metadata.
5. Move to next response.

## 2.4 Proctor monitoring flow
1. Proctor opens active session grid.
2. Grid auto-refreshes every 15 seconds (configurable).
3. Selecting flagged student opens drill-down timeline.
4. Proctor tags event severity, adds notes, optionally escalates.

## 2.5 Admin configuration + CSV onboarding flow
1. Admin configures AI provider + feature toggles.
2. Admin configures authentication controls (SMS OAuth, reset toggle).
3. Admin uploads CSV, maps fields, previews validation results.
4. System generates passwords, marks forced change on first login (if enabled).
5. Admin prints grouped credentials and distribution sheets.

---

## 3) Role-Based Navigation Map

```text
Public
└── Login
    ├── Password login
    └── SMS OAuth login

Student
├── Dashboard
│   ├── My Exams
│   ├── Upcoming Schedule
│   └── Results
└── Exam Runtime
    ├── Question Workspace
    ├── Timer + Progress
    └── Submit

Staff (Teacher/Proctor)
├── Staff Dashboard
│   ├── Grading Queue
│   ├── Proctor Console
│   └── Reports
├── Essay Grading Workspace
└── Proctor Session Drill-down

Admin
├── Admin Dashboard
├── AI Configuration
├── Authentication Settings
├── CSV Import + Mapping
├── Credential Generation/Printing
└── Audit Trail
```

### Role separation standards
- Student UI never exposes admin/proctor controls.
- Teacher grading actions are distinct from proctor moderation actions.
- Admin-only configuration routes protected by role middleware and visible role labels.

---

## 4) Accessibility & Low-Bandwidth Review

## 4.1 Accessibility checklist
- Keyboard-only operation for all primary exam and grading actions.
- Focus indicators always visible with high-contrast outlines.
- Color is never the sole status signal (icons + text labels required).
- ARIA labels for icon-only controls (timer, full-screen toggle, refresh).
- Semantic heading hierarchy for screen readers.
- Form errors are inline, specific, and announced in accessible regions.

## 4.2 Exam safety + cognitive load
- Minimize competing UI elements in exam mode.
- Avoid disruptive popups; use inline confirmations and compact banners.
- Keep consistent element positions (timer, navigation, submit) across screens.

## 4.3 Low-bandwidth behavior
- Use lightweight polling and delta updates for proctor grid.
- Provide manual refresh fallback when realtime updates lag.
- Defer non-critical imagery and avoid heavy animation assets.
- Maintain clear offline/sync states (“Saved locally”, “Synced”).

## 4.4 Mobile responsiveness strategy
- Mobile support for dashboard and monitoring views.
- Exam runtime optimized for desktop/laptop with guarded mobile layout.
- Tap targets >= 44px, responsive typography, and compact sidebar collapse on small screens.

---

## 5) UI Standards Summary
- Minimal, distraction-free exam interface.
- Consistent color tokens for success, warning, critical, neutral.
- Non-intrusive audit visibility for staff/admin workflows.
- Explicit AI boundaries: assistive for staff, invisible to students during exams.

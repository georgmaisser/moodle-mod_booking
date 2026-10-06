# Changes — bookingextension_oneclick

All notable changes to the One-Click trial-instance provisioning plugin are documented here.
Versions use the Moodle `YYYYMMDDXX` scheme; the human-readable release tag is in parentheses.

## 2026070302 (v1.4.5) — 2026-07-07

### Changed
- Guest email-claim flow: the automatic continuation message after a successful claim is now a
  plain fresh request ("Please create my trial instance named X") instead of a confirmation-style
  sentence ("My email address is set now. Please create my instance X now."). The old wording
  pattern-matched the selector's "explicit confirmation of an already pending action ->
  commands=[]" decision rule and reliably destabilized the follow-up planning turn
  (`CONTRACT_VALIDATION_ERROR`: command-bearing response type with empty commands), so the very
  first post-claim attempt failed with a "please try again" error while a manual retry worked.
  Changed in the lang strings (en/de) and the client-side fallback for older payloads.

## 2026070301 (v1.4.4) — 2026-07-06

### Added
- Admin view for `oneclick.list_instances`: a user holding the new capability
  `bookingextension/oneclick:viewalljobs` (RISK_PERSONAL, system level, **no archetype
  defaults** — must be granted explicitly; site admins pass implicitly) transparently gets the
  full job list **across all users** via the operator endpoint `GET /admin/jobs` (newest 100,
  including owner user id/email and `error_summary` for failed jobs). Everything the planner
  sees — schema, description, triggers, guidance — is unchanged for everyone, so an
  unprivileged user's agent cannot learn the admin view exists; the capability only changes
  what the execution observation contains. New `provisioner_client::list_all_jobs()`
  (`X-Operator-Id` audit header, limit/offset passthrough).

## 2026070300 (v1.4.3) — 2026-07-03

### Fixed
- Guest claim flow language binding: every user-facing preflight clarification of
  `oneclick.create_instance` (claim prompt, register hint, template choice, email-not-verified)
  is now rendered in the **conversation language** (framework-injected `outputlang`) instead of
  the requester's UI language — a guest account carries the site default language, which broke
  German conversations with English clarifications. The claim form texts AND the automatic
  continuation message now ship server-rendered in the payload (`payload.strings`); the client
  falls back to its own strings for older payloads. A foreign-language continuation message
  also destabilized the follow-up planning turn (observed as `CONTRACT_VALIDATION_ERROR`).

## 2026070202 (v1.4.2) — 2026-07-02

### Changed
- Guest email-claim flow: the claim form now ships as a **preview block on the preflight
  clarification issue** (agent-engine preview source C, the clarification preview channel), so it
  opens in the side panel with the **first request** — before any confirmation. The previous
  extra roundtrip (confirm → honest error → form) is gone; the execute()-side short-circuit stays
  as a defensive net for stale queue items and mid-flow account changes. Requires an agent build
  that includes the clarification preview channel (preview_passthrough stash/consume); on older
  agents the clarification text (with the register link) still shows, only the inline form is
  absent.

## 2026070201 (v1.4.1) — 2026-07-02

### Changed
- Guest email-claim flow: after a successful claim, the side preview now **continues the
  conversation automatically** — it re-issues the instance request through the regular chat
  pipeline (fills the agent input and triggers send, so privacy precheck, thread and rendering all
  run normally). If the chat is busy or absent, the success card falls back to "ask again".
- `/spawn` reports `requester_email_verified` from `user.confirmed` again: the provisioner
  hard-rejects unverified requesters (422), which broke the post-claim spawn. Ownership is proven
  later via the conversion's set-password email; the claim preference stays recorded for auditing
  and a future re-verification flow.

## 2026070200 (v1.4.0) — 2026-07-02

### Added
- Guest email-claim flow for `oneclick.create_instance`: a shopping_cart guest-checkout user
  (temporary `guest_checkout_*` account) is no longer turned away with "register first". They now
  pass preflight flagged, confirm the creation as usual, and `execute()` short-circuits into a side
  preview offering the lightest possible upgrade — enter just an email address — next to the full
  log-in/registration link. Submitting calls the new self-service webservice
  `bookingextension_oneclick_claim_guest_email`, which converts the account through shopping_cart's
  own guest conversion (real email, non-guest username, pending 24h cleanup cancelled, set-password
  email for later verification) and refreshes the session user, so simply asking again creates the
  instance. The email travels form → webservice and never enters the chat (where the privacy
  anonymizer would redact it). Zero agent-engine changes: the flow rides the existing
  result-preview channel (`get_result_preview` → `preview_passthrough`).
- Honest verification reporting: an email set via the claim flow is flagged
  (user preference, exported via the privacy provider) and `/spawn` now sends
  `requester_email_verified=false` for it — the provisioner applies its own policy. The real
  Moodle site guest and `guest_`-prefixed accounts without a claimable shopping_cart record keep
  the previous register-first behaviour. local_shopping_cart remains a soft dependency (guarded
  by `class_exists`).

## 2026070102 (v1.3.0) — 2026-07-01

### Added
- `oneclick.list_instances` agent skill (read-only, R0): lists the current user's own trial
  instances by wrapping the provisioner `GET /jobs` endpoint (ownership-scoped via
  `X-Requester-User-Id`, so a user only ever sees their own). Each instance is presented with its
  address, status, template, payment state and expiry; an empty list yields a friendly
  "no instances yet" reply instead of an error. Name-derived read capability
  `bookingextension/oneclick:skill_oneclick_list_instances`.

### Changed
- `oneclick.create_instance` agent skill: `template_id` is now exposed to the planner **only when
  more than one template is configured**. With a single template the field is hidden, so the
  selection planner no longer asks for a template it does not need — the request goes straight to
  confirmation and preflight auto-resolves the only template. With several templates the choice is
  offered as before. Fixes single-template setups still being asked "which template?" (the earlier
  preflight auto-pick alone never ran, because selection asked first).

## 2026063000 (v1.2.1) — 2026-06-30

### Changed
- `oneclick.create_instance` agent skill: when no template is named, the template is now resolved in
  preflight instead of always asking. With exactly **one** configured template it is auto-selected
  (there is nothing to choose); with **several** the list (id + description) is presented for
  selection; a named-but-unknown template still shows the list. `template_id` stays optional input.

## 2026062400 (v1.2.0)

- Baseline: one-click personal trial Moodle/Booking instance provisioning via the oneclick-provisioner
  API (`/spawn` + `/execute`), the `oneclick.create_instance` (R3) and `oneclick.delete_instance` agent
  skills, live spawn preview, SAML2 SP auto-registration, and admin configuration (templates, host
  suffix, shared secret, register URL).

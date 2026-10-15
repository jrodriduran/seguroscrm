# Insurance extensions

Insurance behavior that can be extended without editing Krayin core belongs here.
The existing legacy models still live in Lead; this package does not migrate them.

InsuranceServiceProvider binds the existing QuoteController to a subclass. All
routes, middleware, ownership checks and ACL remain in place. Only conversion is
overridden: quote locking, transactional writes, the correct pipeline won stage,
blocking playbooks, and retries that preserve existing policy and commission data.
Lead update listeners run after commit.

The conversion response keeps `status`, `message` and `quote`, and adds `policy`
so callers can inspect coverage status. A recorded sale can have pending coverage.
Provider/Marketplace submission and carrier verification remain separate concerns;
this package does not add external enrollment or payment processing.

Regression and integrated backend coverage: `tests/Feature/Insurance/PolicyConversionSafetyTest.php`.

HealthSherpaONE quoting foundation also lives here: server-side counties/quotes,
explicit household input, lead ownership, signed CRM consent and dedicated ACL.
It is disabled by default (`HEALTHSHERPA_ENABLED=false`), has no quoting UI yet,
and does not enable enrollment or policy status. The fabricated legacy bridge is
blocked through container bindings. Tests fake all provider responses:
`tests/Feature/Insurance/HealthSherpaFoundationTest.php`.
See `docs/healthsherpa-integration.md` for onboarding and pilot prerequisites.

Family coverage changes are recorded in `insurance_household_transitions` with
planned/confirmed/cancelled states, last expected covered day, review date, actor
and reference. They create internal follow-ups and can create one individual lead
per member. They never terminate coverage, alter tax household size or move old
policies/commissions. The linked case needs its own verified information and consent.
Age-26 review tasks run daily, within 90 days of the birthday or during its year;
they do not infer the coverage end date. Marketplace transitions prevent HS quotes
for the affected family applicant after the planned end date (or without a start
date). Other plan types do not block ACA quoting. Existing family CRUD is extended
to enforce ownership, mask write responses, preserve masked identifiers on edit
and disallow routine member deletion. Unsaved tax defaults are not shown as a
client eligibility result. New routes require `leads.family_transitions` and case
creation additionally requires `leads.create`; no roles are granted automatically.

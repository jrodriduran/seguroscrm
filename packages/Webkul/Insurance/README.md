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

# CHANGELOG for 2.2

This changelog consists of the bug & security fixes and new features being included in the releases listed below.

## **v2.2.7 (unreleased)**

* [enhancement] Dashboard redesign. "Open leads by stage" becomes a pipeline funnel for the selected pipeline: stages in funnel order with step badges, progress bars, count and share, late / attention chips and longest-untouched time per stage (from the follow-up center), value at stake for users with financial permission, won / lost and win rate for the period, and links to the pipeline. KPI tiles get icons and accents, and dashboard cards share one elevated style in light and dark mode.

* [fixed] Fixed the dashboard console error "Cannot read properties of undefined (reading createLinearGradient)" from the open-leads widget drawing on a missing canvas; the widget also mixed every pipeline and ignored funnel order.

* [enhancement] One settings menu: system configuration (General, Email, Magic AI, Security, Follow-up & Teamwork) now appears as a "System" block inside Settings, and the separate Configuration entry is removed from the sidebar.

* [feature] Agency time zone (Configuration > General > Time Zone) applied to every request, scheduled command and business-hours calculation, with an option to follow each user's computer time zone and a "Detect" button. Defaults to America/New_York instead of Krayin's Asia/Kolkata.

* [enhancement] Unified buttons: primary buttons use the indigo gradient (Krayin's #0E90D9 default blue is replaced unless the agency chose its own brand colour), secondary buttons a white outline, all with the same height, radius and weight, including the ad-hoc blue buttons of the insurance screens.

* [enhancement] Rows and cards on any screen that hold a single Edit / View action now open the record when clicked, with a hover highlight (e.g. Hierarchy & Overrides cards).

* [enhancement] Emoji used as icons across 35 screens and more icon-font glyphs (dark/light mode, list, calendar, search, add, close, refresh…) are replaced by line icons matching the rest of the interface.

* [feature] @mentions and record following: type "@" in follow-ups, threads, hand-overs and activity comments to pick a teammate (they are notified and the mention is highlighted). Anyone can follow a lead, contact, organization or quote from the new Team menu in the header, and people involved follow automatically; followers are notified when a teammate logs an activity, moves a lead to another stage, creates a follow-up or hands the case over. The follow-up center lists the records you follow.

* [feature] Team automations (Settings > Follow-up & Teamwork > Automations): when a lead comes in, a lead enters a stage (any won stage or a specific one), a policy renewal is N days away, or a case becomes overdue, create a follow-up for the case owner, the master agent or a specific person (priority, due in working days, note with {client}, {lead}, {stage}, {date}, {idle}) or just notify them. Each automation acts once per record and occasion. Four starters ship paused. Date-based triggers run hourly through `teamwork:run-automations` (requires the Laravel scheduler to be running).

* [feature] Team notifications: a bell in the header (unread counter refreshed every minute, pulses for urgent items, count in the tab title) and a notifications page, raised when someone assigns you a follow-up or marks it urgent, comments on a thread you are in, closes it, hands you a case, or sends you a note.

* [feature] Notes between teammates on any lead, contact, organization or quote: the recipient is notified, opening the note records a read receipt the sender can see, and the recipient can turn it into a follow-up (the sender is told) or keep it as a note, or reply. Each record has a Team activity page with its notes and follow-ups, and the follow-up center lists unread notes and sent notes still unread.

* [feature] Communications package: each contact has a Communications panel (header shortcut on the contact page) merging emails, calls, meetings, notes and Chatwoot chat messages linked to their leads into one timeline, filterable by channel and direction, searchable and sortable. The Mail menu is renamed Communications.

* [security] Added a "Financial information" permission: without it, revenue widgets are hidden from the dashboard and revenue statistics are refused. Assistants and front desk do not have it; the auditor role does.

* [enhancement] Teamwork is now open collaboration instead of isolation: every teammate gets the Team tab with a per-person focus, anyone can flag follow-ups (urgent included) for any teammate, read and comment on any follow-up thread (closing stays with the assignee, the requester or the owner), and hand a case over to a teammate from the follow-up center or the lead page (new owner, timeline note and a follow-up with the context). The lead pipeline opens on the user's own cases the first time, with teammates one filter away. Urgent activities and SLA escalations from Team Radar now appear in the follow-up center, and the old My Pending page redirects there. The full-permission role is renamed "Master Agent (dueño)".

* [security] Closed permission gaps for custom roles: policies, commissions, hierarchy, analytics and saving configuration had no ACL entries, so any signed-in user could open or change them by URL. They now have view / edit permissions, and a route guard maps every other admin route to its module permission for custom roles (reads need the module permission, writes need edit or delete, unknown writes are denied). Full-permission roles are unaffected.

* [feature] Added starter roles for independent agencies next to the owner's Administrator role (master agent): Agente asistente (works the book, no deletes, commissions or settings), Auditor (cumplimiento) (read-only, including the access log) and Recepción (solo consulta) (front desk: looks up clients, leads and policies, logs calls and flags follow-ups).

* [feature] Added a Teamwork package that turns the CRM into a follow-up workspace: a Follow-up center (sidebar) with "My work" (urgent items, my follow-ups, and open cases ranked by business hours without a touch) and, for supervisors, "My team" (per-agent summary, late and at-risk cases, late or urgent follow-ups). Any lead, contact, organization or quote can be flagged from a "Follow up" button in the header, for oneself or a teammate, with optional due date and note; supervisors can mark items urgent for their agents. Each follow-up has a shared conversation thread, close and reopen, and history per record. The master agent is the full-permission user at the top of the agency hierarchy and sees the whole agency; other supervisors see their full downline. Late rules are configurable per pipeline or stage (Settings > Follow-up & Teamwork > Late Rules) on top of business hours and default limits in Configuration > General > Follow-up & Teamwork. The dashboard opens with a follow-up strip.

* [security] Added two-step verification with authenticator apps (TOTP): self-service setup with QR code and recovery codes under Account Security, sign-in challenge with replay protection and rate limiting, optional trusted browsers, a policy to require it for administrators or everyone (Configuration > General > Security), and an admin page to see who has it on and reset it for a lost phone (Settings > Security > Two-Factor Authentication). Secrets and recovery codes are encrypted at rest and every 2FA event is written to the access log.

* [security] Added a Security package with a sign-in audit log (Settings > Security > Access Log: who signed in or out, failed attempts and blocked IPs, with date, IP address and device) and an optional admin IP allowlist (Configuration > General > Security) that accepts single IPs or CIDR ranges, always allows loopback, can exempt administrators, and has an emergency `php artisan security:disable-ip-restriction` switch.

* [feature] Nexus 2027 admin shell: dark sidebar rail with module-tinted open states, flyout submenus anchored to the row that opens them (caret, viewport clamping, hover-intent delay, click and keyboard support), and an auto-hide mode where the rail peeks in from the left edge or the header trigger and can be pinned back (`sidebar_auto` cookie). Shell styles moved to `components/layouts/theme.blade.php` in `<head>` because Vue strips `<style>` tags inside `#app`.

* [feature] Workspace refinements: aurora canvas with fading dot grid, softer card elevation, accent focus halos on form fields, row hover wash, lighter secondary buttons and a subtle page entrance animation. Tinted sticky page header band, card tint, datagrid header band, zebra rows with accent hover marker, clickable datagrid rows (any cell opens the row's view action, or edit when there is no view), softer blurred modal backdrop, and corner-anchored modals (Add Activity) lifted off the bottom edge with internal scroll. Bold icon-font glyphs (view, edit, delete, print, mail, settings and configuration cards, note, activity, file) redrawn as thin line icons through CSS masks; list actions become tinted chips on hover and settings cards get colour-coded icon tiles.

* [feature] Daily Action Board (Agent Morning Priority Hub - Historia 4.1): Implemented unified operational priority dashboard (`DailyActionBoardService` and `DailyActionBoardController`) that greets agents with 4 actionable urgency queues: (1) Impending binder payments on active applications; (2) Critical DMI document inconsistencies (< 15 days) before APTC subsidy revocation; (3) Federal grace period policies (Month 1 vs Months 2-3 past due with claims pended and clawback exposure); (4) Impending 60-day SEP / OEP deadline expirations. Includes financial exposure tracking (`revenue_at_risk_amount`), individual vs all-agency filtering, and 1-click action buttons (WhatsApp, call, policy review) at `/admin/insurance/action-board`.

* [feature] Revenue Shield - Continuous Commission Recovery Engine (Historia 4.2): Built algorithmic revenue audit system (`RevenueShieldService` and `RevenueShieldController`) continuously cross-referencing active policies against reconciled commission statements over the last 60 days. Flags policies active for > 45 days with zero carrier commission credits (`missing_commission_flag`), calculates estimated unpaid PMPM commission volume, tracks detection timestamps and uncollected days, generates carrier-by-carrier discrepancy breakdowns, and provides dedicated administrative dispute claim resolution workflows (`/admin/insurance/revenue-shield`).

* [feature] Agency Multi-Tenancy & SaaS Data Isolation Architecture (Historia 4.3): Added multi-tenant partitioning for independent insurance agencies (`agencies` table and `BelongsToAgency` trait). Enforces database-level row isolation via Eloquent Global Scope (`AgencyScope`), guaranteeing that users belonging to Agency A cannot query, view, or modify leads, policies, or carrier statements from Agency B, with automatic `agency_id` stamping on new record creation.

* [migration] Added `missing_commission_flag`, `missing_commission_detected_at`, `missing_commission_amount`, `missing_commission_days`, and `missing_commission_notes` to `insurance_policies` table (Lead migration `2026_09_18_000026`). Created `agencies` table and added `agency_id` foreign key columns to `users`, `leads`, `insurance_policies`, and `carrier_statements` tables (Lead migration `2026_09_18_000027`).

* [tests] Added Pest feature tests `DailyActionBoardTest`, `RevenueShieldTest`, and `AgencyMultiTenancyTest` covering urgency queue aggregation, revenue-at-risk calculations, algorithmic commission discrepancy audits, manual dispute resolution, and strict multi-tenant agency data isolation.

* [feature] Intelligent Lead Routing by Active State License & Language (Historia 3.1): Enhanced `AssignmentEngine` to enforce mandatory state DOI & CMS licensing compliance prior to lead assignment. Filters the agent pool by active, non-expired licenses in `user_agent_licenses` matching the lead's state and health line of authority (`'health'` / `'aca'`). Incorporates preferred language matching (ES / EN) prioritizing bilingual agents, with capacity management (`least_loaded` / `round_robin`), fallback routing, and audit failure tracking (`no_licensed_agents_in_state`, `max_capacity_exceeded`). Added `auditLeadAssignment` diagnostic inspection API and `reassignWithCompliance` helper.

* [feature] Omnichannel Chatwoot TCPA Consent Control & Express Authorization Gate (Historia 3.2): Implemented strict regulatory compliance enforcement under the Telephone Consumer Protection Act (47 U.S.C. § 227). Blocks outbound SMS and WhatsApp messaging from Chatwoot when express customer consent is missing, rejecting unconsented attempts with HTTP 422 and throwing `TcpaConsentRequiredException`. Added dedicated administrative TCPA capture endpoint (`/admin/leads/{id}/chatwoot/tcpa-consent`) supporting web opt-in, recorded verbal authorization, signed consent documents, and SMS keyword proofs with immutable CRM activity logging. Integrated visual TCPA status badges, compliance warning banners, and 1-click consent capture modal in the live chat inbox (`chatwoot_inbox.blade.php`) and compliance card (`compliance_card.blade.php`).

* [migration] Added `state_code`, `preferred_language`, `assignment_failure_reason`, `has_tcpa_consent`, `tcpa_consented_at`, `tcpa_consent_type`, and `tcpa_consent_proof` to `leads` table, and `spoken_languages` to `users` table (Lead package migration `2026_09_18_000025`).

* [tests] Added Pest feature tests `IntelligentRoutingLicenseLanguageTest` and `ChatwootTcpaComplianceTest` covering state license and authority validation, bilingual agent routing, license expiration filters, unroutable state diagnostics, TCPA message blocking, express consent capture, and exception enforcement.

* [feature] Agent Commission Ledger & Running Balance Management (Historia 2.4): Implemented double-entry accounting ledger system for agency writing agents (`agent_commission_balances` and `agent_commission_ledger_transactions`), tracking real-time running balances, cumulative earnings, deductions, and payouts with database-level row locking.

* [feature] Automated Clawback Debt Amortization & Negative Balance Alerts: Built automated chargeback recovery engine offsetting cancellation debits directly against agent commission accruals. Flags negative account balances (`negative_balance_alert`), prevents over-disbursements beyond available net funds, and links chargebacks directly from carrier reconciliation statements. Added dedicated Ledger administrative interface (`/admin/insurance/ledger`).

* [migration] Added `agent_commission_balances` and `agent_commission_ledger_transactions` tables (Lead package migration `2026_09_18_000024`).

* [tests] Added Pest feature test `AgentCommissionLedgerTest` covering running balance initialization, clawback debt amortization, negative balance safeguards, disbursement validation, and HTTP audit endpoints.

* [feature] Carrier Statement Idempotency & SHA-256 Anti-Duplication Engine (Historia 2.3): Built cryptographic protection preventing double-payouts and duplicate statement processing in commission reconciliation. Calculates SHA-256 hashes of statement files (`file_hash`), rejecting re-uploaded statements with HTTP 409 Conflict.

* [feature] Line-Item Duplicate Lock & Intra-batch Guard: Implemented multi-layered duplicate detection blocking multiple commission credits for the same carrier, policy number, and service month (`[carrier_name, policy_number, period_month]`). Flags duplicate payouts as `duplicate_blocked`, prevents double ledger accounting, and references the original payment item (`duplicate_of_item_id`).

* [migration] Added `file_hash`, `duplicate_records`, and `total_duplicate_amount` to `carrier_statements` table, plus `period_month`, `is_duplicate`, and `duplicate_of_item_id` to `carrier_statement_items` table (Lead package migration `2026_09_18_000023`).

* [tests] Added Pest feature test `CommissionIdempotencyTest` covering cryptographic SHA-256 upload rejection, inter-statement policy duplicate blocking, intra-batch duplicate line isolation, and legitimate multi-month recurring payout processing.

* [feature] OEP Renewal Center & Year-Over-Year Plan Comparator (Historia 2.2): Implemented agency Open Enrollment Period (OEP) renewal campaign control center (`PolicyRenewalService` and `PolicyRenewalController`), tracking cohort persistency rates, retained gross/net volume, and policy renewal taxonomy (`same_plan`, `same_carrier_switch`, `cross_carrier_switch`).

* [feature] Year-Over-Year Comparative Audit Engine & Live Variance Preview: Added side-by-side plan comparator (`getYearOverYearComparison`) evaluating prior year base policies against renewed coverage across carriers, plan benefits, deductibles, max out-of-pocket (MOOP), gross premiums, and APTC subsidies. Automatically flags client monthly net savings, premium increases, and critical subsidy drop warnings (> $50/mo reduction). Built interactive modal comparator in Book of Business (`policies/index.blade.php`).

* [migration] Added `plan_year`, `deductible`, `max_out_of_pocket`, `renewal_type`, `renewal_cohort_year`, and `renewal_notes` to `insurance_policies` table (Lead package migration `2026_09_18_000022`).

* [tests] Added Pest feature test `OepRenewalComparatorTest` covering renewal cohort analytics, parent policy chaining via `prior_policy_id`, $0 premium auto-effectuation, cross-carrier binder requirements, and year-over-year variance computation.

* [feature] Post-Sale Service Cases & Client Self-Service 1095-A Portal (Historia 2.1): Implemented comprehensive post-sale service ticketing for active health policies (`policy_service_cases` and `policy_service_case_comments`), supporting automated ticket sequencing (`CAS-YYYY-XXXXXX`), category taxonomy (1095-A tax statement requests, address changes, Marketplace income updates, PCP assignments, ID card replacements, and claims/billing inquiries), priority levels, multi-file attachments, and client visibility sharing.

* [feature] Self-Service 1095-A Tax Form Workflow & Document Vault: Enabled insured clients to request 1095-A tax statements directly from their digital policy portal (`/my-policy/{token}/request-1095a`) and download secure tax documents once verified and uploaded by the agency. Added administrative case management modal in Book of Business ledger (`policies/index.blade.php`).

* [migration] Added `policy_service_cases` and `policy_service_case_comments` tables (Lead package migration `2026_09_18_000021`).

* [tests] Added Pest feature test `PolicyServiceCaseTest` covering administrative ticket generation, file attachments, status resolution transitions, client portal self-service 1095-A requests, and permission-checked document downloads.

* [feature] HHS Federal Poverty Level (FPL) Calculator & Silver CSR Subsidies Engine (Entrega 1 Cerrada): Implemented official HHS poverty guidelines calculation engine (`FplCalculatorService`) evaluating tax household size, projected annual income (MAGI), and state geography to automatically determine % FPL, classify Cost-Sharing Reduction tiers (Silver CSR 94%, 87%, 73%, Standard, Medicaid Gap), calculate ACA/IRA maximum family contribution percentages, and estimate monthly APTC subsidies with $0 net premium qualification indicators.

* [feature] Tax Household Persistence & Real-time Live Preview: Added `lead_tax_households` table and interactive UI calculator in lead details (`household.blade.php`), enabling agents to update projected annual income, simulate family size variations with auto-sync from census dependents, and preview subsidy impacts in real-time.

* [feature] HIPAA Sensitive PII Encryption & Masked SSN Access Control: Implemented at-rest encryption via `Crypt::encryptString` for Social Security Numbers (SSN/ITIN) in `household_members`, default masked rendering (`***-**-1234`) across API endpoints and views, backwards compatibility with legacy plaintext entries, and strict permission-controlled reveal workflow (`leads.view_sensitive_pii` ACL gate).

* [feature] CMS 10-Year Immutable Consent Versioning & Cryptographic Integrity: Added immutable versioning engine for CMS 45 CFR § 155.220 compliance (`lead_consent_versions`). Captures client electronic signature, timestamp, IP address, user-agent, and SHA-256 tamper-evident integrity hash. Superseded and revoked consents preserve permanent historical evidence rather than overwriting records. Added 10-year CMS audit JSON export endpoint (`/admin/leads/{id}/consent/audit-export`).

* [feature] Coverage Effectuation Pipeline & Binder Payment Tracking: Separated sales conversion from policy activation by introducing `binder_pending` state and tracking initial binder payment confirmation number, payment method (carrier portal, credit card, ACH, phone, check), and payment timestamp. Policies with $0 net premium automatically effectuate as `waived_zero_premium`, while positive premium plans require binder payment verification.

* [feature] Policy Coverage Status History & Audit Trail: Implemented granular transition history (`policy_coverage_status_histories`) tracking chronological coverage state movements, effectuation dates, binder payment logs, and cancellation reasons for carrier audits and federal review (`/admin/policies/{id}/coverage-history`).

* [migration] Added `lead_tax_households` table (`2026_09_18_000020`), `lead_consent_versions` table (`2026_09_18_000018`), and binder/effectuation columns to `insurance_policies` plus `policy_coverage_status_histories` table (`2026_09_18_000019`).

* [tests] Added Pest feature tests `FplEligibilityTest`, `SensitivePiiComplianceTest`, `ConsentVersioningAuditTest`, and `CoverageEffectuationBinderTest` covering HHS poverty calculations, Silver CSR tier brackets, PII encryption, ACL gates, 10-year CMS consent versioning with SHA-256 verification, quote binder status routing, and effectuation transitions.

* [feature] HealthSherpa 1-Click Enrollment Bridge: Implemented instant deep-link prefill integration (`/admin/leads/{id}/healthsherpa/redirect`) connecting Krayin CRM directly to HealthSherpa EDE, pre-populating applicant demographics, household dependents, projected FPL income, zip code, and tobacco status to eliminate 15-20 minutes of manual re-keying per ACA application.

* [feature] Cross-Sell Bundle Engine (Dental, Vision, Hospital Indemnity & Critical Illness): Built automated gap-filling matrix evaluating ACA and Medicare out-of-pocket exposure to identify, calculate, and present lucrative supplemental protection packages with tailored client pitch scripts and projected annual agency commission tracking.

* [feature] Producer Licensing, State Authority, AHIP & E&O Compliance Tracker: Added centralized producer compliance management tracking National Producer Numbers (NPN), resident vs non-resident state licenses, annual CMS AHIP Medicare certifications, and Errors & Omissions (E&O) policy liability limits with automated expiration alert audits via console command (`insurance:check-agent-compliance`).

* [feature] AI Client 360° Narrative Snapshot: Built instant 5-second client briefing engine synthesizing household demographics, active health quotes, CMS consent, Medicare SOA compliance, DMI deadlines, prescription drug counts, and next best action into an executive summary drawer before agent calls.

* [migration] Added `lead_cross_sell_opportunities` and `user_agent_licenses` tables (Lead package migrations `2026_09_18_000016` and `2026_09_18_000017`).

* [tests] Added Pest feature test `DeepCompetitiveFeaturesTest` covering HealthSherpa payload formatting, cross-sell bundle evaluation, producer compliance audits, and AI client 360 narrative synthesis.

* [feature] Omnichannel Chatwoot Integration (WhatsApp, SMS, Live Chat): Added enterprise integration connecting Krayin CRM to Chatwoot for centralizing all client conversations across WhatsApp Business, Twilio SMS, and Webchat.

* [feature] Bidirectional Webhook Sync: Built `POST /api/chatwoot/webhook` listener handling customer `message_created` events to automatically create or match CRM leads, log full message conversations into CRM activities, and update real-time response timestamps.

* [feature] Embedded Chatwoot Inbox Drawer in Lead View: Implemented interactive live conversation drawer inside Lead details allowing agents to read conversation history, view sender message bubbles, and send instant WhatsApp/SMS replies directly from the CRM without switching applications.

* [migration] Added `chatwoot_conversation_id`, `chatwoot_inbox_id`, and `chatwoot_last_message_at` to `leads` table and `chatwoot_contact_id` to `persons` table (Lead package migration `2026_09_18_000015`).

* [tests] Added Pest feature test `ChatwootIntegrationTest` covering contact synchronization, conversation creation, inbound webhook processing, and outbound messaging.

* [feature] AI Health Lead Scoring & Next Best Action Copilot: Added predictive AI lead scoring service (`LeadAiScoringService`) evaluating multidimensional health insurance purchase intent (SEP expiration urgency, aging-in to Medicare at 65 IEP window, APTC subsidy potential, CMS compliance readiness, and DMI document lapse risk).

* [feature] Dynamic Next Best Action Card in Lead Sidebar: Implemented interactive agent recommendation card highlighting the single most urgent and profitable action to close or protect coverage, with one-click direct navigation to the relevant lead workflow.

* [feature] AI Insights JSON API: Added `/admin/leads/{id}/ai-insights` endpoint for instant score recalculation, lead tier classification (Hot, Warm, Nurture), and weighted factor breakdowns.

* [tests] Added Pest feature test `LeadAiScoringTest` covering SEP urgency detection, Medicare 65 age-in tracking, DMI deadline alerts, and JSON scoring output.

* [feature] Executive Analytics & Book of Business Valuation Dashboard: Added dedicated agency business intelligence control tower (`/admin/insurance/analytics`) computing market valuation multiples (1.5x Conservative, 2.0x Standard Market, 2.5x High-Growth ARR), annualized gross premium volume, covered lives growth, and real-time persistency rates.

* [feature] Carrier Market Share & Segmentation Analytics: Implemented visual breakdown of active policies, covered lives, and monthly premium volume across health carriers (Florida Blue, Ambetter, Oscar, UnitedHealthcare, etc.), metal tiers (Bronze, Silver CSR, Gold, Platinum), and network types (HMO, EPO, PPO).

* [feature] Producer Leaderboard & OEP Season Tracker: Built agency sales leaderboard ranking producers by active policies, covered lives, volume, and persistency percentage alongside an Open Enrollment Period (OEP) season target progress meter.

* [feature] Executive CSV Valuation Export: Added one-click export for board presentations, banking compliance, and M&A portfolio audit (`Valuacion_Cartera_Ejecutiva_*.csv`).

* [tests] Added Pest feature test `ExecutiveAnalyticsTest` covering valuation multiple computations, ARR scaling, JSON analytics, and CSV streaming.

* [feature] Prescription Formulary (Rx Collect) & Drug Tier Engine: Added comprehensive prescription drug tracker directly in Lead details supporting drug tier classification (Tier 1 Preferred Generic to Tier 5 Specialty), utilization management restrictions (Prior Authorization [PA], Step Therapy [ST], Quantity Limits [QL]), and real-time copay estimation for 30-day retail and 90-day mail-order dispensing.

* [feature] Healthcare Provider & Doctor Network Lookup: Implemented provider network tracking allowing agents to catalog clients' doctors, specialists, clinic/hospital affiliations, 10-digit NPI numbers, Primary Care Physician (PCP) designations, and carrier in-network vs out-of-network status mapping (Florida Blue, Ambetter, Oscar, UnitedHealthcare, etc.).

* [feature] Printable Rx & Provider Network Summary PDF: Added downloadable and audit-ready PDF summary document (`Resumen_Medicinas_Doctores_{id}_{client}.pdf`) compiling all patient prescriptions, copays, and in-network physicians for enrollment verification and client records.

* [migration] Added `lead_rx_medications` and `lead_doctor_networks` tables (Lead package migration `2026_09_18_000014`).

* [tests] Added Pest feature test `RxProviderNetworkTest` covering formulary restrictions, copay calculation, carrier network mapping, PCP designation, and PDF generation.

* [feature] Insured Self-Service Portal & Digital Health Card: Added public mobile-friendly beneficiary self-service portal (`/my-policy/{token}`) allowing insured clients to access their digital member card, plan details, copay summaries, primary care physician (PCP), and covered household dependents anytime without agent intervention.

* [feature] Printable 2-Sided Wallet ID Card PDF: Implemented downloadable wallet-sized insurance card PDF rendering carrier logo, Member ID, RxBIN, RxPCN, RxGrp, emergency contacts, 24/7 NurseLine, and copay breakdowns for medical visits and pharmacy dispensing.

* [feature] Client Document Self-Upload (DMI / Income / Citizenship): Beneficiaries can directly photograph or upload documents (proof of income, citizenship, loss of minimum essential coverage) from their mobile device to resolve marketplace Data Matching Inconsistencies (DMIs) without emailing sensitive PII.

* [migration] Added portal tokens, member identifiers, PCP info, and document stores to `insurance_policies` table (Lead package migration `2026_09_18_000013`).

* [tests] Added Pest feature test `InsuredPortalTest` covering token access, ID card PDF download, and document upload handling.

* [feature] Agency Hierarchy & Multi-Tier Overrides Engine: Added organizational hierarchy model supporting sub-agencies, MGAs, GAs, producers, and junior downlines with automated multi-tier PMPM override distribution on policy issuance and quote-to-policy conversion.

* [feature] Organizational Tree & Downline Production Rollup: Implemented interactive agency tree with live production metrics (personal policies/lives vs team downline policies/lives) and monthly override compensation tracking.

* [feature] Sub-Agency Override Compensation Statement Export: Added direct CSV statement generator for monthly sub-agency and upline commission settlement.

* [migration] Added `agency_hierarchies` and `policy_override_distributions` tables (Lead package migration `2026_09_18_000012`).

* [tests] Added Pest feature test `AgencyHierarchyTest` covering upline chain traversal, multi-tier override payouts, and compensation statement export.

* [feature] Book of Business (Cartera) Ledger & Portfolio Persistency Engine: Added full policy lifecycle management with real-time portfolio persistency rate calculation, covered lives tracking, and monthly gross vs net premium volume KPIs.

* [feature] ACA 90-Day Grace Period Lifecycle & Lapse Warnings: Implemented automated detection of overdue payments with Month 1 (Day 1-30) and Critical Month 2-3 (Day 31-90) grace period transitions, agent high-priority alert tasks to prevent commission chargebacks (clawbacks), payment recording to restore active status, and one-click WhatsApp payment reminders.

* [feature] Automated Daily Grace Period Command: Added `insurance:check-grace-periods` scheduled daily at 07:00 to evaluate policy payment deadlines and enforce federal ACA grace period rules.

* [migration] Added `insurance_policies` table for active health and life insurance policy tracking (Lead package migration `2026_09_18_000011`).

* [tests] Added Pest feature test `BookOfBusinessTest` covering retention KPIs, grace period transitions, payment recording, and automatic policy creation upon quote conversion.

* [feature] Enrollment Period Manager & OEP Countdown (ACA & Medicare): Added global federal enrollment status tracker displaying active ACA Open Enrollment Period (Nov 1 - Jan 15) and Special Enrollment Period (SEP) operational modes across CRM leads and dashboards.

* [feature] SEP / Qualifying Life Events (QLE) 60-Day Window Validator & CMS Checklist: Implemented interactive validation engine inside Lead details that computes the mandatory 60-day enrollment window deadline, expected coverage effective date, and generates a dynamic verification document checklist based on federal CMS categories (loss of coverage, marriage, birth/adoption, permanent relocation, immigration status, income transition, FEMA emergency).

* [migration] Added `lead_sep_qualifications` table for tracking qualifying life events, enrollment deadlines, and verified document checklists (Lead package migration `2026_09_18_000010`).

* [tests] Added Pest feature test `EnrollmentPeriodTest` covering federal status detection, 60-day window deadline calculation, expired event handling, and document checklist verification.

* [feature] Side-by-Side ACA Health Plan Comparator & Multi-Plan Proposal Matrix: Added interactive comparison matrix to Lead Quotes allowing agents to contrast multiple health plans column-by-column across metal tiers (Bronze, Silver CSR, Gold, Platinum), network types (HMO, EPO, PPO), gross vs APTC tax credits, net monthly costs, annual deductibles, MOOP, and primary care/specialist/Rx copays.

* [feature] Professional PDF Proposal Generator: Implemented printable and downloadable multi-plan comparison PDF proposal via `PDFHandler` featuring agency branding, agent NPN, federal subsidy calculation explanations, and signature approval lines.

* [feature] Interactive Beneficiary Comparison & Plan Selection Portal: Created public mobile-responsive client portal (`/proposal/{token}`) where beneficiaries can review presented options, view copays and savings, and select their preferred health plan with a single tap, automatically updating CRM quote status and notifying the agent.

* [migration] Added `health_plan_proposals` table for tracking side-by-side proposals, client views, and plan selections (Quote package migration `2026_09_18_000002`).

* [tests] Added Pest feature test `HealthPlanProposalTest` covering proposal generation, PDF export, client portal viewing, and remote plan selection.

* [feature] Medicare Scope of Appointment (SOA) Compliance Engine: Added dedicated Medicare SOA digital workflow complying with federal CMS regulations, featuring a public mobile-friendly touchscreen signature portal, mandatory CMS TPMO disclaimer, product discussion authorizations (Medicare Advantage Part C, Part D Rx, Medigap, Dental/Vision, Hospital Indemnity), and audit-ready CMS compliance certificates with PDF download.

* [feature] CMS 48-Hour Waiting Period Tracker & Live Countdown: Built real-time compliance tracker in Lead details calculating the mandatory 48-hour cooling-off window between beneficiary signature and consultation eligibility, along with documented CMS exception handling (beneficiary walk-ins and end of enrollment period deadlines).

* [migration] Added `lead_medicare_soas` table for Medicare Scope of Appointment tracking and electronic audit trails (Lead package migration `2026_09_18_000009`).

* [tests] Added Pest feature test `MedicareSoaTest` covering public portal, electronic signature, CMS 48-hour rule calculation, exception submission, and certificate PDF generation.

* [feature] Automated Carrier Commission Reconciliation & Missed Commissions Engine: Added mass CSV/Excel statement processing with fuzzy and exact matching against active CRM policies, variance detection, clawback/chargeback tracking, and automatic identification of unpaid policies (*Missed Commissions*) with dispute export capability.

* [migration] Added `carrier_statements` and `carrier_statement_items` tables for carrier commission reconciliation audits (Lead package migration `2026_09_18_000008`).

* [tests] Added Pest feature test `StatementReconciliationTest` covering exact matching, variance, orphan detection, chargebacks, and missed commissions.

* [feature] Insurance Commissions Module (PMPM / ACA Health): Added dedicated commissions ledger and dashboard with live projection KPIs (gross monthly, agent net earnings, agency retention spread, covered lives), customizable carrier base rates, agent split percentages, and automatic commission generation upon quote-to-policy conversion.

* [migration] Added `insurance_commission_rates` table with default ACA carrier rates (Lead package migration `2026_09_18_000006`).

* [migration] Added `insurance_commissions` table for policy commission tracking and split calculations (Lead package migration `2026_09_18_000007`).

* [feature] Automated DMI Deadline Monitoring Command: Added `insurance:check-dmi-deadlines` scheduled artisan command (daily at 08:00) that scans Marketplace 90-day verification deadlines and automatically assigns urgent/high priority alert activities with client WhatsApp copy to assigned agents.

* [feature] Direct PDF Certificate Generation: Added backend PDF rendering and download for CMS Consent Compliance Certificates via `PDFHandler`.

* [tests] Added Pest feature test suite for Insurance modules: `ConsentPortalTest`, `DmiDocumentTest`, `CheckDmiDeadlinesTest`, `HealthQuoteTest`, and `CommissionTest`.

* [feature] Digital Consent Form & Client Portal (CMS Compliance): Added client electronic consent portal replicating Apizeal CMS workflow with touch/mouse signature pad, audit metadata capture (IP address, user agent, timestamp), printable compliance certificate, and lead detail compliance card.

* [feature] 90-Day DMI Document Tracking (Data Matching Issues): Added tracking for Marketplace ACA document requirements (income proof, immigration status, identity) with expiration date countdown, document upload, status workflow (pending, submitted, verified, rejected), and deadline status badges.

* [migration] Added `lead_consents` table for CMS consent tracking and signatures (Lead package migration `2026_09_18_000004`).

* [migration] Added `lead_dmi_documents` table for Marketplace DMI documentation (Lead package migration `2026_09_18_000005`).

* [fixed] Safe route placeholder replacement in lead quotes tab preventing invalid URI generation in JavaScript handlers.

* [fixed] Added missing CSRF token meta tag to main admin layout view for AJAX operations.

* [feature] Health Insurance Quotes Transformation: Adapted Quotes module to ACA/Obamacare health insurance with carrier selection (Florida Blue, Ambetter, Oscar, Molina, UHC, Aetna), metal tier, gross premium, federal APTC subsidy deduction, and real-time client net monthly premium calculation.

* [feature] WhatsApp Proposal Generator & Direct Chat: Added instant WhatsApp proposal formatter and click-to-chat URL with preformatted client proposal summary and copays breakdown.

* [feature] Quote to Policy Conversion: Added one-click "Emitir Póliza" action that transitions quotes to 'bound', advances linked health leads to 'Policy Issued (Won)', and sets pipeline deal value to the monthly net premium.

* [migration] Added health insurance plan and subsidy columns (`carrier_name`, `plan_name`, `metal_tier`, `gross_premium`, `aptc_subsidy`, `net_premium`, `deductible`, `out_of_pocket_max`, `network_type`, copays, and `quote_status`) to `quotes` table (Quote package migration `2026_09_18_000001`).

* [feature] Added configurable SLA rules by pipeline and lead type (`lead_sla_rules` table, `SlaRuleRepository`) allowing customizable first contact window, follow-up window, and escalation threshold.

* [feature] Added dynamic lead `AssignmentEngine` with Round Robin, Least Loaded (workload balancing), and Manual triage strategies with agent pool selection and per-agent capacity limits (`lead_assignment_rules` table, `AssignmentRuleRepository`).

* [feature] Added `SlaEscalationService` and automated escalation in `insurance:check-sla` command to escalate breached leads to Master Agent, along with agent-initiated escalation requests directly from `leads/my-pending`.

* [migration] Added `lead_sla_rules` table for configurable SLA thresholds (Lead package migration `2026_09_17_000001`).

* [migration] Added `lead_assignment_rules` table for dynamic routing strategies (Lead package migration `2026_09_17_000002`).

* [migration] Added `escalated_at` and `escalation_reason` columns to `leads` table (Lead package migration `2026_09_17_000003`).

* [feature] Added `InsuranceSla` event listener to auto-stamp `assigned_at`, reset `sla_status` to `pending`, and create a first-contact SLA activity based on configurable pipeline rules whenever a lead is created or reassigned.

* [migration] Added `sla_status`, `sla_hours`, and `assigned_at` columns to the `leads` table (Lead package migration `2026_09_16_000001`).

* [migration] Added `priority` and `sla_activity_status` columns to the `activities` table (Activity package migration `2026_09_16_000002`).

## **v2.2.6 (10th of Sept 2026)**

* [feature] Added MariaDB support.

* [feature] Added customizable lead card information. Datagrid columns and Kanban lead card fields can now be chosen per user through new column and card settings components.

* [feature] Added PDF export for dashboard reports, available from the dashboard alongside the existing views.

* [feature] Added Japanese (`ja`) translation for the Admin, Installer, GoogleContact and WebForm packages.

* [feature] Added an associated group column to the users grid in Settings > Users.

* [fixed] Fixed duplicated activity handling across the lead, person, product and warehouse activity controllers by consolidating the shared logic, and added the missing Japanese activity translations.

* [fixed] Fixed the data transfer import queue processing inconsistently, and corrected the form control group rendering used by the import screen.

* [fixed] Fixed the Google Contact settings screen erroring when no account was connected, and added the corresponding translations.

* [fixed] Fixed the mail ACL mapping so email actions are checked against the correct permission.

* [fixed] Fixed people created from a lead being saved with a null `user_id`, which hid them from the person listing for users restricted to group or individual data scope. They are now assigned to the lead owner, falling back to the acting user.

* [fixed] Fixed the stage API resource omitting `lead_pipeline_id`, so stages could not be matched to their pipeline.

* [fixed] Fixed email attachment downloads being blocked by the URL sanitizer middleware.

* [fixed] Added the missing Chinese translations for the users grid's associated group column.

* [fixed] Fixed flaky admin end-to-end tests around organization owner lookup, lead creation and rich-text comment fields.

* [security] Fixed user and role listings not being scoped by the acting user's data scope, which allowed users to see records outside their own group. Roles now track their creator via a new `created_by` column.

* [security] Hardened the admin ACL middleware to fail closed. An administrative route with no ACL mapping is now denied rather than allowed, inheriting the permission of its nearest mapped ancestor, with a narrow allow-list for authentication, self-service account management and generic UI helpers.

* [security] Removed SVG from the allowed upload types for the admin logo and favicon configuration fields.

* [security] Fixed a security issue configuration file uploads.

* [security] Fixed a security issue activity notes and the TinyMCE editor component.

* [security] Fixed a security issue in the web form embed view.

* [security] Fixed a security issue in attribute downloads.

* [security] Fixed a security issue in mail links.

* [security] Fixed broken tag handling in the tag settings controller.

* [security] Fixed SVG sanitization bypasses in media and configuration file uploads.

* [security] Secured installer APIs.

## **v2.2.5 (4th of Aug 2026)**

* #2631[fixed] Fixed the persons CSV import creating duplicate records and dropping select attribute values on re-import. Existing people are now updated by matched email regardless of a changed phone or organization (so the reported count is accurate), and select/multiselect option labels (or ids) are resolved to their option ids instead of being stored as `0`.

* #2630[fixed] Fixed date attributes in the persons CSV import silently saving as `0000-00-00`. Spreadsheet serial numbers and regional formats such as `DD/MM/YYYY` are now normalised to a valid date, and a value that cannot be parsed is reported as a row error instead of being stored as a zero date.

* [feature] Added Chinese (Simplified) `zh_CN` translation for the Admin, Installer, DataTransfer, WebForm and Core packages.

* [feature] Added a configurable default dashboard date range — 1 month, 3 months, 9 months, 1 year, 2 years or a custom number of days — under Configuration > General > Settings > Dashboard Configurations.

* [fixed] Fixed menu item names set in Configuration not applying to section pages, breadcrumbs and the mobile sidebar. Previously only the desktop sidebar reflected a rename.

* [fixed] Fixed renaming the "Mail" and "Contacts" menu items having no effect anywhere, as their configuration fields did not match the actual menu keys.

* [fixed] Fixed the dashboard date range label omitting the year on ranges spanning more than one calendar year, which rendered as "30 Jul - 30 Jul".

* [fixed] Fixed Arabic DataTransfer translations never loading, as the file was named `ar/ar.php` instead of `ar/app.php`.

* [fixed] Fixed the missing Korean translation for the "None" input validation option on the create and edit attribute forms.

* [enhancement] Moved the Core and DataTransfer package translations into the Admin package. Only packages that ship their own Blade views now carry a `Resources/lang` directory.

* [enhancement] Reduced database queries on every admin page by loading the configured menu names in a single query instead of one per menu item.

* [enhancement] Documented the localization convention in the `crm-package-development` agent skill and in AGENTS.md.
* [feature] Added a collapse/expand toggle to the admin sidebar, matching the Bagisto admin. The choice is remembered across page loads, and page content now reflows to the sidebar width instead of being overlaid by it. The sidebar no longer expands on hover; it is controlled by the toggle only.
* #2614[security] Fixed unauthenticated installer access and executable email attachment upload vulnerabilities.

* #2612[feature] Added import and export support for custom attributes for Leads and Persons.

* #2609[feature] Added Google Contacts export for Persons with Google account connection, duplicate detection, queued export progress, and result summary.

* #2608[fixed] Added missing "none" key to the Korean locale for attribute validation.

* #2606[feature] Added a collapse/expand toggle to the admin sidebar, matching the Bagisto admin. The choice is remembered across page loads, and page content now reflows to the sidebar width instead of being overlaid by it. The sidebar no longer expands on hover; it is controlled by the toggle only.

* #2606[feature] Added an option to show or hide the "Powered by" bar under Configuration > General > Settings > Powered by Section Configurations.

* #2603[feature] Added Korean translations for the Installer, DataTransfer, WebForm, and Core packages.

* #2602[feature] Added Korean translation support for the Admin package.

* #2600[fixed] Fixed invalid activity calendar .ics date-times by emitting UTC RFC 5545 values.

* #2592[security] Fixed webhook validation to reject internal endpoint URLs.

* #2580[enhancement] Added a "None" option to input validation for text attributes.

## **v2.2.4 (20th of July 2026)** *Release*

* #2590[fixed] Fixed page does not refresh after creating a record via Quick Add.

* #2589[fixed] Fixed Quick Add not working for users with group and individual permissions.

* #2582[fixed] Fixed pipeline field visible on public webform.

* #2581[enhancement] Fixed responsive UI issues when page is zoomed.

* #2579[feature] Allow group selection for individual view permission users.

* #2575[enhancement] Added previous month's sales update in Kanban view.

* #2573[enhancement] Added dashboard support for multiple pipelines.

* #2572[enhancement] Added filter by tag option in Contacts > Persons.

* #2571[fixed] Fixed issue with lead creation.

* #2570[fixed] Fixed auto-fill lead email issue.

* #2567[fixed] Fixed IDOR agent record access control vulnerability.

* #2563[fixed] Fixed Kanban infinite scroll duplicates issue.

* #2583[fixed] Fixed SQL injection in rotten lead filter.

* #2585[security] Fixed unrestricted file upload vulnerability (CVE-2026-38526).

* #2559[fixed] Fixed agent record access control issue.

* #2556[fixed] Fixed installation config save issue.

* #2550[fixed] Fixed Kanban infinite scroll duplicates.

* #2549[enhancement] Added support tab feature.

* #2548[enhancement] Allow search by phone and email when creating a lead.

* #2546[feature] Quick Attribute now available at lead form.

* #2545[feature] Added agent skills functionality.

* #2544[enhancement] Added validate skills.

* #2543[enhancement] Added Agents Skills folder.

* #2542[fixed] Fixed stored XSS vulnerability in notes field.

* #2541[fixed] Fixed quote description truncation issue.

* #2539[fixed] Fixed lost revenue arrow UI issue.

* #2538[fixed] Fixed missing translations.

* #2501[fixed] Fixed sales owner not saved in organization.

* #2500[fixed] Fixed activities date filter range issue.

* #2479[fixed] Fixed textarea field not rendered in WebForm.

* #2471[fixed] Fixed missing translations for lead won/lost modal.

* #2420[fixed] Added missing mega search translations for settings and configurations.

* #2533[fixed] Fixed GUI installation issue.

* #2419[security] Fixed stored XSS vulnerability in notes field.

* #2454[fixed] Fixed quote description truncation.

* #2407[fixed] Fixed missing translations.

* #2157[fixed] Fixed auto-fill lead email when creating a lead.

* #2258[fixed] Fixed issue with same-as-billing-address field.

## **v2.2.3 (1st of May 2026)** *Release*

* [fixed] Pipline critical issue resolved.

## **v2.2.2 (1st of May 2026)** *Release*

* [fixed] Update Change Log and version.

## **v2.2.1 (1st of May 2026)** *Release*

* [fixed] Quote fields now auto-fill correctly when a quote is linked to a lead.

* [fixed] Fixed price formatting issue.

* [fixed] Fixed Lead Kanban list ordering.

* [fixed] Fixed header block position at the top.

* [fixed] Updated Activity UI.

* [fixed] Admins can now view and share quote details to a person from the quote list.

* [fixed] Fixed submission issue on the web form.

* [fixed] Fixed activity display issue in the Calendar view.

* [fixed] Logo update issue resolved.

* [enhancement] Drag-and-drop support added to Activity. Admins can now change date and time directly from the Calendar view.

* [feature] Quick App feature added for faster access to key CRM actions.

* [feature] Admins can now add or update a person directly from the lead view page.

* [security] Resolved an authentication bypass vulnerability caused by improper access control in the installer.

## **v2.2.0 (17th of March 2026)** *Release*

* **[Laravel 12 Upgrade]** Upgraded framework to Laravel 12

* #2480[enhancement] Codebase updates and refinements.

* #2478[enhancement] Improved class instantiation handling.

* #2472[enhancement] Upgrade to Laravel 12.

* #2470[enhancement] Updated auto_commits.yml configuration.

* #2469[enhancement] General enhancements and optimizations.

* #2468[enhancement] Documentation updates (MD files).

* #2450[fixed] Added ACL support for warehouses.

* #2444[fixed] Improved global search functionality for organizations.

# CHANGELOG for 2.2

This changelog consists of the bug & security fixes and new features being included in the releases listed below.

## **v2.2.7 (unreleased)**

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

{{--
    Nexus 2027 shell theme (header, sidebar rail, flyouts, canvas).

    Lives in <head> on purpose: <style> tags inside #app are stripped by Vue
    when the app mounts, so shell styles must never be declared in the body.
--}}
@php
    // Krayin ships #0E90D9 as brand colour; unless the agency picked its own, use the theme accent.
    $nxBrand = strtoupper((string) core()->getConfigData('general.settings.menu_color.brand_color'));
@endphp

<style>
    @if (! $nxBrand || $nxBrand === '#0E90D9')
        html:root { --brand-color: #4f6bff; }
    @endif

    :root {
        --nx-rail: #0a1020;
        --nx-rail-2: #0d1428;
        --nx-rail-line: rgba(148, 163, 184, 0.10);
        --nx-surface: #111a2f;
        --nx-surface-line: rgba(148, 163, 184, 0.16);
        --nx-text: #9aa6bd;
        --nx-text-strong: #eef2fa;
        --nx-muted: #5d6983;
        --nx-accent: #4f7cff;
        --nx-accent-2: #22d3ee;
        --nx-canvas: #f3f5f9;
        --nx-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* ── Canvas ─────────────────────────────────────────── */
    body { background: var(--nx-canvas); }
    /* Soft aurora glows over a dot grid that fades out below the fold */
    .nx-canvas {
        background-color: var(--nx-canvas);
        background-image:
            radial-gradient(1100px 420px at 88% -12%, rgba(79, 124, 255, 0.08), transparent 62%),
            radial-gradient(700px 320px at 30% -18%, rgba(34, 211, 238, 0.06), transparent 60%),
            linear-gradient(180deg, rgba(243, 245, 249, 0) 0px, var(--nx-canvas) 520px),
            radial-gradient(rgba(15, 23, 42, 0.07) 1px, transparent 1.2px);
        background-size: auto, auto, auto, 22px 22px;
        background-repeat: no-repeat, no-repeat, no-repeat, repeat;
        background-attachment: fixed;
    }
    .dark body, .dark .nx-canvas { background: #070b16; }

    /* ── Header ─────────────────────────────────────────── */
    .nx-header {
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.6) inset;
    }
    .nx-header::after {
        content: '';
        position: absolute;
        left: 0; right: 0; bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, var(--nx-accent) 22%, var(--nx-accent-2) 50%, transparent 85%);
        opacity: 0.55;
        pointer-events: none;
    }
    .dark .nx-header {
        background: rgba(10, 16, 32, 0.85);
        border-bottom-color: var(--nx-rail-line);
        box-shadow: none;
    }

    /* ── Buttons: one family everywhere ───────────────────
       Primary = indigo gradient, secondary = white outline, same height,
       radius and weight. Replaces Krayin's flat #0E90D9 blue, including the
       ad-hoc blue buttons of the insurance screens. */
    .primary-button,
    .secondary-button,
    .transparent-button,
    :is(button, a).bg-brandColor,
    .admin-main-content :is(button, a):is(.bg-blue-500, .bg-blue-600, .bg-sky-500, .bg-sky-600, .bg-indigo-600) {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        min-height: 36px; padding: 0 14px !important;
        border-radius: 10px !important;
        font-size: 13px !important; font-weight: 600 !important; letter-spacing: -0.005em;
        white-space: nowrap;
        transition: transform 0.15s var(--nx-ease), box-shadow 0.2s ease, background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .primary-button,
    :is(button, a).bg-brandColor:not(.rounded-full),
    .admin-main-content :is(button, a):is(.bg-blue-500, .bg-blue-600, .bg-sky-500, .bg-sky-600, .bg-indigo-600) {
        color: #fff !important;
        border: 1px solid transparent !important;
        background: linear-gradient(135deg, var(--nx-accent) 0%, #6d5dfc 100%) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 1px 2px rgba(15, 23, 42, 0.12), 0 6px 16px -8px rgba(79, 124, 255, 0.7) !important;
    }
    .primary-button:hover,
    :is(button, a).bg-brandColor:not(.rounded-full):hover,
    .admin-main-content :is(button, a):is(.bg-blue-500, .bg-blue-600, .bg-sky-500, .bg-sky-600, .bg-indigo-600):hover {
        opacity: 1 !important;
        transform: translateY(-1px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 2px 4px rgba(15, 23, 42, 0.12), 0 10px 22px -8px rgba(79, 124, 255, 0.8) !important;
    }
    .secondary-button {
        color: #334155 !important;
        background: #fff !important;
        border: 1px solid rgba(15, 23, 42, 0.12) !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05) !important;
    }
    .secondary-button:hover { color: var(--nx-accent) !important; border-color: rgba(79, 124, 255, 0.4) !important; background: #f7f9ff !important; }
    .dark .secondary-button { color: #e2e8f0 !important; background: rgba(148, 163, 184, 0.06) !important; border-color: rgba(148, 163, 184, 0.2) !important; }
    .transparent-button { border: 1px solid transparent !important; color: #475569; }
    .transparent-button:hover { background: rgba(79, 124, 255, 0.08) !important; color: var(--nx-accent); }

    /* Round "+" quick-create in the header */
    :is(button, a, div).bg-brandColor.rounded-full,
    .nx-header .rounded-full.bg-brandColor {
        background: linear-gradient(135deg, var(--nx-accent), #6d5dfc) !important;
        box-shadow: 0 6px 16px -6px rgba(79, 124, 255, 0.8);
    }

    /* Accent text/borders that used the old blue */
    .text-brandColor { color: var(--nx-accent) !important; }
    .border-brandColor { border-color: var(--nx-accent) !important; }
    .admin-main-content :is(a, button).text-blue-600:hover { color: #6d5dfc !important; }
    :focus-visible { outline: 2px solid color-mix(in srgb, var(--nx-accent) 70%, transparent); outline-offset: 2px; }

    /* ═══════════════════════════════════════════════════════
       SIDEBAR RAIL
       No transform / filter / backdrop-filter on the rail or its rows:
       any of those would trap the position:fixed flyouts inside it.
       ═══════════════════════════════════════════════════════ */
    #admin-sidebar {
        background:
            radial-gradient(260px 200px at 0% 0%, rgba(79, 124, 255, 0.16), transparent 70%),
            linear-gradient(180deg, var(--nx-rail-2) 0%, var(--nx-rail) 100%);
        border-right: 1px solid var(--nx-rail-line);
        box-shadow: 1px 0 0 rgba(255, 255, 255, 0.02) inset, 8px 0 30px -12px rgba(2, 6, 23, 0.35);
    }
    [dir="rtl"] #admin-sidebar { border-right: 0; border-left: 1px solid var(--nx-rail-line); }

    #admin-sidebar .journal-scroll::-webkit-scrollbar { width: 4px; }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.18); border-radius: 99px; }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-track { background: transparent; }

    .nv-section {
        padding: 14px 12px 6px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
        color: var(--nx-muted);
    }
    .sidebar-collapsed .nv-section { visibility: hidden; height: 10px; padding: 0; }

    .nv-row { position: relative; }

    .nv-link {
        position: relative;
        display: flex; align-items: center; gap: 11px;
        height: 38px; padding: 0 10px;
        border-radius: 10px;
        color: var(--nx-text);
        border: 1px solid transparent;
        transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease;
        cursor: pointer;
    }
    .nv-icon {
        width: 28px; height: 28px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 8px;
        color: var(--nx-text);
        background: rgba(148, 163, 184, 0.06);
        border: 1px solid rgba(148, 163, 184, 0.08);
        transition: color 0.16s ease, background-color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }
    .nv-label { font-size: 13px; font-weight: 550; letter-spacing: -0.005em; }
    .nv-chevron { width: 14px; height: 14px; color: var(--nx-muted); transition: transform 0.2s var(--nx-ease), color 0.16s ease; }

    /* Hover */
    .nv-row:hover > .nv-link { background: rgba(148, 163, 184, 0.07); color: var(--nx-text-strong); }
    .nv-row:hover > .nv-link .nv-icon { color: var(--nx-c); border-color: color-mix(in srgb, var(--nx-c) 30%, transparent); }

    /* Open: the row that owns the visible flyout */
    .nv-row.nv-open > .nv-link {
        background: linear-gradient(90deg, color-mix(in srgb, var(--nx-c) 16%, transparent), rgba(148, 163, 184, 0.05));
        border-color: color-mix(in srgb, var(--nx-c) 45%, transparent);
        color: var(--nx-text-strong);
    }
    .nv-row.nv-open > .nv-link .nv-icon {
        color: #fff;
        background: var(--nx-c);
        border-color: transparent;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--nx-c) 22%, transparent), 0 6px 16px -4px color-mix(in srgb, var(--nx-c) 70%, transparent);
    }
    .nv-row.nv-open > .nv-link .nv-chevron { color: var(--nx-c); transform: translateX(3px); }
    [dir="rtl"] .nv-chevron { transform: scaleX(-1); }
    [dir="rtl"] .nv-row.nv-open > .nv-link .nv-chevron { transform: scaleX(-1) translateX(3px); }

    /* Active route */
    .nv-link.nv-active { background: rgba(79, 124, 255, 0.12); color: var(--nx-text-strong); }
    .nv-link.nv-active::before {
        content: '';
        position: absolute; left: -8px; top: 9px; bottom: 9px; width: 3px;
        border-radius: 0 3px 3px 0;
        background: linear-gradient(180deg, var(--nx-accent-2), var(--nx-accent));
        box-shadow: 0 0 12px rgba(79, 124, 255, 0.8);
    }
    [dir="rtl"] .nv-link.nv-active::before { left: auto; right: -8px; border-radius: 3px 0 0 3px; }
    .nv-link.nv-active .nv-icon { color: #fff; background: linear-gradient(135deg, var(--nx-accent), #6d5dfc); border-color: transparent; }

    .sidebar-collapsed .nv-link { justify-content: center; padding: 0; }

    /* ═══════════════════════════════════════════════════════
       FLYOUT — opened/positioned by JS (.nv-open on the row)
       ═══════════════════════════════════════════════════════ */
    .nv-flyout {
        position: fixed;
        top: 0; left: 0;
        z-index: 10020;
        width: 264px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateX(-8px);
        transition: opacity 0.14s ease, transform 0.22s var(--nx-ease), visibility 0s linear 0.22s;
    }
    [dir="rtl"] .nv-flyout { transform: translateX(8px); }
    .nv-row.nv-open > .nv-flyout {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: none;
        transition-delay: 0s;
    }

    /* Invisible bridge across the gap so the pointer never "leaves" the row */
    .nv-flyout::after {
        content: '';
        position: absolute; top: 0; bottom: 0; left: -14px; width: 14px;
    }
    [dir="rtl"] .nv-flyout::after { left: auto; right: -14px; }

    /* Caret pointing back at the row that opened it */
    .nv-flyout::before {
        content: '';
        position: absolute; z-index: 2;
        left: -6px; top: var(--nx-caret-y, 19px);
        width: 12px; height: 12px;
        margin-top: -6px;
        background: var(--nx-surface);
        border-left: 1px solid var(--nx-surface-line);
        border-bottom: 1px solid var(--nx-surface-line);
        transform: rotate(45deg);
        border-radius: 0 0 0 3px;
    }
    [dir="rtl"] .nv-flyout::before { left: auto; right: -6px; transform: rotate(-135deg); }

    .nv-flyout-inner {
        position: relative; z-index: 1;
        background: var(--nx-surface);
        border: 1px solid var(--nx-surface-line);
        border-radius: 14px;
        overflow: hidden;
        box-shadow:
            0 0 0 1px rgba(2, 6, 23, 0.4),
            0 24px 48px -12px rgba(2, 6, 23, 0.55),
            0 0 40px -10px color-mix(in srgb, var(--nx-c) 35%, transparent);
    }
    .nv-flyout-inner::before {
        content: '';
        position: absolute; left: 0; right: 0; top: 0; height: 2px;
        background: linear-gradient(90deg, var(--nx-c), color-mix(in srgb, var(--nx-c) 30%, transparent) 70%, transparent);
    }

    .nv-flyout-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 14px 10px;
        border-bottom: 1px solid var(--nx-rail-line);
    }
    .nv-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--nx-c);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--nx-c) 22%, transparent), 0 0 10px var(--nx-c);
    }
    .nv-flyout-title { font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--nx-text-strong); }
    .nv-flyout-count {
        font-size: 10px; font-weight: 600; font-variant-numeric: tabular-nums;
        padding: 2px 7px; border-radius: 6px;
        color: var(--nx-text); background: rgba(148, 163, 184, 0.08); border: 1px solid var(--nx-rail-line);
    }

    .nv-flyout-body { padding: 6px; display: flex; flex-direction: column; gap: 1px; }

    .nv-sub {
        position: relative;
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        height: 38px; padding: 0 8px;
        border-radius: 9px;
        color: #c3cce0;
        text-decoration: none;
        transition: background-color 0.14s ease, color 0.14s ease;
    }
    .nv-sub:hover, .nv-sub:focus-visible { background: rgba(148, 163, 184, 0.08); color: #fff; }
    .nv-sub-icon {
        width: 26px; height: 26px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 7px;
        color: var(--nx-sc, var(--nx-c));
        background: color-mix(in srgb, var(--nx-sc, var(--nx-c)) 14%, transparent);
    }
    .nv-sub-text { font-size: 12.5px; font-weight: 550; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nv-sub-arr { width: 13px; height: 13px; flex-shrink: 0; color: var(--nx-muted); opacity: 0; transform: translateX(-4px); transition: all 0.16s var(--nx-ease); }
    .nv-sub:hover .nv-sub-arr { opacity: 1; transform: none; color: var(--nx-c); }
    [dir="rtl"] .nv-sub-arr { transform: scaleX(-1) translateX(-4px); }
    [dir="rtl"] .nv-sub:hover .nv-sub-arr { transform: scaleX(-1); }
    .nv-sub-badge {
        font-size: 9.5px; font-weight: 600; white-space: nowrap;
        padding: 2px 7px; border-radius: 6px;
        color: var(--nx-text); background: rgba(148, 163, 184, 0.08); border: 1px solid var(--nx-rail-line);
    }
    .nv-sub-active { background: color-mix(in srgb, var(--nx-c) 14%, transparent); color: #fff; }
    .nv-sub-active::before {
        content: '';
        position: absolute; left: 0; top: 10px; bottom: 10px; width: 2px; border-radius: 2px;
        background: var(--nx-c);
    }

    /* Collapse toggle */
    /* Kept to the rail's width explicitly: the Tailwind max-w utilities are not in the compiled build */
    .nv-collapse { width: 220px !important; max-width: 220px !important; }
    .sidebar-collapsed .nv-collapse { width: 70px !important; max-width: 70px !important; }
    html.nv-auto .nv-collapse { width: 220px !important; max-width: 220px !important; }
    .nv-collapse {
        background: var(--nx-rail);
        border-top: 1px solid var(--nx-rail-line);
        color: var(--nx-text);
    }
    .nv-collapse:hover { color: var(--nx-text-strong); background: #0e162b; }

    /* Rail tools (collapse + pin) */
    .nv-tool {
        display: flex; align-items: center; gap: 8px;
        height: 34px; padding: 0 8px;
        border-radius: 8px;
        color: var(--nx-text);
        font-size: 12px; font-weight: 550; white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .nv-tool:hover { background: rgba(148, 163, 184, 0.08); color: var(--nx-text-strong); }
    .nv-tool svg { width: 15px; height: 15px; flex-shrink: 0; }
    .nv-pin-label-pin { display: none; }
    html.nv-auto .nv-pin-label-unpin { display: none; }
    html.nv-auto .nv-pin-label-pin { display: inline; }
    html.nv-auto .nv-tool-collapse { display: none; }
    html.nv-auto .nv-tool-pin { color: var(--nx-text-strong); background: rgba(79, 124, 255, 0.16); margin-left: auto; }
    html.nv-auto .nv-tool-pin svg { color: var(--nx-accent); }

    /* ═══════════════════════════════════════════════════════
       AUTO-HIDE RAIL (html.nv-auto) — peeks in with html.nv-peek.
       Slides with `left`, never `transform`, so flyouts stay fixed.
       ═══════════════════════════════════════════════════════ */
    .nv-edge, .nv-reveal { display: none; }

    @media (min-width: 1024px) {
        html:not([dir="rtl"]) #admin-sidebar { left: 0; }

        html.nv-auto #admin-sidebar {
            width: 220px !important;
            left: -236px;
            transition: left 0.32s var(--nx-ease), right 0.32s var(--nx-ease), box-shadow 0.32s ease;
        }
        html.nv-auto.nv-peek #admin-sidebar {
            left: 0;
            box-shadow: 30px 0 70px -24px rgba(2, 6, 23, 0.55), 1px 0 0 rgba(255, 255, 255, 0.04) inset;
        }
        html[dir="rtl"].nv-auto #admin-sidebar { left: auto; right: -236px; }
        html[dir="rtl"].nv-auto.nv-peek #admin-sidebar { right: 0; }

        html.nv-auto .admin-main-content { padding-left: 28px !important; padding-right: 28px !important; }

        html.nv-auto .nv-edge {
            display: block;
            position: fixed; z-index: 10001;
            top: 60px; bottom: 0; left: 0; width: 12px;
        }
        html[dir="rtl"].nv-auto .nv-edge { left: auto; right: 0; }
        html.nv-auto .nv-edge::after {
            content: '';
            position: absolute; left: 3px; top: 50%;
            width: 3px; height: 56px; margin-top: -28px;
            border-radius: 99px;
            background: linear-gradient(180deg, var(--nx-accent-2), var(--nx-accent));
            opacity: 0.35;
            transition: opacity 0.2s ease, height 0.25s var(--nx-ease), margin-top 0.25s var(--nx-ease);
        }
        html.nv-auto .nv-edge:hover::after { opacity: 0.9; height: 96px; margin-top: -48px; }
        html.nv-auto.nv-peek .nv-edge::after { opacity: 0; }

        html.nv-auto .nv-reveal {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; margin-right: 6px;
            border-radius: 10px;
            color: #475569;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            transition: color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }
        html.nv-auto .nv-reveal svg { width: 17px; height: 17px; }
        html.nv-auto .nv-reveal:hover,
        html.nv-auto.nv-peek .nv-reveal {
            color: var(--nx-accent);
            border-color: rgba(79, 124, 255, 0.35);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.12);
        }
        .dark.nv-auto .nv-reveal { color: var(--nx-text); background: rgba(148, 163, 184, 0.06); border-color: var(--nx-rail-line); }
    }

    /* ═══════════════════════════════════════════════════════
       WORKSPACE — quiet refinements for the content area
       ═══════════════════════════════════════════════════════ */
    body {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-feature-settings: "cv11", "ss01", "ss03";
        text-rendering: optimizeLegibility;
    }
    html { scrollbar-width: thin; scrollbar-color: rgba(100, 116, 139, 0.35) transparent; }
    ::selection { background: rgba(79, 124, 255, 0.18); }

    /* Content settles in on each page load (ends at transform:none, so fixed modals are unaffected) */
    @keyframes nxRise {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }
    .admin-main-content { animation: nxRise 0.45s var(--nx-ease) both; }

    /* Page titles */
    :root:not(.dark) .admin-main-content .text-xl.font-bold { color: #0b1220; }
    .admin-main-content .text-xl.font-bold { letter-spacing: -0.015em; }

    /* Cards: hairline border, top highlight and a soft, long shadow */
    .admin-main-content .box-shadow,
    .admin-main-content .bg-white.border:is(.rounded-lg, .rounded-xl, .rounded-2xl) {
        border-color: rgba(15, 23, 42, 0.07);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.9),
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 12px 32px -20px rgba(15, 23, 42, 0.22);
    }
    .dark .admin-main-content .box-shadow,
    .dark .admin-main-content .bg-white.border:is(.rounded-lg, .rounded-xl, .rounded-2xl) {
        border-color: rgba(148, 163, 184, 0.1);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03), 0 14px 32px -18px rgba(0, 0, 0, 0.7);
    }

    /* Rows and cards that open on click (see "clickable rows" script) */
    .nx-row-clickable { cursor: pointer; }
    .nx-row-clickable:hover {
        background-color: rgba(79, 124, 255, 0.05) !important;
        box-shadow: inset 3px 0 0 var(--nx-accent), 0 8px 20px -16px rgba(79, 124, 255, 0.6) !important;
    }

    /* Emoji replaced by line icons */
    .nx-emoji { display: inline-block; width: 1.05em; height: 1.05em; vertical-align: -0.16em; margin-right: 0.3em; flex-shrink: 0; color: var(--nx-emoji-c, currentColor); }
    .nx-emoji.is-alert { --nx-emoji-c: #e11d48; }
    .nx-emoji.is-warn { --nx-emoji-c: #d97706; }
    .nx-emoji.is-ok { --nx-emoji-c: #059669; }
    .nx-emoji.is-money { --nx-emoji-c: #059669; }
    .nx-emoji.is-accent { --nx-emoji-c: var(--nx-accent); }

    /* Cards never sit flat: a barely-there vertical tint */
    :root:not(.dark) .admin-main-content .box-shadow,
    :root:not(.dark) .admin-main-content .bg-white.border:is(.rounded-lg, .rounded-xl, .rounded-2xl) {
        background-image: linear-gradient(180deg, #ffffff 0%, #fafbfe 100%);
    }

    /* Sticky page header bar (title + primary actions): its own tinted band */
    .admin-main-content .scroll-reactive-sticky {
        border-color: rgba(79, 124, 255, 0.14) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 8px 24px -16px rgba(30, 58, 138, 0.28) !important;
    }
    :root:not(.dark) .admin-main-content .scroll-reactive-sticky {
        background-color: #fbfcff !important;
        background-image:
            radial-gradient(520px 140px at 100% 0%, rgba(34, 211, 238, 0.09), transparent 70%),
            linear-gradient(100deg, rgba(79, 124, 255, 0.07) 0%, rgba(79, 124, 255, 0.02) 45%, rgba(255, 255, 255, 0) 70%) !important;
    }
    .admin-main-content .scroll-reactive-sticky::before {
        content: '';
        position: absolute; left: 0; top: 10px; bottom: 10px; width: 3px;
        border-radius: 0 3px 3px 0;
        background: linear-gradient(180deg, var(--nx-accent-2), var(--nx-accent));
    }
    .dark .admin-main-content .scroll-reactive-sticky {
        background-image: linear-gradient(100deg, rgba(79, 124, 255, 0.12), transparent 60%) !important;
        border-color: rgba(79, 124, 255, 0.2) !important;
    }

    /* ── Data lists (datagrid) ─────────────────────────── */
    .admin-main-content .table-responsive { border-color: rgba(15, 23, 42, 0.08) !important; }

    /* Column header: tinted band with small-caps labels */
    :root:not(.dark) .admin-main-content .table-responsive .row.grid.bg-gray-50 {
        background: linear-gradient(180deg, #f4f7fd 0%, #edf1fa 100%) !important;
        border-bottom-color: rgba(79, 124, 255, 0.14) !important;
    }
    .admin-main-content .table-responsive .row.grid.bg-gray-50 {
        font-size: 11.5px; font-weight: 650; letter-spacing: 0.06em; text-transform: uppercase;
        color: #51607a !important;
    }

    /* Body rows: soft zebra, hairline separators, accent hover with edge marker */
    .admin-main-content .table-responsive .row.grid:not(.bg-gray-50) {
        position: relative;
        border-bottom-color: rgba(15, 23, 42, 0.06) !important;
        transition: background-color 0.15s ease, box-shadow 0.15s ease;
    }
    :root:not(.dark) .admin-main-content .table-responsive .row.grid:not(.bg-gray-50):nth-child(even of .row.grid:not(.bg-gray-50)) {
        background-color: #f7f9fd;
    }
    .dark .admin-main-content .table-responsive .row.grid:not(.bg-gray-50):nth-child(even of .row.grid:not(.bg-gray-50)) {
        background-color: rgba(148, 163, 184, 0.04);
    }
    .admin-main-content .table-responsive .row.grid:not(.bg-gray-50):hover {
        background-color: rgba(79, 124, 255, 0.07) !important;
        box-shadow: inset 3px 0 0 var(--nx-accent);
    }
    .admin-main-content .table-responsive .row.grid:not(.bg-gray-50):has(.icon-eye, .icon-edit) { cursor: pointer; }

    /* Other lists (kanban cards, plain rows) keep the gentle wash */
    .admin-main-content .row.grid:not(.table-responsive .row.grid):hover {
        background-image: linear-gradient(90deg, rgba(79, 124, 255, 0.06), rgba(79, 124, 255, 0) 55%);
    }

    /* ── Modals ─────────────────────────────────────────── */
    .fixed.inset-0.bg-gray-500.bg-opacity-50 {
        background-color: rgba(15, 23, 42, 0.38) !important;
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }
    /* Corner-anchored modals (e.g. Add Activity): lift them clear of the bottom edge and let tall forms scroll */
    @media (min-width: 640px) {
        .fixed.inset-0 > .min-h-full > .sm\:absolute:is(.bottom-4) {
            bottom: 56px;
            max-height: calc(100vh - 136px);
            overflow-y: auto;
        }
        .fixed.inset-0 > .min-h-full > .sm\:absolute:is(.top-4) {
            top: 76px;
            max-height: calc(100vh - 136px);
            overflow-y: auto;
        }
    }

    /* Form fields: calm borders, accent focus halo */
    .admin-main-content :is(input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="range"]), select, textarea) {
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .admin-main-content :is(input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="range"]), select, textarea):focus {
        border-color: rgba(79, 124, 255, 0.6) !important;
        box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.14) !important;
        outline: none;
    }

    /* Secondary buttons: lighter 1px frame instead of the heavy 2px one */
    .secondary-button {
        border-width: 1px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .secondary-button:hover { box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-color) 12%, transparent); }

    /* ═══════════════════════════════════════════════════════
       ICONS — the bold Krayin icon font is redrawn as thin line
       icons (same family as the rail) via CSS masks, so every
       existing `icon-*` element picks it up with no markup change.
       ═══════════════════════════════════════════════════════ */
    .admin-main-content .icon-eye { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0'/><circle cx='12' cy='12' r='3'/></svg>"); --nx-act: #4f7cff; }
    .admin-main-content .icon-edit { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'/><path d='M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z'/></svg>"); --nx-act: #7c5cff; }
    .admin-main-content .icon-delete { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M3 6h18'/><path d='M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6'/><path d='M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2'/><path d='M10 11v6'/><path d='M14 11v6'/></svg>"); --nx-act: #e5484d; }
    .admin-main-content .icon-tick { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M20 6 9 17l-5-5'/></svg>"); --nx-act: #10b981; }
    .admin-main-content .icon-print { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2'/><path d='M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6'/><rect x='6' y='14' width='12' height='8' rx='1'/></svg>"); --nx-act: #0ea5e9; }
    .admin-main-content :is(.icon-mail, .icon-settings-mail) { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><rect width='20' height='16' x='2' y='4' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/></svg>"); --nx-act: #f59e0b; --nx-ic: #3b82f6; }
    .admin-main-content :is(.icon-import, .icon-download) { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M12 15V3'/><path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/><path d='m7 10 5 5 5-5'/></svg>"); --nx-act: #0ea5e9; --nx-ic: #0ea5e9; }
    .admin-main-content .icon-settings-group { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M22 21v-2a4 4 0 0 0-3-3.87'/><path d='M16 3.13a4 4 0 0 1 0 7.75'/></svg>"); --nx-ic: #6366f1; }
    .admin-main-content .icon-role { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z'/><path d='m9 12 2 2 4-4'/></svg>"); --nx-ic: #f59e0b; }
    .admin-main-content .icon-user { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><circle cx='12' cy='10' r='3'/><path d='M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662'/></svg>"); --nx-ic: #0ea5e9; }
    .admin-main-content .icon-settings-pipeline { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z'/></svg>"); --nx-ic: #4f7cff; }
    .admin-main-content .icon-settings-sources { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><circle cx='18' cy='5' r='3'/><circle cx='6' cy='12' r='3'/><circle cx='18' cy='19' r='3'/><path d='m8.59 13.51 6.83 3.98'/><path d='m15.41 6.51-6.82 3.98'/></svg>"); --nx-ic: #10b981; }
    .admin-main-content .icon-settings-type { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><rect width='7' height='7' x='3' y='3' rx='1.5'/><rect width='7' height='7' x='14' y='3' rx='1.5'/><rect width='7' height='7' x='14' y='14' rx='1.5'/><rect width='7' height='7' x='3' y='14' rx='1.5'/></svg>"); --nx-ic: #d946ef; }
    .admin-main-content .icon-settings-tag { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z'/><circle cx='7.5' cy='7.5' r='1'/></svg>"); --nx-ic: #f43f5e; }
    .admin-main-content .icon-attribute { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4'/></svg>"); --nx-ic: #14b8a6; }
    .admin-main-content .icon-settings-webhooks { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2'/><path d='m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06'/><path d='m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8'/></svg>"); --nx-ic: #8b5cf6; }
    .admin-main-content .icon-settings-warehouse { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z'/><path d='M6 18h12M6 14h12'/><rect width='12' height='12' x='6' y='10'/></svg>"); --nx-ic: #f97316; }
    .admin-main-content .icon-settings-flow { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><rect width='8' height='8' x='3' y='3' rx='2'/><path d='M7 11v4a2 2 0 0 0 2 2h4'/><rect width='8' height='8' x='13' y='13' rx='2'/></svg>"); --nx-ic: #06b6d4; }
    .admin-main-content .icon-calendar { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M8 2v4M16 2v4'/><rect width='18' height='18' x='3' y='4' rx='2'/><path d='M3 10h18'/></svg>"); --nx-ic: #ec4899; }
    .admin-main-content :is(.icon-setting, .icon-settings) { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M20 7h-9M14 17H5'/><circle cx='17' cy='17' r='3'/><circle cx='7' cy='7' r='3'/></svg>"); --nx-ic: #4f7cff; }
    .admin-main-content .icon-configuration { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'/></svg>"); --nx-ic: #64748b; }
    .admin-main-content .icon-note { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z'/><path d='M15 3v4a2 2 0 0 0 2 2h4'/></svg>"); --nx-ic: #f59e0b; }
    .admin-main-content .icon-activity { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M8 2v4M16 2v4'/><rect width='18' height='18' x='3' y='4' rx='2'/><path d='M3 10h18'/><path d='m9 16 2 2 4-4'/></svg>"); --nx-ic: #6366f1; }
    .admin-main-content .icon-file { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z'/><path d='M14 2v4a2 2 0 0 0 2 2h4'/></svg>"); --nx-ic: #0ea5e9; }

    .admin-main-content [class*="icon-"]:is(.icon-eye, .icon-edit, .icon-delete, .icon-tick, .icon-print, .icon-mail, .icon-settings-mail, .icon-import, .icon-download, .icon-settings-group, .icon-role, .icon-user, .icon-settings-pipeline, .icon-settings-sources, .icon-settings-type, .icon-settings-tag, .icon-attribute, .icon-settings-webhooks, .icon-settings-warehouse, .icon-settings-flow, .icon-calendar, .icon-setting, .icon-settings, .icon-configuration, .icon-note, .icon-activity, .icon-file)::before {
        content: '' !important;
        display: inline-block;
        width: 1em; height: 1em;
        vertical-align: -0.125em;
        background-color: currentColor;
        -webkit-mask: var(--nx-i) center / contain no-repeat;
        mask: var(--nx-i) center / contain no-repeat;
    }

    /* More icon-font glyphs, in the content area, the header and dialogs */
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-dark { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-light { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='4'/><path d='M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-list { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M3 12h.01M3 18h.01M3 6h.01M8 12h13M8 18h13M8 6h13'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-kanban { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M6 5v11M12 5v6M18 5v14'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-add { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M5 12h14M12 5v14'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-search { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='8'/><path d='m21 21-4.3-4.3'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-down-arrow { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m6 9 6 6 6-6'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-cross-large { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M18 6 6 18M6 6l12 12'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-refresh { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8'/><path d='M21 3v5h-5'/><path d='M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16'/><path d='M8 16H3v5'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-info { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><path d='M12 16v-4M12 8h.01'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-more { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='1'/><circle cx='19' cy='12' r='1'/><circle cx='5' cy='12' r='1'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-attachment { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-menu { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M4 6h16M4 12h16M4 18h16'/></svg>"); }
    :is(.admin-main-content, .nx-header, .tw-modal) .icon-calendar { --nx-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'><path d='M8 2v4M16 2v4'/><rect width='18' height='18' x='3' y='4' rx='2'/><path d='M3 10h18'/></svg>"); }

    :is(.admin-main-content, .nx-header, .tw-modal) :is(.icon-dark, .icon-light, .icon-list, .icon-kanban, .icon-add, .icon-search, .icon-down-arrow, .icon-cross-large, .icon-refresh, .icon-info, .icon-more, .icon-attachment, .icon-menu, .icon-calendar)::before {
        content: '' !important;
        display: inline-block;
        width: 1em; height: 1em;
        vertical-align: -0.125em;
        background-color: currentColor;
        -webkit-mask: var(--nx-i) center / contain no-repeat;
        mask: var(--nx-i) center / contain no-repeat;
    }

    /* List action buttons (view / edit / delete…): quiet chips that tint on hover */
    .admin-main-content .table-responsive span.cursor-pointer:is(.icon-eye, .icon-edit, .icon-delete, .icon-tick, .icon-print, .icon-mail, .icon-import) {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        margin-inline-start: 6px;
        padding: 0 !important;
        font-size: 16px !important;
        border-radius: 9px;
        color: #5b6781;
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.2s ease, transform 0.2s var(--nx-ease);
    }
    .admin-main-content .table-responsive span.cursor-pointer:is(.icon-eye, .icon-edit, .icon-delete, .icon-tick, .icon-print, .icon-mail, .icon-import):hover {
        color: var(--nx-act);
        background: color-mix(in srgb, var(--nx-act) 10%, #fff) !important;
        border-color: color-mix(in srgb, var(--nx-act) 35%, transparent);
        box-shadow: 0 6px 14px -6px color-mix(in srgb, var(--nx-act) 60%, transparent);
        transform: translateY(-1px);
    }
    .dark .admin-main-content .table-responsive span.cursor-pointer:is(.icon-eye, .icon-edit, .icon-delete, .icon-tick, .icon-print, .icon-mail, .icon-import) {
        color: #9aa6bd; background: rgba(148, 163, 184, 0.06); border-color: rgba(148, 163, 184, 0.12);
    }
    .dark .admin-main-content .table-responsive span.cursor-pointer:is(.icon-eye, .icon-edit, .icon-delete, .icon-tick, .icon-print, .icon-mail, .icon-import):hover {
        background: color-mix(in srgb, var(--nx-act) 16%, transparent) !important;
    }

    /* Settings / Configuration cards: tinted icon tiles, soft hover */
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> i[class*="icon-"]) {
        width: 52px; height: 52px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        padding: 0 !important;
        border-radius: 14px !important;
        color: var(--nx-ic);
        background: linear-gradient(145deg, color-mix(in srgb, var(--nx-ic) 16%, transparent), color-mix(in srgb, var(--nx-ic) 5%, transparent)) !important;
        border: 1px solid color-mix(in srgb, var(--nx-ic) 22%, transparent);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 8px 18px -12px color-mix(in srgb, var(--nx-ic) 80%, transparent);
        transition: transform 0.2s var(--nx-ease), box-shadow 0.2s ease;
    }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3 > i[class*="icon-"] { font-size: 24px !important; }
    /* The tile inherits the colour its icon declares (accent by default) */
    .admin-main-content .rounded-lg.bg-gray-100.p-3 { --nx-ic: var(--nx-accent); }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-group) { --nx-ic: #6366f1; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-role) { --nx-ic: #f59e0b; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-user) { --nx-ic: #0ea5e9; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-pipeline) { --nx-ic: #4f7cff; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-sources) { --nx-ic: #10b981; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-type) { --nx-ic: #d946ef; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-tag) { --nx-ic: #f43f5e; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-attribute) { --nx-ic: #14b8a6; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-webhooks) { --nx-ic: #8b5cf6; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-warehouse) { --nx-ic: #f97316; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-settings-flow) { --nx-ic: #06b6d4; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> :is(.icon-mail, .icon-settings-mail)) { --nx-ic: #3b82f6; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-calendar) { --nx-ic: #ec4899; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> :is(.icon-download, .icon-import)) { --nx-ic: #0ea5e9; }
    .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> .icon-configuration) { --nx-ic: #64748b; }
    .admin-main-content a:has(> .rounded-lg.bg-gray-100.p-3 > i[class*="icon-"]):hover {
        background-color: rgba(79, 124, 255, 0.05) !important;
    }
    .admin-main-content a:has(> .rounded-lg.bg-gray-100.p-3 > i[class*="icon-"]):hover > .rounded-lg {
        transform: translateY(-2px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 12px 22px -12px color-mix(in srgb, var(--nx-ic) 90%, transparent);
    }
    .dark .admin-main-content a > .rounded-lg.bg-gray-100.p-3:has(> i[class*="icon-"]) { box-shadow: none; }

    /* ═══════════════════════════════════════════════════════
       TEAMWORK — follow-up center, flag button, modal
       ═══════════════════════════════════════════════════════ */
    .tw-ok { --tw-c: #059669; }
    .tw-warning { --tw-c: #d97706; }
    .tw-overdue { --tw-c: #dc2626; }
    .tw-urgent { --tw-c: #e11d48; }
    .tw-info { --tw-c: #4f7cff; }

    .tw-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 1024px) { .tw-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .tw-kpi {
        position: relative; overflow: hidden;
        display: flex; flex-direction: column; gap: 4px;
        padding: 14px 16px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.07);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 26px -20px rgba(15, 23, 42, 0.3);
        text-decoration: none;
        transition: transform 0.2s var(--nx-ease), box-shadow 0.2s ease;
    }
    .tw-kpi::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--tw-c); }
    .tw-kpi::after {
        content: ''; position: absolute; right: -30px; top: -30px; width: 110px; height: 110px; border-radius: 50%;
        background: radial-gradient(circle, color-mix(in srgb, var(--tw-c) 18%, transparent), transparent 70%);
    }
    a.tw-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -20px color-mix(in srgb, var(--tw-c) 70%, transparent); }
    .tw-kpi-label { font-size: 12px; font-weight: 600; color: #5b6781; }
    .tw-kpi-value { font-size: 28px; font-weight: 750; letter-spacing: -0.02em; color: #0b1220; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .tw-kpi-value small { font-size: 12px; font-weight: 600; color: var(--tw-c); margin-left: 6px; letter-spacing: 0; }
    .dark .tw-kpi { background: #0f172a; border-color: rgba(148, 163, 184, 0.12); }
    .dark .tw-kpi-value { color: #f1f5f9; }

    .tw-card {
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.07);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 32px -22px rgba(15, 23, 42, 0.25);
        overflow: hidden;
    }
    .dark .tw-card { background: #0f172a; border-color: rgba(148, 163, 184, 0.12); }
    .tw-card-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: linear-gradient(180deg, #f8faff, #f3f6fc);
    }
    .dark .tw-card-head { background: rgba(148, 163, 184, 0.05); border-color: rgba(148, 163, 184, 0.1); }
    .tw-card-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #0b1220; }
    .dark .tw-card-title { color: #f1f5f9; }
    .tw-card-title::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--tw-c, var(--nx-accent)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--tw-c, var(--nx-accent)) 20%, transparent); }
    .tw-card-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
    .tw-count { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; color: #475569; background: rgba(15, 23, 42, 0.06); font-variant-numeric: tabular-nums; }
    .tw-empty { padding: 22px 16px; font-size: 13px; color: #64748b; text-align: center; }

    .tw-row {
        display: grid; align-items: center; gap: 12px;
        padding: 10px 16px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        font-size: 13px; color: #1e293b;
        transition: background-color 0.15s ease;
    }
    .tw-row:last-child { border-bottom: 0; }
    .tw-row:nth-child(even) { background: #f9fbfe; }
    .tw-row:hover { background: rgba(79, 124, 255, 0.06); }
    .tw-row.tw-row-head { font-size: 11px; font-weight: 650; letter-spacing: 0.05em; text-transform: uppercase; color: #64748b; background: transparent !important; padding-top: 8px; padding-bottom: 8px; }
    .dark .tw-row { color: #cbd5e1; border-color: rgba(148, 163, 184, 0.08); }
    .dark .tw-row:nth-child(even) { background: rgba(148, 163, 184, 0.03); }
    .tw-cols-cases { grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.4fr) minmax(0, 1fr) 110px 110px auto; }
    .tw-cols-team-cases { grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr) minmax(0, 1fr) 100px 110px auto; }
    .tw-cols-follow { grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr) 120px 110px auto; }
    .tw-cols-team { grid-template-columns: minmax(0, 1.6fr) repeat(5, 90px) minmax(0, 1fr); }
    @media (max-width: 1100px) {
        .tw-cols-cases, .tw-cols-team-cases, .tw-cols-follow, .tw-cols-team { grid-template-columns: 1fr auto; }
        .tw-row > .tw-hide-sm, .tw-row-head { display: none !important; }
    }
    .tw-title { font-weight: 600; color: #0f172a; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
    .tw-title:hover { color: var(--nx-accent); }
    .dark .tw-title { color: #f1f5f9; }
    .tw-meta { font-size: 11.5px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .tw-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 3px 9px; border-radius: 99px;
        font-size: 11.5px; font-weight: 650; white-space: nowrap;
        color: var(--tw-c); background: color-mix(in srgb, var(--tw-c) 11%, transparent);
        border: 1px solid color-mix(in srgb, var(--tw-c) 24%, transparent);
    }
    .tw-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--tw-c); }
    .tw-idle { font-weight: 700; font-variant-numeric: tabular-nums; color: var(--tw-c); }

    .tw-actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
    .tw-btn {
        display: inline-flex; align-items: center; gap: 6px;
        height: 30px; padding: 0 10px;
        border-radius: 8px;
        font-size: 12px; font-weight: 600; white-space: nowrap;
        color: #334155; background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.1);
        cursor: pointer; text-decoration: none;
        transition: all 0.15s ease;
    }
    .tw-btn:hover { color: var(--tw-c, var(--nx-accent)); border-color: color-mix(in srgb, var(--tw-c, var(--nx-accent)) 40%, transparent); background: color-mix(in srgb, var(--tw-c, var(--nx-accent)) 7%, #fff); }
    .tw-btn-primary { color: #fff; background: linear-gradient(135deg, var(--nx-accent), #6d5dfc); border-color: transparent; }
    .tw-btn-primary:hover { color: #fff; background: linear-gradient(135deg, #3f6cf5, #5d4df0); }
    .dark .tw-btn { background: rgba(148, 163, 184, 0.06); color: #cbd5e1; border-color: rgba(148, 163, 184, 0.15); }

    .tw-tabs { display: inline-flex; gap: 4px; padding: 4px; border-radius: 12px; background: rgba(15, 23, 42, 0.05); }
    .tw-tab { padding: 6px 14px; border-radius: 9px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; }
    .tw-tab.is-active { background: #fff; color: #0b1220; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12); }
    .dark .tw-tabs { background: rgba(148, 163, 184, 0.08); }
    .dark .tw-tab.is-active { background: #1e293b; color: #f1f5f9; }

    .tw-urgent-card { border-color: rgba(225, 29, 72, 0.25); box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.06), 0 14px 30px -22px rgba(225, 29, 72, 0.5); }
    .tw-urgent-card .tw-card-head { background: linear-gradient(180deg, #fff5f7, #ffeef2); }

    /* Header "Follow up" button on record pages */
    .tw-flag {
        display: inline-flex; align-items: center; gap: 7px;
        height: 36px; padding: 0 12px;
        border-radius: 10px;
        font-size: 13px; font-weight: 600;
        color: #334155; background: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(79, 124, 255, 0.3);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .tw-flag svg { width: 16px; height: 16px; color: var(--nx-accent); }
    .tw-flag:hover { background: #fff; box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.14); }
    .tw-flag-badge { font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 99px; color: #fff; background: var(--nx-accent); }
    .dark .tw-flag { color: #e2e8f0; background: rgba(148, 163, 184, 0.06); }

    /* Modal */
    .tw-modal { position: fixed; inset: 0; z-index: 10050; display: none; align-items: flex-start; justify-content: center; padding: 90px 16px 16px; background: rgba(15, 23, 42, 0.38); backdrop-filter: blur(3px); }
    .tw-modal.is-open { display: flex; }
    .tw-modal-panel {
        width: 100%; max-width: 480px; max-height: calc(100vh - 110px); overflow-y: auto;
        border-radius: 16px; background: #fff;
        box-shadow: 0 30px 60px -20px rgba(15, 23, 42, 0.45);
        animation: nxRise 0.25s var(--nx-ease) both;
    }
    .dark .tw-modal-panel { background: #0f172a; }
    .tw-modal-head { padding: 16px 20px 12px; border-bottom: 1px solid rgba(15, 23, 42, 0.07); }
    .tw-modal-body { padding: 16px 20px; display: grid; gap: 14px; }
    .tw-modal-foot { padding: 12px 20px 16px; display: flex; justify-content: flex-end; gap: 8px; }
    .tw-label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 600; color: #334155; }
    .dark .tw-label { color: #cbd5e1; }
    .tw-input { width: 100%; border-radius: 9px; border: 1px solid rgba(15, 23, 42, 0.14); padding: 9px 11px; font-size: 13px; color: #0f172a; background: #fff; }
    .dark .tw-input { background: #0b1220; color: #f1f5f9; border-color: rgba(148, 163, 184, 0.2); }
    .tw-input:focus { outline: none; border-color: rgba(79, 124, 255, 0.6); box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.14); }
    .tw-segment { display: inline-flex; gap: 4px; padding: 3px; border-radius: 10px; background: rgba(15, 23, 42, 0.05); }
    .tw-segment label { cursor: pointer; }
    .tw-segment input { position: absolute; opacity: 0; pointer-events: none; }
    .tw-segment span { display: inline-block; padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #475569; }
    .tw-segment input:checked + span { background: #fff; color: #0b1220; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.14); }
    .tw-segment input[value="urgent"]:checked + span { color: #e11d48; }
    .tw-segment input:disabled + span { opacity: 0.4; cursor: not-allowed; }

    /* Notification bell */
    .tw-bell { position: relative; }
    .tw-bell-btn {
        position: relative;
        display: flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        color: #475569; cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .tw-bell-btn:hover, .tw-bell.is-open .tw-bell-btn { background: rgba(79, 124, 255, 0.1); color: var(--nx-accent); }
    .tw-bell-btn svg { width: 20px; height: 20px; }
    .dark .tw-bell-btn { color: #cbd5e1; }
    .tw-bell-badge {
        position: absolute; top: 3px; right: 2px;
        min-width: 18px; height: 18px; padding: 0 5px;
        border-radius: 99px; border: 2px solid #fff;
        font-size: 10px; font-weight: 800; line-height: 14px; text-align: center;
        color: #fff; background: #e11d48;
    }
    .tw-bell-badge[hidden] { display: none; }
    .tw-bell.has-urgent .tw-bell-badge { animation: twPulse 1.8s ease-in-out infinite; }
    @keyframes twPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.5); } 50% { box-shadow: 0 0 0 6px rgba(225, 29, 72, 0); } }
    .tw-bell-panel {
        position: absolute; right: 0; top: calc(100% + 10px); z-index: 10040;
        width: 380px; max-width: 92vw;
        display: none; flex-direction: column;
        border-radius: 14px; background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 24px 50px -16px rgba(15, 23, 42, 0.35);
        overflow: hidden;
    }
    .tw-bell.is-open .tw-bell-panel { display: flex; animation: nxRise 0.2s var(--nx-ease) both; }
    .dark .tw-bell-panel { background: #0f172a; border-color: rgba(148, 163, 184, 0.14); }
    .tw-bell-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid rgba(15, 23, 42, 0.06); }
    .tw-bell-list { max-height: 420px; overflow-y: auto; }
    .tw-bell-item { display: flex; gap: 10px; padding: 10px 14px; text-decoration: none; border-bottom: 1px solid rgba(15, 23, 42, 0.05); transition: background-color 0.15s ease; }
    .tw-bell-item:hover { background: rgba(79, 124, 255, 0.06); }
    .tw-bell-item.is-unread { background: color-mix(in srgb, var(--tw-c) 6%, transparent); box-shadow: inset 3px 0 0 var(--tw-c); }
    .tw-bell-title { display: block; font-size: 12.5px; font-weight: 600; color: #0f172a; }
    .tw-bell-body { display: block; margin-top: 2px; font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tw-bell-time { display: block; margin-top: 3px; font-size: 11px; color: #94a3b8; }
    .dark .tw-bell-title { color: #f1f5f9; }
    .tw-bell-foot { display: block; padding: 10px; text-align: center; font-size: 12.5px; font-weight: 600; color: var(--nx-accent); text-decoration: none; background: #f8faff; }
    .dark .tw-bell-foot { background: rgba(148, 163, 184, 0.05); }

    /* ═══════════════════════════════════════════════════════
       DASHBOARD — pipeline funnel, KPI tiles, cards (light + dark)
       ═══════════════════════════════════════════════════════ */
    .nx-funnel, .nx-dash-card, .nx-revenue {
        border-radius: 16px !important;
        border: 1px solid rgba(15, 23, 42, 0.07) !important;
        background: linear-gradient(180deg, #ffffff, #fafbfe) !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 14px 34px -24px rgba(15, 23, 42, 0.3) !important;
    }
    .dark :is(.nx-funnel, .nx-dash-card, .nx-revenue) {
        border-color: rgba(148, 163, 184, 0.12) !important;
        background: linear-gradient(180deg, #111a2f, #0e1628) !important;
        box-shadow: 0 16px 34px -22px rgba(0, 0, 0, 0.7) !important;
    }
    .nx-funnel { display: flex; flex-direction: column; gap: 14px; padding: 18px; }
    .nx-funnel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .nx-funnel-title { font-size: 15px; font-weight: 700; color: #0b1220; }
    .nx-funnel-sub { margin-top: 2px; font-size: 12px; color: #64748b; }
    .nx-funnel-link { font-size: 12px; font-weight: 600; color: var(--nx-accent); text-decoration: none; white-space: nowrap; }
    .nx-funnel-link:hover { text-decoration: underline; }
    .nx-funnel-totals { display: flex; gap: 10px; }
    .nx-funnel-totals > * {
        flex: 1; display: flex; flex-direction: column; gap: 2px;
        padding: 10px 12px; border-radius: 12px; text-decoration: none;
        background: rgba(79, 124, 255, 0.06); border: 1px solid rgba(79, 124, 255, 0.12);
    }
    .nx-funnel-big { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; color: #0b1220; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .nx-funnel-label { font-size: 11px; font-weight: 600; color: #5b6781; text-transform: uppercase; letter-spacing: 0.05em; }
    .nx-funnel-alert { background: rgba(225, 29, 72, 0.07) !important; border-color: rgba(225, 29, 72, 0.2) !important; }
    .nx-funnel-alert .nx-funnel-big { color: #e11d48; }
    .nx-funnel-alert.is-quiet { background: rgba(5, 150, 105, 0.06) !important; border-color: rgba(5, 150, 105, 0.18) !important; }
    .nx-funnel-alert.is-quiet .nx-funnel-big { color: #059669; }

    .nx-funnel-stages { display: flex; flex-direction: column; gap: 8px; }
    .nx-stage {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 10px 12px; border-radius: 12px; text-decoration: none;
        border: 1px solid rgba(15, 23, 42, 0.06);
        background: color-mix(in srgb, var(--stage-c) 4%, #fff);
        transition: transform 0.15s var(--nx-ease), box-shadow 0.2s ease, border-color 0.15s ease;
    }
    .nx-stage:hover { transform: translateX(3px); border-color: color-mix(in srgb, var(--stage-c) 40%, transparent); box-shadow: 0 10px 22px -16px var(--stage-c); }
    .nx-stage-step {
        width: 28px; height: 28px; flex-shrink: 0; margin-top: 1px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 9px; font-size: 12px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, var(--stage-c), color-mix(in srgb, var(--stage-c) 70%, #000));
        box-shadow: 0 6px 14px -6px var(--stage-c);
    }
    .nx-stage-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .nx-stage-top { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .nx-stage-name { font-size: 13px; font-weight: 650; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .nx-stage-count { font-size: 17px; font-weight: 800; color: #0b1220; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .nx-stage-count small { margin-left: 4px; font-size: 11px; font-weight: 600; color: #64748b; }
    .nx-stage-track { height: 7px; border-radius: 99px; background: color-mix(in srgb, var(--stage-c) 12%, transparent); overflow: hidden; }
    .nx-stage-bar { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--stage-c), color-mix(in srgb, var(--stage-c) 60%, #fff)); transition: width 0.6s var(--nx-ease); }
    .nx-stage-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .nx-stage-oldest { font-size: 11px; color: #64748b; }
    .nx-chip { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 650; white-space: nowrap; }
    .nx-chip.is-late { color: #e11d48; background: rgba(225, 29, 72, 0.1); }
    .nx-chip.is-warn { color: #b45309; background: rgba(217, 119, 6, 0.12); }
    .nx-chip.is-ok { color: #047857; background: rgba(5, 150, 105, 0.1); }
    .nx-funnel-empty { padding: 20px; text-align: center; font-size: 13px; color: #64748b; }
    .nx-funnel-foot { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-top: 12px; border-top: 1px dashed rgba(15, 23, 42, 0.1); }
    .nx-funnel-rate { margin-left: auto; font-size: 12px; color: #475569; }
    .nx-funnel-rate strong { font-size: 14px; color: #0b1220; }

    .dark .nx-funnel-title, .dark .nx-funnel-big, .dark .nx-stage-count, .dark .nx-funnel-rate strong { color: #f1f5f9; }
    .dark .nx-stage-name { color: #e2e8f0; }
    .dark .nx-funnel-sub, .dark .nx-stage-oldest, .dark .nx-stage-count small, .dark .nx-funnel-label, .dark .nx-funnel-rate { color: #94a3b8; }
    .dark .nx-stage { background: color-mix(in srgb, var(--stage-c) 9%, #0f172a); border-color: color-mix(in srgb, var(--stage-c) 18%, transparent); }
    .dark .nx-funnel-totals > * { background: rgba(79, 124, 255, 0.1); border-color: rgba(79, 124, 255, 0.2); }
    .dark .nx-chip.is-late { color: #fb7185; background: rgba(251, 113, 133, 0.14); }
    .dark .nx-chip.is-warn { color: #fbbf24; background: rgba(251, 191, 36, 0.14); }
    .dark .nx-chip.is-ok { color: #34d399; background: rgba(52, 211, 153, 0.13); }
    .dark .nx-funnel-foot { border-color: rgba(148, 163, 184, 0.15); }

    /* KPI tiles (over-all): icon, accent stripe, soft glow */
    .nx-kpi-grid > div {
        position: relative; overflow: hidden;
        padding-left: 66px !important;
        border-radius: 16px !important;
        border: 1px solid rgba(15, 23, 42, 0.07) !important;
        background: #fff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 30px -24px rgba(15, 23, 42, 0.35);
        transition: transform 0.2s var(--nx-ease), box-shadow 0.2s ease;
    }
    .nx-kpi-grid > div:hover { transform: translateY(-2px); box-shadow: 0 16px 30px -20px color-mix(in srgb, var(--kpi-c) 70%, transparent); }
    .nx-kpi-grid > div::before {
        content: ''; position: absolute; left: 16px; top: 50%; margin-top: -19px;
        width: 38px; height: 38px; border-radius: 12px;
        background: color-mix(in srgb, var(--kpi-c) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--kpi-c) 25%, transparent);
    }
    .nx-kpi-grid > div::after {
        content: ''; position: absolute; left: 25px; top: 50%; margin-top: -10px;
        width: 20px; height: 20px; background: var(--kpi-c);
        -webkit-mask: var(--kpi-i) center / contain no-repeat; mask: var(--kpi-i) center / contain no-repeat;
    }
    .nx-kpi-grid > div > p:first-child { text-transform: uppercase; letter-spacing: 0.05em; font-weight: 650 !important; font-size: 11px !important; }
    .nx-kpi-grid > div p.text-xl { font-size: 24px !important; font-weight: 800 !important; letter-spacing: -0.02em; color: #0b1220; }
    .dark .nx-kpi-grid > div { background: linear-gradient(180deg, #111a2f, #0e1628) !important; border-color: rgba(148, 163, 184, 0.12) !important; }
    .dark .nx-kpi-grid > div p.text-xl { color: #f1f5f9 !important; }
    .nx-kpi-grid > div:nth-child(1) { --kpi-c: #10b981; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'/></svg>"); }
    .nx-kpi-grid > div:nth-child(2) { --kpi-c: #6366f1; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><circle cx='12' cy='12' r='6'/><circle cx='12' cy='12' r='2'/></svg>"); }
    .nx-kpi-grid > div:nth-child(3) { --kpi-c: #0ea5e9; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M3 3v16a2 2 0 0 0 2 2h16'/><path d='m19 9-5 5-4-4-3 3'/></svg>"); }
    .nx-kpi-grid > div:nth-child(4) { --kpi-c: #f59e0b; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z'/><path d='M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8'/></svg>"); }
    .nx-kpi-grid > div:nth-child(5) { --kpi-c: #14b8a6; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'/></svg>"); }
    .nx-kpi-grid > div:nth-child(6) { --kpi-c: #8b5cf6; --kpi-i: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect width='16' height='20' x='4' y='2' rx='2'/><path d='M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01'/></svg>"); }

    /* Revenue card: inner won / lost tiles */
    .nx-revenue .rounded-lg.border { border-radius: 14px !important; border-color: rgba(15, 23, 42, 0.07) !important; }
    .nx-revenue .rounded-lg.border:first-child { background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), transparent); }
    .dark .nx-revenue .rounded-lg.border { border-color: rgba(148, 163, 184, 0.14) !important; }

    /* Dark follow-up tiles: keep text readable */
    .dark .tw-kpi-label { color: #94a3b8; }

    /* Team menu (native <details>) */
    .tw-menu { position: relative; }
    .tw-menu > summary { list-style: none; }
    .tw-menu > summary::-webkit-details-marker { display: none; }
    .tw-menu[open] > summary { background: #fff; box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.14); }
    .tw-menu-panel {
        position: absolute; right: 0; top: calc(100% + 8px); z-index: 10040;
        min-width: 250px; padding: 6px;
        border-radius: 12px; background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 20px 40px -14px rgba(15, 23, 42, 0.3);
        animation: nxRise 0.18s var(--nx-ease) both;
    }
    .dark .tw-menu-panel { background: #0f172a; border-color: rgba(148, 163, 184, 0.14); }
    .tw-menu-item {
        display: block; width: 100%; padding: 8px 10px; border-radius: 8px;
        text-align: left; font-size: 13px; font-weight: 550; color: #1e293b; text-decoration: none; cursor: pointer;
    }
    .tw-menu-item:hover { background: rgba(79, 124, 255, 0.08); color: var(--nx-accent); }
    .dark .tw-menu-item { color: #e2e8f0; }

    /* @mentions */
    .tw-mention { padding: 0 3px; border-radius: 4px; font-weight: 650; color: var(--nx-accent); background: rgba(79, 124, 255, 0.1); }
    .tw-mention-list {
        position: fixed; z-index: 10060; min-width: 220px; max-height: 240px; overflow-y: auto; padding: 4px;
        border-radius: 10px; background: #fff; border: 1px solid rgba(15, 23, 42, 0.1);
        box-shadow: 0 16px 34px -12px rgba(15, 23, 42, 0.35);
    }
    .tw-mention-option { display: flex; align-items: center; gap: 8px; width: 100%; padding: 6px 8px; border-radius: 7px; font-size: 13px; text-align: left; color: #1e293b; cursor: pointer; }
    .tw-mention-option.is-active, .tw-mention-option:hover { background: rgba(79, 124, 255, 0.1); color: var(--nx-accent); }

    /* Thread */
    .tw-msg { display: flex; gap: 10px; }
    .tw-avatar { width: 30px; height: 30px; flex-shrink: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--nx-accent), #6d5dfc); }
    .tw-bubble { flex: 1; min-width: 0; padding: 10px 12px; border-radius: 4px 12px 12px 12px; background: #f4f6fb; border: 1px solid rgba(15, 23, 42, 0.05); font-size: 13px; color: #1e293b; white-space: pre-wrap; word-break: break-word; }
    .tw-msg.is-mine .tw-bubble { background: rgba(79, 124, 255, 0.08); border-color: rgba(79, 124, 255, 0.18); }
    .dark .tw-bubble { background: rgba(148, 163, 184, 0.06); color: #e2e8f0; }

    @media (prefers-reduced-motion: reduce) {
        .nv-flyout, .nv-link, .nv-icon, .nv-chevron, .nv-sub, .nv-sub-arr, #admin-sidebar { transition: none !important; }
        .admin-main-content { animation: none; }
    }
</style>

<script>
    /**
     * Clickable list rows: clicking any cell of a datagrid row (name, email…)
     * opens the record through the row's own "view" action, or "edit" when a
     * grid has no view. Checkboxes, links, buttons and action icons keep their
     * behaviour, and selecting text never navigates.
     */
    document.addEventListener('click', function (event) {
        var target = event.target;

        if (event.defaultPrevented || event.button !== 0 || ! target.closest) {
            return;
        }

        var row = target.closest('.admin-main-content .table-responsive .row.grid');

        if (! row || row.classList.contains('bg-gray-50')) {
            return;
        }

        if (target.closest('a, button, input, label, select, textarea, [role="button"], [class*="icon-"]')) {
            return;
        }

        if (String(window.getSelection && window.getSelection()).trim()) {
            return;
        }

        var action = row.querySelector('.icon-eye') || row.querySelector('.icon-edit');

        if (action) {
            action.click();
        }
    });

    /**
     * Follow-up modal (#tw-modal). Any [data-tw-open] element opens it and can
     * prefill: data-entity-type, data-entity-id, data-assignee, data-priority,
     * data-heading. "Urgent" is only enabled for people the user supervises.
     */
    (function () {
        function modal() {
            return document.getElementById('tw-modal');
        }

        function syncUrgent(form) {
            var supervised = (form.dataset.supervised || '').split(',');
            var select = form.querySelector('[name="assigned_to"]');
            var urgent = form.querySelector('input[name="priority"][value="urgent"]');

            if (! select || ! urgent) {
                return;
            }

            var allowed = select.value === form.dataset.me || supervised.indexOf(select.value) !== -1;

            urgent.disabled = ! allowed;

            if (! allowed && urgent.checked) {
                form.querySelector('input[name="priority"][value="normal"]').checked = true;
            }
        }

        document.addEventListener('click', function (event) {
            var target = event.target;

            if (! target.closest) {
                return;
            }

            var opener = target.closest('[data-tw-open]');

            if (opener && modal()) {
                event.preventDefault();

                var box = modal();
                var form = box.querySelector('form');

                if (opener.dataset.entityType) {
                    form.querySelector('[name="entity_type"]').value = opener.dataset.entityType;
                }

                if (opener.dataset.entityId) {
                    form.querySelector('[name="entity_id"]').value = opener.dataset.entityId;
                }

                if (opener.dataset.assignee) {
                    form.querySelector('[name="assigned_to"]').value = opener.dataset.assignee;
                }

                form.querySelector('input[name="priority"][value="' + (opener.dataset.priority || 'normal') + '"]').checked = true;

                if (opener.dataset.heading) {
                    box.querySelector('[data-tw-heading]').textContent = opener.dataset.heading;
                }

                syncUrgent(form);
                box.classList.add('is-open');

                setTimeout(function () {
                    var note = form.querySelector('[name="note"]');

                    note && note.focus();
                }, 50);

                return;
            }

            // Note to a teammate
            var noteBox = document.getElementById('tw-note');

            if (target.closest('[data-tw-note]') && noteBox) {
                event.preventDefault();
                noteBox.classList.add('is-open');

                setTimeout(function () {
                    var body = noteBox.querySelector('[name="body"]');

                    body && body.focus();
                }, 50);

                return;
            }

            // Bell dropdown
            var bell = target.closest('[data-tw-bell]');

            if (target.closest('[data-tw-bell-toggle]') && bell) {
                bell.classList.toggle('is-open');

                return;
            }

            if (! bell) {
                document.querySelectorAll('[data-tw-bell].is-open').forEach(function (open) {
                    open.classList.remove('is-open');
                });
            }

            var handoff = target.closest('[data-tw-handoff]');
            var handoffBox = document.getElementById('tw-handoff');

            if (handoff && handoffBox) {
                event.preventDefault();

                handoffBox.querySelector('[name="lead_id"]').value = handoff.dataset.leadId;
                handoffBox.querySelector('[data-tw-heading]').textContent = handoff.dataset.heading || '';

                var select = handoffBox.querySelector('[name="to_user"]');

                // Preselect someone other than the current owner.
                Array.prototype.some.call(select.options, function (option) {
                    if (option.value !== handoff.dataset.owner) {
                        select.value = option.value;

                        return true;
                    }
                });

                handoffBox.classList.add('is-open');

                return;
            }

            if (target.closest('[data-tw-close]') || target.classList.contains('tw-modal')) {
                closeAll();
            }
        });

        function closeAll() {
            document.querySelectorAll('.tw-modal.is-open').forEach(function (box) {
                box.classList.remove('is-open');
            });
        }

        document.addEventListener('change', function (event) {
            if (event.target.name === 'assigned_to' && event.target.form && event.target.form.dataset.supervised !== undefined) {
                syncUrgent(event.target.form);
            }
        });

        /**
         * Bell counter: refresh every minute (and when the tab comes back),
         * and pulse when something urgent is waiting.
         */
        function refreshBell() {
            var bell = document.querySelector('[data-tw-bell]');

            if (! bell || document.hidden) {
                return;
            }

            fetch(bell.dataset.countUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (! data) {
                        return;
                    }

                    var badge = bell.querySelector('[data-tw-bell-badge]');

                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.hidden = ! data.count;
                    bell.classList.toggle('has-urgent', data.urgent > 0);

                    document.title = document.title.replace(/^\(\d+\+?\) /, '');

                    if (data.count) {
                        document.title = '(' + (data.count > 99 ? '99+' : data.count) + ') ' + document.title;
                    }
                })
                .catch(function () {});
        }

        window.addEventListener('load', function () {
            setTimeout(refreshBell, 1500);
            setInterval(refreshBell, 60000);
        });

        document.addEventListener('visibilitychange', refreshBell);

        /**
         * @mention autocomplete in team text boxes (follow-ups, threads, hand-overs,
         * activity comments). The team list travels on the bell as data-team.
         */
        var mentionList = null;
        var mentionState = null;

        function team() {
            var bell = document.querySelector('[data-tw-bell]');

            try {
                return bell ? JSON.parse(bell.dataset.team || '[]') : [];
            } catch (error) {
                return [];
            }
        }

        function closeMentions() {
            mentionList && mentionList.remove();
            mentionList = null;
            mentionState = null;
        }

        function isMentionBox(element) {
            return element && element.tagName === 'TEXTAREA' && (element.hasAttribute('data-tw-mentions') || element.name === 'comment');
        }

        function pickMention(name) {
            var box = mentionState.box;
            var before = box.value.slice(0, mentionState.start);
            var after = box.value.slice(box.selectionStart);

            box.value = before + '@' + name + ' ' + after;
            box.selectionStart = box.selectionEnd = (before + '@' + name + ' ').length;

            // Let Vue v-model (activity forms) see the change.
            box.dispatchEvent(new Event('input', { bubbles: true }));
            box.focus();

            closeMentions();
        }

        document.addEventListener('input', function (event) {
            var box = event.target;

            if (! isMentionBox(box)) {
                return;
            }

            var upToCaret = box.value.slice(0, box.selectionStart);
            var match = upToCaret.match(/(^|\s)@([^@\n]{0,30})$/);

            if (! match) {
                closeMentions();

                return;
            }

            var query = match[2].toLowerCase();
            var options = team().filter(function (member) { return member.name.toLowerCase().indexOf(query) !== -1; }).slice(0, 8);

            if (! options.length) {
                closeMentions();

                return;
            }

            closeMentions();

            mentionState = { box: box, start: upToCaret.length - match[2].length - 1, active: 0, options: options };
            mentionList = document.createElement('div');
            mentionList.className = 'tw-mention-list';

            options.forEach(function (member, index) {
                var option = document.createElement('button');

                option.type = 'button';
                option.className = 'tw-mention-option' + (index === 0 ? ' is-active' : '');
                option.textContent = '@' + member.name;
                option.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    pickMention(member.name);
                });

                mentionList.appendChild(option);
            });

            var rect = box.getBoundingClientRect();

            mentionList.style.left = rect.left + 'px';
            mentionList.style.top = Math.min(rect.bottom + 4, window.innerHeight - 250) + 'px';
            document.body.appendChild(mentionList);
        });

        document.addEventListener('keydown', function (event) {
            if (! mentionState) {
                return;
            }

            var buttons = mentionList.querySelectorAll('.tw-mention-option');

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();

                buttons[mentionState.active].classList.remove('is-active');
                mentionState.active = (mentionState.active + (event.key === 'ArrowDown' ? 1 : buttons.length - 1)) % buttons.length;
                buttons[mentionState.active].classList.add('is-active');
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                pickMention(mentionState.options[mentionState.active].name);
            } else if (event.key === 'Escape') {
                event.stopPropagation();
                closeMentions();
            }
        }, true);

        document.addEventListener('focusout', function (event) {
            if (mentionState && event.target === mentionState.box) {
                setTimeout(closeMentions, 150);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAll();
                document.querySelectorAll('[data-tw-bell].is-open').forEach(function (open) {
                    open.classList.remove('is-open');
                });
            }
        });
    })();
</script>

@include('admin::components.layouts.theme-scripts')

@includeIf('communications::partials.head')

@includeIf('teamwork::partials.head')

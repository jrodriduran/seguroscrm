@php
    $svgIcons = [
        'dashboard' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
        'follow_up' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>',
        'leads' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'quotes' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>',
        'policies' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
        'commissions' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
        'mail' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
        'hierarchy' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="5" rx="1"/><rect x="3" y="16" width="6" height="5" rx="1"/><rect x="15" y="16" width="6" height="5" rx="1"/><path d="M12 8v4m0 0H6v4m6-4h6v4"/></svg>',
        'activities' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'insurance_analytics' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
        'contacts' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'products' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
        'settings' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'configuration' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    ];

    $menuConfig = [
        'dashboard' => ['bg' => '#EEF2FF', 'border' => '#C7D2FE', 'color' => '#6366F1', 'glow' => 'rgba(99,102,241,0.18)'],
        'follow_up' => ['bg' => '#FFE4E6', 'border' => '#FECDD3', 'color' => '#E11D48', 'glow' => 'rgba(225,29,72,0.18)'],
        'leads' => ['bg' => '#E0F2FE', 'border' => '#BAE6FD', 'color' => '#0EA5E9', 'glow' => 'rgba(14,165,233,0.18)'],
        'quotes' => ['bg' => '#FEF3C7', 'border' => '#FDE68A', 'color' => '#F59E0B', 'glow' => 'rgba(245,158,11,0.18)'],
        'policies' => ['bg' => '#D1FAE5', 'border' => '#A7F3D0', 'color' => '#10B981', 'glow' => 'rgba(16,185,129,0.18)'],
        'commissions' => ['bg' => '#DCFCE7', 'border' => '#BBF7D0', 'color' => '#22C55E', 'glow' => 'rgba(34,197,94,0.18)'],
        'mail' => ['bg' => '#DBEAFE', 'border' => '#BFDBFE', 'color' => '#3B82F6', 'glow' => 'rgba(59,130,246,0.18)'],
        'hierarchy' => ['bg' => '#F3E8FF', 'border' => '#E9D5FF', 'color' => '#A855F7', 'glow' => 'rgba(168,85,247,0.18)'],
        'activities' => ['bg' => '#FFE4E6', 'border' => '#FECDD3', 'color' => '#F43F5E', 'glow' => 'rgba(244,63,94,0.18)'],
        'insurance_analytics' => ['bg' => '#FAE8FF', 'border' => '#F5D0FE', 'color' => '#D946EF', 'glow' => 'rgba(217,70,239,0.18)'],
        'contacts' => ['bg' => '#CCFBF1', 'border' => '#99F6E4', 'color' => '#14B8A6', 'glow' => 'rgba(20,184,166,0.18)'],
        'products' => ['bg' => '#FFEDD5', 'border' => '#FED7AA', 'color' => '#F97316', 'glow' => 'rgba(249,115,22,0.18)'],
        'settings' => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'color' => '#64748B', 'glow' => 'rgba(100,116,139,0.12)'],
        'configuration' => ['bg' => '#F4F4F5', 'border' => '#E4E4E7', 'color' => '#71717A', 'glow' => 'rgba(113,113,122,0.12)'],
    ];

    $subItemSvgs = [
        'mail.inbox' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'mail.draft' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
        'mail.outbox' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 8 12 4 8 8"/><line x1="12" y1="4" x2="12" y2="16"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'mail.sent' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'mail.trash' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
        'contacts.persons' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'contacts.organizations' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg>',
        'leads.team_radar' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-8.4 8.4"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/></svg>',
        'settings.user' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
        'settings.user.groups' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'settings.user.roles' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'settings.user.users' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a6 6 0 0 1 12 0v2"/></svg>',
        'settings.lead' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'settings.lead.pipelines' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="3" x2="21" y2="3"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="10" y1="15" x2="14" y2="15"/><line x1="12" y1="21" x2="12" y2="21"/></svg>',
        'settings.lead.sources' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'settings.lead.types' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    ];

    $subItemConfig = [
        'mail.inbox' => ['bg' => '#DBEAFE', 'color' => '#3B82F6', 'badge' => ''],
        'mail.draft' => ['bg' => '#FEF3C7', 'color' => '#F59E0B', 'badge' => ''],
        'mail.outbox' => ['bg' => '#E0F2FE', 'color' => '#0EA5E9', 'badge' => ''],
        'mail.sent' => ['bg' => '#D1FAE5', 'color' => '#10B981', 'badge' => ''],
        'mail.trash' => ['bg' => '#FFE4E6', 'color' => '#F43F5E', 'badge' => ''],
        'contacts.persons' => ['bg' => '#CCFBF1', 'color' => '#14B8A6', 'badge' => 'Asegurados'],
        'contacts.organizations' => ['bg' => '#EEF2FF', 'color' => '#6366F1', 'badge' => 'Carriers'],
        'leads.team_radar' => ['bg' => '#F3E8FF', 'color' => '#A855F7', 'badge' => 'Radar SLA'],
        'settings.user' => ['bg' => '#EEF2FF', 'color' => '#6366F1', 'badge' => ''],
        'settings.user.groups' => ['bg' => '#EEF2FF', 'color' => '#6366F1', 'badge' => 'Grupos'],
        'settings.user.roles' => ['bg' => '#FEF3C7', 'color' => '#F59E0B', 'badge' => 'Roles'],
        'settings.user.users' => ['bg' => '#E0F2FE', 'color' => '#0EA5E9', 'badge' => 'Usuarios'],
        'settings.lead' => ['bg' => '#E0F2FE', 'color' => '#0EA5E9', 'badge' => ''],
        'settings.lead.pipelines' => ['bg' => '#E0F2FE', 'color' => '#0EA5E9', 'badge' => 'Pipelines'],
        'settings.lead.sources' => ['bg' => '#D1FAE5', 'color' => '#10B981', 'badge' => 'Fuentes'],
        'settings.lead.types' => ['bg' => '#FAE8FF', 'color' => '#D946EF', 'badge' => 'Tipos'],
    ];
@endphp

{{-- Styles for this rail live in components/layouts/theme.blade.php (<head>), because Vue strips <style> tags inside #app. --}}
<div
    id="admin-sidebar"
    ref="sidebar"
    class="duration-80 fixed top-[60px] z-[10002] h-full w-[220px] pt-1.5 transition-all group-[.sidebar-collapsed]/container:w-[70px] max-lg:hidden"
>
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-y-auto overflow-x-hidden group-[.sidebar-collapsed]/container:overflow-visible pb-14">
        <nav class="grid w-full gap-[3px] px-2 pt-1">
            @foreach (menu()->getItems('admin') as $menuItem)
                {{-- System configuration is reached from Settings: one menu for every setting. --}}
                @continue($menuItem->getKey() === 'configuration')

                @php
                    $key = $menuItem->getKey();
                    $cfg = $menuConfig[$key] ?? ['color' => '#64748B'];
                    $svg = $svgIcons[$key] ?? '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
                    $isItemActive = (bool) $menuItem->isActive();
                    $hasChildren = ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren();
                @endphp

                <div
                    class="nv-row {{ $hasChildren ? 'nv-has-flyout' : '' }}"
                    data-menu-key="{{ $key }}"
                    style="--nx-c: {{ $cfg['color'] }};"
                >
                    <a
                        class="nv-link {{ $isItemActive ? 'nv-active' : '' }}"
                        href="{{ $hasChildren ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                        title="{{ $menuItem->getName() }}"
                        @if ($hasChildren) aria-haspopup="true" aria-expanded="false" @endif
                    >
                        <span class="nv-icon">{!! $svg !!}</span>

                        <div class="flex-1 min-w-0 flex justify-between items-center whitespace-nowrap group-[.sidebar-collapsed]/container:hidden">
                            <span class="nv-label truncate">{{ $menuItem->getName() }}</span>

                            @if ($hasChildren)
                                <svg class="nv-chevron shrink-0 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            @endif
                        </div>
                    </a>

                    @if ($hasChildren)
                        <div class="nv-flyout" role="menu">
                            <div class="nv-flyout-inner">
                                <div class="nv-flyout-head">
                                    <div class="flex items-center gap-2">
                                        <span class="nv-dot"></span>
                                        <span class="nv-flyout-title">{{ $menuItem->getName() }}</span>
                                    </div>

                                    <span class="nv-flyout-count">{{ count($menuItem->getChildren()) }}</span>
                                </div>

                                <div class="nv-flyout-body">
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        @php
                                            $subKey = $subMenuItem->getKey();
                                            $subCfg = $subItemConfig[$subKey] ?? ['color' => $cfg['color'], 'badge' => ''];
                                            $subSvg = $subItemSvgs[$subKey] ?? '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>';
                                            $showBadge = ! empty($subCfg['badge']) && strtolower(trim($subCfg['badge'])) !== strtolower(trim($subMenuItem->getName()));
                                        @endphp

                                        <a
                                            href="{{ $subMenuItem->getUrl() }}"
                                            class="nv-sub {{ $subMenuItem->isActive() ? 'nv-sub-active' : '' }}"
                                            style="--nx-sc: {{ $subCfg['color'] }};"
                                            role="menuitem"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="nv-sub-icon">{!! $subSvg !!}</span>
                                                <span class="nv-sub-text">{{ $subMenuItem->getName() }}</span>
                                            </div>

                                            @if ($showBadge)
                                                <span class="nv-sub-badge">{{ $subCfg['badge'] }}</span>
                                            @else
                                                <svg class="nv-sub-arr" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
    </div>

    {!! view_render_event('admin.layout.sidebar.toggle.before') !!}
    <v-sidebar-collapse></v-sidebar-collapse>
    {!! view_render_event('admin.layout.sidebar.toggle.after') !!}
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="nv-collapse fixed bottom-0 w-full max-w-[220px] px-2 transition-all duration-300 max-lg:hidden"
            :class="{'max-w-[70px]': isCollapsed}"
        >
            <div class="flex items-center justify-between gap-1 py-1.5">
                <!-- Collapse / expand (hidden in auto-hide mode) -->
                <button
                    type="button"
                    class="nv-tool nv-tool-collapse"
                    :title="isCollapsed
                        ? '@lang('admin::app.layouts.sidebar.expand')'
                        : '@lang('admin::app.layouts.sidebar.collapse')'"
                    @click="toggle"
                >
                    <span
                        class="icon-left-arrow text-2xl transition-all"
                        :class="[isCollapsed ? 'ltr:rotate-[180deg] rtl:rotate-[0]' : 'ltr:rotate-[0] rtl:rotate-[180deg]']"
                    ></span>
                </button>

                <!-- Pin / auto-hide, handled by the vanilla rail script -->
                <button
                    type="button"
                    class="nv-tool nv-tool-pin"
                    data-nv-pin
                    v-show="! isCollapsed"
                >
                    <svg class="nv-pin-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/></svg>
                    <span class="nv-pin-label nv-pin-label-unpin">@lang('admin::app.layouts.sidebar.unpin')</span>
                    <span class="nv-pin-label nv-pin-label-pin">@lang('admin::app.layouts.sidebar.pin')</span>
                </button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-sidebar-collapse', {
            template: '#v-sidebar-collapse-template',

            data() {
                return {
                    isCollapsed: {{ ! request()->cookie('sidebar_auto') && request()->cookie('sidebar_collapsed') ? 1 : 0 }},
                }
            },

            methods: {
                toggle() {
                    this.isCollapsed = parseInt(this.isCollapsedCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'sidebar_collapsed=' + this.isCollapsed + '; path=/; expires=' + expiryDate.toGMTString();

                    this.$root.$refs.appLayout.classList.toggle('sidebar-collapsed');

                    this.$root.$refs.appLayout.classList.toggle('sidebar-not-collapsed');
                },

                isCollapsedCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'sidebar_collapsed') {
                            return value;
                        }
                    }

                    return 0;
                },
            },
        });
    </script>

    <script>
        /**
         * Sidebar flyouts.
         *
         * Uses event delegation on `document`, so it keeps working after Vue
         * mounts and replaces the #app DOM. The row that owns the visible flyout
         * gets `.nv-open`; the flyout is placed next to the rail with its top
         * aligned to that row, clamped to the viewport, and its caret always
         * points at the row's vertical center.
         */
        (function () {
            if (window.__nvFlyouts) {
                return;
            }

            window.__nvFlyouts = true;

            var HEADER_OFFSET = 68;
            var VIEWPORT_MARGIN = 12;
            var GAP = 10;
            var CLOSE_DELAY = 180;

            var openRow = null;
            var closeTimer = null;

            function position(row) {
                var flyout = row.querySelector('.nv-flyout');
                var sidebar = document.getElementById('admin-sidebar');

                if (! flyout || ! sidebar) {
                    return;
                }

                var rowRect = row.getBoundingClientRect();
                var railRect = sidebar.getBoundingClientRect();

                if (document.documentElement.dir === 'rtl') {
                    flyout.style.left = 'auto';
                    flyout.style.right = (window.innerWidth - railRect.left + GAP) + 'px';
                } else {
                    flyout.style.right = 'auto';
                    flyout.style.left = (railRect.right + GAP) + 'px';
                }

                var height = flyout.offsetHeight;
                var top = Math.max(HEADER_OFFSET, Math.min(rowRect.top, window.innerHeight - height - VIEWPORT_MARGIN));
                var caret = Math.max(16, Math.min(height - 16, rowRect.top + rowRect.height / 2 - top));

                flyout.style.top = top + 'px';
                flyout.style.setProperty('--nx-caret-y', caret + 'px');
            }

            function close(row) {
                row.classList.remove('nv-open');
                row.querySelector('.nv-link').setAttribute('aria-expanded', 'false');

                if (openRow === row) {
                    openRow = null;
                }
            }

            function open(row) {
                clearTimeout(closeTimer);

                if (openRow === row) {
                    return;
                }

                if (openRow) {
                    close(openRow);
                }

                openRow = row;
                position(row);
                row.classList.add('nv-open');
                row.querySelector('.nv-link').setAttribute('aria-expanded', 'true');
            }

            function scheduleClose() {
                clearTimeout(closeTimer);

                closeTimer = setTimeout(function () {
                    if (openRow) {
                        close(openRow);
                    }
                }, CLOSE_DELAY);
            }

            function rowFrom(target) {
                return target && target.closest ? target.closest('#admin-sidebar .nv-row') : null;
            }

            document.addEventListener('mouseover', function (event) {
                var row = rowFrom(event.target);

                if (row && row.classList.contains('nv-has-flyout')) {
                    open(row);
                } else if (openRow) {
                    scheduleClose();
                }
            });

            document.addEventListener('mouseout', function (event) {
                if (openRow && ! event.relatedTarget) {
                    scheduleClose();
                }
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest && event.target.closest('#admin-sidebar .nv-has-flyout > .nv-link');

                if (link) {
                    event.preventDefault();

                    open(link.parentElement);
                } else if (openRow && ! rowFrom(event.target)) {
                    close(openRow);
                }
            });

            document.addEventListener('focusin', function (event) {
                var row = rowFrom(event.target);

                if (row && row.classList.contains('nv-has-flyout')) {
                    open(row);
                } else if (openRow) {
                    close(openRow);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && openRow) {
                    var link = openRow.querySelector('.nv-link');

                    close(openRow);
                    link.focus({ preventScroll: true });
                }
            });

            window.addEventListener('resize', function () {
                if (openRow) {
                    position(openRow);
                }
            });

            document.addEventListener('scroll', function () {
                if (openRow) {
                    position(openRow);
                }
            }, true);

            // The rail slides while peeking; re-anchor an open flyout once it settles.
            document.addEventListener('transitionend', function (event) {
                if (openRow && event.target.id === 'admin-sidebar') {
                    position(openRow);
                }
            });

            /**
             * Auto-hide mode (`html.nv-auto`): the rail sits off-canvas and peeks
             * in (`html.nv-peek`) while the pointer is on the left edge strip, the
             * header trigger, or the rail itself, including its flyouts.
             */
            var root = document.documentElement;
            var PEEK_CLOSE_DELAY = 320;
            var peekTimer = null;

            function setCookie(name, value) {
                var expiryDate = new Date();

                expiryDate.setMonth(expiryDate.getMonth() + 1);

                document.cookie = name + '=' + value + '; path=/; expires=' + expiryDate.toGMTString();
            }

            function peek() {
                clearTimeout(peekTimer);
                root.classList.add('nv-peek');
            }

            function unpeek(immediate) {
                clearTimeout(peekTimer);

                if (immediate) {
                    root.classList.remove('nv-peek');

                    return;
                }

                peekTimer = setTimeout(function () {
                    if (openRow) {
                        close(openRow);
                    }

                    root.classList.remove('nv-peek');
                }, PEEK_CLOSE_DELAY);
            }

            function isPeekZone(target) {
                return !! (target && target.closest && target.closest('#admin-sidebar, .nv-edge, .nv-reveal'));
            }

            document.addEventListener('mouseover', function (event) {
                if (! root.classList.contains('nv-auto')) {
                    return;
                }

                isPeekZone(event.target) ? peek() : root.classList.contains('nv-peek') && unpeek();
            });

            document.addEventListener('mouseout', function (event) {
                if (root.classList.contains('nv-peek') && ! event.relatedTarget) {
                    unpeek();
                }
            });

            document.addEventListener('click', function (event) {
                if (! event.target.closest) {
                    return;
                }

                if (event.target.closest('.nv-reveal')) {
                    root.classList.contains('nv-peek') ? unpeek(true) : peek();

                    return;
                }

                if (event.target.closest('[data-nv-pin]')) {
                    var auto = ! root.classList.contains('nv-auto');
                    var layout = document.querySelector('.group\\/container');

                    setCookie('sidebar_auto', auto ? 1 : 0);

                    if (auto && layout && layout.classList.contains('sidebar-collapsed')) {
                        layout.classList.replace('sidebar-collapsed', 'sidebar-not-collapsed');
                        setCookie('sidebar_collapsed', 0);
                    }

                    if (openRow) {
                        close(openRow);
                    }

                    root.classList.toggle('nv-auto', auto);
                    unpeek(true);

                    return;
                }

                if (root.classList.contains('nv-peek') && ! isPeekZone(event.target)) {
                    unpeek(true);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && ! openRow && root.classList.contains('nv-peek')) {
                    unpeek(true);
                }
            });
        })();
    </script>
@endPushOnce

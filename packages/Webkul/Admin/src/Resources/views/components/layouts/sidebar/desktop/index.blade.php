@php
    $svgIcons = [
        'dashboard' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
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

<div
    id="admin-sidebar"
    ref="sidebar"
    class="duration-80 fixed top-[60px] z-[10002] h-full w-[220px] pt-1.5 transition-all group-[.sidebar-collapsed]/container:w-[70px] max-lg:hidden"
>
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-y-auto overflow-x-hidden group-[.sidebar-collapsed]/container:overflow-visible pb-14">
        <nav class="grid w-full gap-[2px] px-2">
            @foreach (menu()->getItems('admin') as $menuItem)
                @php
                    $key = $menuItem->getKey();
                    $cfg = $menuConfig[$key] ?? ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'color' => '#64748B', 'glow' => 'rgba(100,116,139,0.12)'];
                    $svg = $svgIcons[$key] ?? '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
                    $isItemActive = (bool) $menuItem->isActive();
                    $hasChildren = ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren();
                @endphp

                <div class="nv-row relative {{ $hasChildren ? 'nv-has-flyout' : '' }}" data-menu-key="{{ $key }}">
                    <a
                        class="nv-link flex gap-2.5 px-2 py-[7px] items-center cursor-pointer rounded-xl transition-all duration-200 {{ $isItemActive ? 'nv-active' : '' }}"
                        href="{{ $hasChildren ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                    >
                        <span
                            class="nv-icon w-[30px] h-[30px] rounded-[9px] flex items-center justify-center shrink-0 transition-all duration-200"
                            style="background: {{ $cfg['bg'] }}; border: 1px solid {{ $cfg['border'] }}; color: {{ $cfg['color'] }}; box-shadow: 0 2px 6px {{ $cfg['glow'] }};"
                        >
                            {!! $svg !!}
                        </span>
                        <div class="flex-1 min-w-0 flex justify-between items-center whitespace-nowrap group-[.sidebar-collapsed]/container:hidden">
                            <span class="nv-label text-[12.5px] font-semibold tracking-tight truncate transition-colors duration-150">
                                {{ $menuItem->getName() }}
                            </span>
                            @if ($hasChildren)
                                <svg class="nv-chevron w-3.5 h-3.5 shrink-0 ml-1 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            @endif
                        </div>
                    </a>

                    {{-- Flyout: CSS :hover driven, JS positions it vertically --}}
                    @if ($hasChildren)
                        <div class="nv-flyout">
                            <div class="nv-flyout-inner">
                                <div class="nv-flyout-head">
                                    <div class="flex items-center gap-2">
                                        <span class="nv-dot" style="background: {{ $cfg['color'] }}; box-shadow: 0 0 6px {{ $cfg['glow'] }};"></span>
                                        <span class="nv-flyout-title">{{ $menuItem->getName() }}</span>
                                    </div>
                                    <span class="nv-flyout-count">{{ count($menuItem->getChildren()) }}</span>
                                </div>
                                <div class="nv-flyout-body">
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        @php
                                            $subKey = $subMenuItem->getKey();
                                            $subCfg = $subItemConfig[$subKey] ?? ['bg' => '#F1F5F9', 'color' => '#64748B', 'badge' => ''];
                                            $subSvg = $subItemSvgs[$subKey] ?? '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>';
                                            $showBadge = !empty($subCfg['badge']) && strtolower(trim($subCfg['badge'])) !== strtolower(trim($subMenuItem->getName()));
                                        @endphp
                                        <a href="{{ $subMenuItem->getUrl() }}" class="nv-sub {{ $subMenuItem->isActive() ? 'nv-sub-active' : '' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="nv-sub-icon" style="background: {{ $subCfg['bg'] }}; color: {{ $subCfg['color'] }};">
                                                    {!! $subSvg !!}
                                                </span>
                                                <span class="nv-sub-text">{{ $subMenuItem->getName() }}</span>
                                            </div>
                                            @if($showBadge)
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

<style>
    /* ═══════════════════════════════════════════════════════
       2027 AURORA SIDEBAR — Premium Enterprise SaaS
       ═══════════════════════════════════════════════════════ */

    #admin-sidebar {
        background: linear-gradient(175deg, #f8faff 0%, #f0f4fb 35%, #eaeff8 70%, #e6ebf4 100%) !important;
        border-right: 1px solid rgba(196, 207, 226, 0.55) !important;
        box-shadow: 2px 0 24px rgba(15, 23, 42, 0.04), 1px 0 4px rgba(15, 23, 42, 0.02) !important;
    }
    .dark #admin-sidebar {
        background: linear-gradient(175deg, #0f172a 0%, #0c1322 60%, #0a0f1c 100%) !important;
        border-right: 1px solid rgba(51, 65, 85, 0.5) !important;
        box-shadow: 2px 0 24px rgba(0, 0, 0, 0.3) !important;
    }

    /* Scrollbar */
    #admin-sidebar .journal-scroll::-webkit-scrollbar { width: 3px; }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 99px; }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.6); }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-track { background: transparent; }

    /* ── Menu Link ── */
    .nv-link { color: #475569; border: 1px solid transparent; }
    .dark .nv-link { color: #cbd5e1; }
    .nv-label { color: #334155; }
    .dark .nv-label { color: #e2e8f0; }
    .nv-chevron { color: #94a3b8; }
    .dark .nv-chevron { color: #64748b; }

    /* ── Hover ── */
    .nv-row:hover > .nv-link:not(.nv-active) {
        background: rgba(255, 255, 255, 0.75);
        border-color: rgba(196, 207, 226, 0.55);
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(255,255,255,0.5) inset;
    }
    .nv-row:hover > .nv-link:not(.nv-active) .nv-label { color: #0f172a; font-weight: 700; }
    .nv-row:hover > .nv-link:not(.nv-active) .nv-icon { transform: scale(1.07); }
    .nv-row:hover > .nv-link:not(.nv-active) .nv-chevron { color: #3b82f6; transform: translateX(2px); }
    .dark .nv-row:hover > .nv-link:not(.nv-active) {
        background: rgba(30, 41, 59, 0.7);
        border-color: rgba(51, 65, 85, 0.6);
    }
    .dark .nv-row:hover > .nv-link:not(.nv-active) .nv-label { color: #f1f5f9; }

    /* ── Active ── */
    .nv-active {
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%) !important;
        border-color: transparent !important;
        box-shadow: 0 4px 18px rgba(59,130,246,0.3), 0 1px 3px rgba(99,102,241,0.2) !important;
    }
    .nv-active .nv-label { color: #fff !important; font-weight: 700 !important; }
    .nv-active .nv-icon {
        background: rgba(255,255,255,0.2) !important; border-color: rgba(255,255,255,0.3) !important;
        color: #fff !important; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.15), 0 1px 3px rgba(0,0,0,0.15) !important;
    }
    .nv-active .nv-chevron { color: rgba(255,255,255,0.8) !important; }

    /* ═══════════════════════════════════════════════════════
       FLYOUT — Pure CSS hover show, JS vertical positioning
       ═══════════════════════════════════════════════════════ */

    .nv-flyout {
        position: fixed;
        z-index: 10020;
        width: 260px;
        /* Flush against sidebar: no gap = unbroken hover zone */
        left: 220px;
        display: none;
        pointer-events: none;
        padding-left: 4px; /* tiny visual gap, but padding keeps hover zone continuous */
    }
    [dir="rtl"] .nv-flyout {
        left: auto;
        right: 220px;
        padding-left: 0;
        padding-right: 4px;
    }
    .group-[.sidebar-collapsed]\/container .nv-flyout { left: 70px; }
    [dir="rtl"] .group-[.sidebar-collapsed]\/container .nv-flyout { right: 70px; left: auto; }

    /* SHOW on hover — the row includes the flyout, so hovering on flyout keeps row hovered */
    .nv-row.nv-has-flyout:hover > .nv-flyout {
        display: block !important;
        pointer-events: auto;
        animation: nvSlideIn 0.16s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Flyout Inner (glassmorphism card) */
    .nv-flyout-inner {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(24px) saturate(180%);
        -webkit-backdrop-filter: blur(24px) saturate(180%);
        border: 1px solid rgba(196, 207, 226, 0.65);
        border-radius: 14px;
        box-shadow:
            0 20px 40px -10px rgba(15, 23, 42, 0.14),
            0 8px 20px -6px rgba(15, 23, 42, 0.07),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        overflow: hidden;
    }
    .dark .nv-flyout-inner {
        background: rgba(15, 23, 42, 0.92);
        border-color: rgba(51, 65, 85, 0.6);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
    }

    /* Header */
    .nv-flyout-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px 8px;
        border-bottom: 1px solid rgba(226,232,240,0.6);
        background: linear-gradient(135deg, rgba(248,250,252,0.7) 0%, rgba(241,245,249,0.4) 100%);
    }
    .dark .nv-flyout-head { border-bottom-color: rgba(51,65,85,0.4); background: linear-gradient(135deg, rgba(30,41,59,0.5) 0%, rgba(15,23,42,0.3) 100%); }
    .nv-dot { width: 8px; height: 8px; border-radius: 50%; }
    .nv-flyout-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; }
    .dark .nv-flyout-title { color: #e2e8f0; }
    .nv-flyout-count { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: rgba(241,245,249,0.85); color: #64748b; border: 1px solid rgba(226,232,240,0.5); }
    .dark .nv-flyout-count { background: rgba(30,41,59,0.7); color: #94a3b8; border-color: rgba(51,65,85,0.5); }

    /* Body */
    .nv-flyout-body { padding: 5px; display: flex; flex-direction: column; gap: 2px; }

    /* Sub Item */
    .nv-sub {
        display: flex; align-items: center; justify-content: space-between;
        padding: 7px 10px; border-radius: 10px;
        font-size: 12px; font-weight: 600; color: #475569;
        background: rgba(255,255,255,0.55);
        border: 1px solid rgba(226,232,240,0.45);
        transition: all 0.15s ease; text-decoration: none;
    }
    .nv-sub:hover {
        background: linear-gradient(135deg, rgba(219,234,254,0.65) 0%, rgba(224,242,254,0.45) 100%);
        border-color: rgba(147,197,253,0.55);
        color: #1d4ed8;
        transform: translateX(3px);
        box-shadow: 0 2px 8px rgba(59,130,246,0.1);
    }
    .dark .nv-sub { color: #cbd5e1; background: rgba(30,41,59,0.45); border-color: rgba(51,65,85,0.35); }
    .dark .nv-sub:hover { background: rgba(30,58,138,0.35); border-color: rgba(59,130,246,0.35); color: #93c5fd; }
    .nv-sub-icon { width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .nv-sub-text { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nv-sub-arr { width: 12px; height: 12px; flex-shrink: 0; margin-left: 4px; color: #cbd5e1; transition: all 0.15s ease; }
    .nv-sub:hover .nv-sub-arr { color: #3b82f6; transform: translateX(2px); }
    .dark .nv-sub-arr { color: #475569; }
    .nv-sub-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 99px; background: rgba(241,245,249,0.85); color: #64748b; border: 1px solid rgba(226,232,240,0.45); white-space: nowrap; }
    .dark .nv-sub-badge { background: rgba(30,41,59,0.7); color: #94a3b8; border-color: rgba(51,65,85,0.5); }

    .nv-sub-active {
        background: linear-gradient(135deg, rgba(219,234,254,0.75) 0%, rgba(238,242,255,0.55) 100%) !important;
        border-color: rgba(147,197,253,0.65) !important;
        color: #2563eb !important;
    }
    .dark .nv-sub-active { background: rgba(30,58,138,0.45) !important; border-color: rgba(59,130,246,0.45) !important; color: #93c5fd !important; }

    @keyframes nvSlideIn {
        from { opacity: 0; transform: translateX(-6px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    [dir="rtl"] .nv-row.nv-has-flyout:hover > .nv-flyout { animation-name: nvSlideInRtl; }
    @keyframes nvSlideInRtl {
        from { opacity: 0; transform: translateX(6px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }

    /* ── Desktop Layout Spacing ── */
    @media (min-width: 1024px) {
        .group\/container.sidebar-not-collapsed > div:last-child > div:first-child { padding-left: 248px !important; }
        .group\/container.sidebar-collapsed > div:last-child > div:first-child { padding-left: 85px !important; }
        [dir="rtl"] .group\/container.sidebar-not-collapsed > div:last-child > div:first-child { padding-left: 16px !important; padding-right: 248px !important; }
        [dir="rtl"] .group\/container.sidebar-collapsed > div:last-child > div:first-child { padding-left: 16px !important; padding-right: 85px !important; }
    }
</style>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="fixed bottom-0 w-full max-w-[220px] cursor-pointer border-t px-4 transition-all duration-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950 max-lg:hidden"
            style="background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); border-color: rgba(196,207,226,0.5);"
            :class="{'max-w-[70px]': isCollapsed}"
            :title="isCollapsed
                ? '@lang('admin::app.layouts.sidebar.expand')'
                : '@lang('admin::app.layouts.sidebar.collapse')'"
            @click="toggle"
        >
            <div class="flex items-center gap-2.5 p-1.5">
                <span
                    class="icon-left-arrow text-2xl transition-all"
                    :class="[isCollapsed ? 'ltr:rotate-[180deg] rtl:rotate-[0]' : 'ltr:rotate-[0] rtl:rotate-[180deg]']"
                ></span>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-sidebar-collapse', {
            template: '#v-sidebar-collapse-template',

            data() {
                return {
                    isCollapsed: {{ request()->cookie('sidebar_collapsed') ?? 0 }},
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

        /**
         * Flyout vertical positioning — vanilla JS, runs after Vue mounts.
         * Positions each flyout so its top aligns with the menu row.
         * Clamps to viewport so it never overflows off-screen.
         */
        window.addEventListener('load', function() {
            document.querySelectorAll('.nv-row.nv-has-flyout').forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    var flyout = row.querySelector('.nv-flyout');
                    if (!flyout) return;

                    var rowRect = row.getBoundingClientRect();
                    var viewH = window.innerHeight;

                    // Align flyout top with row top
                    var top = rowRect.top;

                    // After a frame, measure flyout height and clamp
                    requestAnimationFrame(function() {
                        var fH = flyout.offsetHeight || 200;
                        if (top + fH > viewH - 12) {
                            top = viewH - fH - 12;
                        }
                        if (top < 62) top = 62; // below header
                        flyout.style.top = top + 'px';
                    });

                    flyout.style.top = top + 'px';
                });
            });
        });
    </script>
@endPushOnce
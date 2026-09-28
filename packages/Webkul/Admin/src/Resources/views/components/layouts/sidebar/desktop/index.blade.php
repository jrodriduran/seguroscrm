@php
    $svgIcons = [
        // Dashboard: Modern 4-quadrant layout
        'dashboard' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',

        // Leads: Target / Crosshair
        'leads' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',

        // Quotes: Document proposal with lines
        'quotes' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>',

        // Policies (Book of Business): Shield with verified checkmark
        'policies' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',

        // Commissions: Banknote / Payment card
        'commissions' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',

        // Mail: Modern envelope
        'mail' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',

        // Hierarchy & Overrides: Organization tree
        'hierarchy' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="5" rx="1"/><rect x="3" y="16" width="6" height="5" rx="1"/><rect x="15" y="16" width="6" height="5" rx="1"/><path d="M12 8v4m0 0H6v4m6-4h6v4"/></svg>',

        // Activities: Lightning bolt / Zap
        'activities' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',

        // Analytics: Trending up chart
        'insurance_analytics' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',

        // Contacts: Users group
        'contacts' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',

        // Products: 3D Box
        'products' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',

        // Settings: Sliders / Gears
        'settings' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',

        // Configuration: Wrench / Adjust
        'configuration' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    ];

    $menuConfig = [
        'dashboard' => [
            'bg' => '#EEF2FF',
            'border' => '#C7D2FE',
            'color' => '#4F46E5',
        ],
        'leads' => [
            'bg' => '#E0F2FE',
            'border' => '#BAE6FD',
            'color' => '#0284C7',
        ],
        'quotes' => [
            'bg' => '#FEF3C7',
            'border' => '#FDE68A',
            'color' => '#D97706',
        ],
        'policies' => [
            'bg' => '#D1FAE5',
            'border' => '#A7F3D0',
            'color' => '#059669',
        ],
        'commissions' => [
            'bg' => '#DCFCE7',
            'border' => '#BBF7D0',
            'color' => '#16A34A',
        ],
        'mail' => [
            'bg' => '#DBEAFE',
            'border' => '#BFDBFE',
            'color' => '#2563EB',
        ],
        'hierarchy' => [
            'bg' => '#F3E8FF',
            'border' => '#E9D5FF',
            'color' => '#9333EA',
        ],
        'activities' => [
            'bg' => '#FFE4E6',
            'border' => '#FECDD3',
            'color' => '#E11D48',
        ],
        'insurance_analytics' => [
            'bg' => '#FAE8FF',
            'border' => '#F5D0FE',
            'color' => '#C026D3',
        ],
        'contacts' => [
            'bg' => '#CCFBF1',
            'border' => '#99F6E4',
            'color' => '#0D9488',
        ],
        'products' => [
            'bg' => '#FFEDD5',
            'border' => '#FED7AA',
            'color' => '#EA580C',
        ],
        'settings' => [
            'bg' => '#F1F5F9',
            'border' => '#E2E8F0',
            'color' => '#475569',
        ],
        'configuration' => [
            'bg' => '#F4F4F5',
            'border' => '#E4E4E7',
            'color' => '#52525B',
        ],
    ];

    $subItemSvgs = [
        // Mail
        'mail.inbox' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'mail.draft' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
        'mail.outbox' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 8 12 4 8 8"/><line x1="12" y1="4" x2="12" y2="16"/><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'mail.sent' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'mail.trash' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',

        // Contacts
        'contacts.persons' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'contacts.organizations' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',

        // Leads
        'leads.team_radar' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-8.4 8.4"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="M2 12h4"/><path d="M18 12h4"/></svg>',

        // Settings User
        'settings.user' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
        'settings.user.groups' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'settings.user.roles' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'settings.user.users' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a6 6 0 0 1 12 0v2"/></svg>',
        'settings.lead' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'settings.lead.pipelines' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="3" x2="21" y2="3"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="10" y1="15" x2="14" y2="15"/><line x1="12" y1="21" x2="12" y2="21"/></svg>',
        'settings.lead.sources' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'settings.lead.types' => '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    ];

    $subItemConfig = [
        // Mail
        'mail.inbox' => ['bg' => '#DBEAFE', 'color' => '#2563EB', 'badge' => ''],
        'mail.draft' => ['bg' => '#FEF3C7', 'color' => '#D97706', 'badge' => ''],
        'mail.outbox' => ['bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => ''],
        'mail.sent' => ['bg' => '#D1FAE5', 'color' => '#059669', 'badge' => ''],
        'mail.trash' => ['bg' => '#FFE4E6', 'color' => '#E11D48', 'badge' => ''],

        // Contacts
        'contacts.persons' => ['bg' => '#CCFBF1', 'color' => '#0D9488', 'badge' => 'Asegurados'],
        'contacts.organizations' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => 'Carriers'],

        // Leads
        'leads.team_radar' => ['bg' => '#F3E8FF', 'color' => '#9333EA', 'badge' => 'Radar SLA'],

        // Settings User
        'settings.user' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => ''],
        'settings.user.groups' => ['bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => 'Grupos'],
        'settings.user.roles' => ['bg' => '#FEF3C7', 'color' => '#D97706', 'badge' => 'Roles'],
        'settings.user.users' => ['bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => 'Usuarios'],
        'settings.lead' => ['bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => ''],
        'settings.lead.pipelines' => ['bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => 'Pipelines'],
        'settings.lead.sources' => ['bg' => '#D1FAE5', 'color' => '#059669', 'badge' => 'Fuentes'],
        'settings.lead.types' => ['bg' => '#FAE8FF', 'color' => '#C026D3', 'badge' => 'Tipos'],
    ];
@endphp

<div
    id="admin-sidebar"
    ref="sidebar"
    class="duration-80 fixed top-[60px] z-[10002] h-full w-[220px] bg-white pt-2.5 transition-all group-[.sidebar-collapsed]/container:w-[70px] dark:border-gray-800 dark:bg-slate-900 max-lg:hidden ltr:border-r rtl:border-l shadow-xs"
>
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-y-auto overflow-x-hidden group-[.sidebar-collapsed]/container:overflow-visible pb-14">
        <nav class="grid w-full gap-0.5 px-2.5">
            <!-- Navigation Menu -->
            @foreach (menu()->getItems('admin') as $menuItem)
                @php
                    $key = $menuItem->getKey();
                    $cfg = $menuConfig[$key] ?? [
                        'bg' => '#F1F5F9',
                        'border' => '#E2E8F0',
                        'color' => '#475569',
                    ];
                    $svg = $svgIcons[$key] ?? '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
                    $isItemActive = (bool) $menuItem->isActive();
                    $isLowerItem = in_array($key, ['contacts', 'products', 'settings', 'configuration']);
                @endphp

                <div
                    class="group/item relative my-0.5 {{ $isItemActive ? 'active' : 'inactive' }}"
                    @mouseenter="hoveringMenu = '{{$key}}'"
                    @mouseleave="hoveringMenu == '{{$key}}' ? hoveringMenu = '' : {}"
                >
                    <a
                        class="ghl-menu-link flex gap-2.5 px-2.5 py-2 items-center cursor-pointer rounded-xl transition-all duration-200 peer"
                        :class="{
                            'ghl-menu-active': {{ $isItemActive ? 'true' : 'false' }},
                            'ghl-menu-deployed': hoveringMenu == '{{$key}}' && !{{ $isItemActive ? 'true' : 'false' }},
                            'ghl-menu-hover': hoveringMenu != '{{$key}}' && !{{ $isItemActive ? 'true' : 'false' }}
                        }"
                        href="{{ ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren() ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                        @click="isMenuActive = !isMenuActive; hoveringMenu = '{{$key}}';"
                    >
                        <!-- High-Tech Pastel Icon Container -->
                        <span
                            class="ghl-icon-badge w-8 h-8 rounded-[10px] flex items-center justify-center shrink-0 transition-transform duration-200 group-hover/item:scale-105"
                            style="background-color: {{ $cfg['bg'] }}; border: 1px solid {{ $cfg['border'] }}; color: {{ $cfg['color'] }};"
                        >
                            {!! $svg !!}
                        </span>

                        <!-- Menu Name & Chevron -->
                        <div class="flex-1 min-w-0 flex justify-between items-center whitespace-nowrap group-[.sidebar-collapsed]/container:hidden">
                            <span class="ghl-menu-text text-[13px] font-semibold text-slate-700 dark:text-slate-200 tracking-tight truncate transition-colors">
                                {{ $menuItem->getName() }}
                            </span>
                        
                            @if ( ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren())
                                <span class="ghl-menu-arrow text-slate-400 group-hover/item:text-slate-600 dark:text-slate-500 text-xs font-bold transition-transform duration-200 group-hover/item:translate-x-0.5 shrink-0 pl-1">
                                    ›
                                </span>
                            @endif
                        </div>
                    </a>

                    <!-- GoHighLevel Popout Submenu Card -->
                    @if (
                        ! in_array($key, ['settings', 'configuration'])
                        && $menuItem->haveChildren()
                    )
                        <div
                            class="ghl-flyout-card fixed z-[10010] w-[256px] max-lg:hidden {{ $isLowerItem ? 'ghl-flyout-bottom' : '' }}"
                            :class="{'!flex': hoveringMenu == '{{$key}}'}"
                            style="left: 226px;"
                        >
                            <div class="ghl-card-inner w-full flex flex-col rounded-2xl border overflow-hidden">
                                <!-- Card Header -->
                                <div class="px-3.5 py-2.5 bg-gradient-to-r from-blue-50/80 via-slate-50 to-white dark:from-slate-800 dark:to-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shadow-xs" style="background-color: {{ $cfg['color'] }};"></span>
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                                            {{ $menuItem->getName() }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                        {{ count($menuItem->getChildren()) }}
                                    </span>
                                </div>

                                <!-- Submenu Options List -->
                                <div class="p-2 space-y-1">
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        @php
                                            $subKey = $subMenuItem->getKey();
                                            $subCfg = $subItemConfig[$subKey] ?? [
                                                'bg' => '#F1F5F9',
                                                'color' => '#64748B',
                                                'badge' => '',
                                            ];
                                            $subSvg = $subItemSvgs[$subKey] ?? '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>';
                                            $showBadge = !empty($subCfg['badge']) && strtolower(trim($subCfg['badge'])) !== strtolower(trim($subMenuItem->getName()));
                                        @endphp
                                        <a
                                            href="{{ $subMenuItem->getUrl() }}"
                                            class="ghl-subitem-row flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/90 dark:hover:bg-slate-800 transition-all duration-150 {{ $subMenuItem->isActive() ? 'ghl-subitem-active' : '' }}"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shadow-2xs shrink-0"
                                                    style="background-color: {{ $subCfg['bg'] }}; color: {{ $subCfg['color'] }};"
                                                >
                                                    {!! $subSvg !!}
                                                </span>
                                                <span class="truncate whitespace-nowrap">{{ $subMenuItem->getName() }}</span>
                                            </div>

                                            @if($showBadge)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 shrink-0 ml-1">
                                                    {{ $subCfg['badge'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600 text-xs font-bold shrink-0 ml-1">›</span>
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

    <!-- Collapse menu -->
    <v-sidebar-collapse></v-sidebar-collapse>

    {!! view_render_event('admin.layout.sidebar.toggle.after') !!}
</div>

<style>
    /* GoHighLevel Modern SaaS Aesthetics */
    #admin-sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.03) !important;
    }
    .dark #admin-sidebar {
        background-color: #0f172a !important;
        border-right: 1px solid #1e293b !important;
    }

    /* Clean Scrollbar inside Sidebar */
    #admin-sidebar .journal-scroll::-webkit-scrollbar {
        width: 4px;
    }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(203, 213, 225, 0.6);
        border-radius: 9999px;
    }
    #admin-sidebar .journal-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Active Link in GoHighLevel Style */
    .ghl-menu-active {
        background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3) !important;
        border-radius: 0.75rem !important;
    }
    .ghl-menu-active .ghl-menu-text {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .ghl-menu-active .ghl-icon-badge {
        background: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.35) !important;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3) !important;
    }
    .ghl-menu-active .ghl-icon-badge svg {
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.25));
    }
    .ghl-menu-active .ghl-menu-arrow {
        color: #ffffff !important;
    }

    /* Deployed menu item (notable background when submenus pop out) */
    .ghl-menu-deployed {
        background-color: #E0F2FE !important;
        border: 1px solid #7DD3FC !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.16) !important;
    }
    .ghl-menu-deployed .ghl-menu-text {
        color: #0369A1 !important;
        font-weight: 700 !important;
    }
    .ghl-menu-deployed .ghl-menu-arrow {
        color: #0284C7 !important;
        transform: translateX(2px) !important;
    }
    .dark .ghl-menu-deployed {
        background-color: #0c4a6e !important;
        border-color: #0284c7 !important;
    }

    /* Hover Link */
    .ghl-menu-hover:hover {
        background-color: #f8fafc !important;
        border-radius: 0.75rem !important;
        transform: translateX(3px) !important;
    }
    .dark .ghl-menu-hover:hover {
        background-color: #1e293b !important;
    }

    /* Popout Flyout Card (GoHighLevel floating menu) */
    .ghl-flyout-card {
        display: none !important;
        margin-top: -46px !important;
        z-index: 10020 !important;
    }
    .ghl-flyout-card.\!flex,
    .group\/item:hover > .ghl-flyout-card {
        display: flex !important;
        animation: ghlFlyoutSlide 0.16s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    /* Smooth hover bridge so cursor never drops flyout */
    .ghl-flyout-card::before {
        content: '';
        position: absolute;
        top: -12px;
        bottom: -12px;
        left: -20px;
        width: 22px;
    }
    .ghl-flyout-bottom {
        margin-top: -120px !important;
    }
    .group-[.sidebar-collapsed]/container .ghl-flyout-card {
        left: 78px !important;
    }
    [dir="rtl"] .ghl-flyout-card {
        left: auto !important;
        right: 226px !important;
    }
    [dir="rtl"] .ghl-flyout-card::before {
        left: auto;
        right: -20px;
    }
    [dir="rtl"] .group-[.sidebar-collapsed]/container .ghl-flyout-card {
        right: 78px !important;
    }

    /* Card Inner Background: Notable Soft Gray/Blue Tone */
    .ghl-card-inner {
        background-color: #F8FAFC !important;
        border-color: #CBD5E1 !important;
        box-shadow: 0 20px 35px -8px rgba(15, 23, 42, 0.18), 0 8px 16px -4px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(15, 23, 42, 0.06) !important;
    }
    .dark .ghl-card-inner {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        box-shadow: 0 25px 45px -8px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
    }

    /* Submenu Row Individual Card */
    .ghl-subitem-row {
        background-color: #ffffff !important;
        border: 1px solid #E2E8F0 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
    }
    .ghl-subitem-row:hover {
        background-color: #E0F2FE !important;
        border-color: #BAE6FD !important;
        color: #0284C7 !important;
        transform: translateX(3px) !important;
    }
    .dark .ghl-subitem-row {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark .ghl-subitem-row:hover {
        background-color: #0c4a6e !important;
        border-color: #0284c7 !important;
    }

    /* Active Subitem inside Popout */
    .ghl-subitem-active {
        background-color: #EFF6FF !important;
        color: #2563eb !important;
        border-color: #93C5FD !important;
        font-weight: 700 !important;
    }
    .dark .ghl-subitem-active {
        background-color: #1e3a8a !important;
        color: #93c5fd !important;
        border-color: #2563eb !important;
    }

    @keyframes ghlFlyoutSlide {
        from {
            opacity: 0;
            transform: translateX(-6px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    /* Desktop layout spacing guarantees main content is never covered */
    @media (min-width: 1024px) {
        .group\/container.sidebar-not-collapsed > div:last-child > div:first-child {
            padding-left: 236px !important;
        }
        .group\/container.sidebar-collapsed > div:last-child > div:first-child {
            padding-left: 85px !important;
        }
        [dir="rtl"] .group\/container.sidebar-not-collapsed > div:last-child > div:first-child {
            padding-left: 16px !important;
            padding-right: 236px !important;
        }
        [dir="rtl"] .group\/container.sidebar-collapsed > div:last-child > div:first-child {
            padding-left: 16px !important;
            padding-right: 85px !important;
        }
    }
</style>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="fixed bottom-0 w-full max-w-[220px] cursor-pointer border-t border-gray-200 bg-white px-4 transition-all duration-300 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-950 max-lg:hidden"
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
    </script>
@endPushOnce
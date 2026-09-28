@php
    $menuConfig = [
        'dashboard' => [
            'icon' => '📊',
            'bg' => '#EEF2FF',
            'color' => '#4F46E5',
        ],
        'leads' => [
            'icon' => '🎯',
            'bg' => '#E0F2FE',
            'color' => '#0284C7',
        ],
        'quotes' => [
            'icon' => '📑',
            'bg' => '#FEF3C7',
            'color' => '#D97706',
        ],
        'policies' => [
            'icon' => '🛡️',
            'bg' => '#D1FAE5',
            'color' => '#059669',
        ],
        'commissions' => [
            'icon' => '💵',
            'bg' => '#DCFCE7',
            'color' => '#16A34A',
        ],
        'mail' => [
            'icon' => '✉️',
            'bg' => '#DBEAFE',
            'color' => '#2563EB',
        ],
        'hierarchy' => [
            'icon' => '🏛️',
            'bg' => '#F3E8FF',
            'color' => '#9333EA',
        ],
        'activities' => [
            'icon' => '⚡',
            'bg' => '#FFE4E6',
            'color' => '#E11D48',
        ],
        'insurance_analytics' => [
            'icon' => '📈',
            'bg' => '#FAE8FF',
            'color' => '#C026D3',
        ],
        'contacts' => [
            'icon' => '👥',
            'bg' => '#CCFBF1',
            'color' => '#0D9488',
        ],
        'products' => [
            'icon' => '📦',
            'bg' => '#FFEDD5',
            'color' => '#EA580C',
        ],
        'settings' => [
            'icon' => '⚙️',
            'bg' => '#F1F5F9',
            'color' => '#475569',
        ],
        'configuration' => [
            'icon' => '🔧',
            'bg' => '#F4F4F5',
            'color' => '#52525B',
        ],
    ];

    $subItemConfig = [
        // Mail
        'mail.inbox' => ['icon' => '📥', 'bg' => '#DBEAFE', 'color' => '#2563EB', 'badge' => 'Inbox'],
        'mail.draft' => ['icon' => '📝', 'bg' => '#FEF3C7', 'color' => '#D97706', 'badge' => 'Draft'],
        'mail.outbox' => ['icon' => '📤', 'bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => 'Outbox'],
        'mail.sent' => ['icon' => '🚀', 'bg' => '#D1FAE5', 'color' => '#059669', 'badge' => 'Sent'],
        'mail.trash' => ['icon' => '🗑️', 'bg' => '#FFE4E6', 'color' => '#E11D48', 'badge' => 'Trash'],

        // Contacts
        'contacts.persons' => ['icon' => '👤', 'bg' => '#CCFBF1', 'color' => '#0D9488', 'badge' => 'Asegurados'],
        'contacts.organizations' => ['icon' => '🏢', 'bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => 'Carriers'],

        // Leads
        'leads.team_radar' => ['icon' => '🛰️', 'bg' => '#F3E8FF', 'color' => '#9333EA', 'badge' => 'Radar SLA'],

        // Settings User
        'settings.user' => ['icon' => '👥', 'bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => ''],
        'settings.user.groups' => ['icon' => '👥', 'bg' => '#EEF2FF', 'color' => '#4F46E5', 'badge' => 'Grupos'],
        'settings.user.roles' => ['icon' => '🛡️', 'bg' => '#FEF3C7', 'color' => '#D97706', 'badge' => 'Roles'],
        'settings.user.users' => ['icon' => '👤', 'bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => 'Usuarios'],
        'settings.lead' => ['icon' => '🎯', 'bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => ''],
        'settings.lead.pipelines' => ['icon' => '📊', 'bg' => '#E0F2FE', 'color' => '#0284C7', 'badge' => 'Pipelines'],
        'settings.lead.sources' => ['icon' => '🌐', 'bg' => '#D1FAE5', 'color' => '#059669', 'badge' => 'Fuentes'],
        'settings.lead.types' => ['icon' => '🏷️', 'bg' => '#FAE8FF', 'color' => '#C026D3', 'badge' => 'Tipos'],
    ];
@endphp

<div
    id="admin-sidebar"
    ref="sidebar"
    class="duration-80 fixed top-[60px] z-[10002] h-full w-[205px] border-gray-200 bg-white pt-3 transition-all group-[.sidebar-collapsed]/container:w-[70px] dark:border-gray-800 dark:bg-gray-900 max-lg:hidden ltr:border-r rtl:border-l shadow-xs"
>
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-y-auto overflow-x-hidden group-[.sidebar-collapsed]/container:overflow-visible pb-12">
        <nav class="grid w-full gap-1 px-2.5">
            <!-- Navigation Menu -->
            @foreach (menu()->getItems('admin') as $menuItem)
                @php
                    $key = $menuItem->getKey();
                    $cfg = $menuConfig[$key] ?? [
                        'icon' => '⚡',
                        'bg' => '#F1F5F9',
                        'color' => '#475569',
                    ];
                    $isItemActive = $menuItem->isActive() == 'active';
                @endphp

                <div class="group/item relative my-0.5 {{ $menuItem->isActive() ? 'active' : 'inactive' }}">
                    <a
                        class="ghl-menu-link flex gap-2.5 px-2.5 py-2 items-center cursor-pointer rounded-xl transition-all duration-200 peer"
                        :class="{
                            'ghl-menu-active': {{ $isItemActive ? 'true' : 'false' }} || hoveringMenu == '{{$key}}',
                            'ghl-menu-hover': hoveringMenu != '{{$key}}' && {{ $isItemActive ? 'false' : 'true' }}
                        }"
                        href="{{ ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren() ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                        @mouseleave="!isMenuActive ? hoveringMenu = '' : {}"
                        @mouseover="hoveringMenu='{{$key}}'"
                        @click="isMenuActive = !isMenuActive"
                    >
                        <!-- Pastel Icon Badge -->
                        <span
                            class="ghl-icon-badge w-8 h-8 rounded-xl flex items-center justify-center text-sm font-semibold shrink-0 shadow-xs transition-transform duration-200 group-hover/item:scale-105"
                            style="background-color: {{ $cfg['bg'] }}; color: {{ $cfg['color'] }};"
                        >
                            {{ $cfg['icon'] }}
                        </span>

                        <!-- Menu Name & Arrow -->
                        <div class="flex-1 flex justify-between items-center whitespace-nowrap group-[.sidebar-collapsed]/container:hidden">
                            <span class="ghl-menu-text text-[13px] font-semibold text-slate-700 dark:text-slate-200 tracking-tight transition-colors">
                                {{ $menuItem->getName() }}
                            </span>
                        
                            @if ( ! in_array($key, ['settings', 'configuration']) && $menuItem->haveChildren())
                                <span class="ghl-menu-arrow text-slate-400 group-hover/item:text-slate-600 dark:text-slate-500 text-xs font-bold transition-transform duration-200 group-hover/item:translate-x-0.5">
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
                            class="ghl-flyout-card fixed z-[10010] w-[240px] max-lg:hidden"
                            :class="[isMenuActive && (hoveringMenu == '{{$key}}') ? '!flex' : 'hidden']"
                            style="left: 212px;"
                        >
                            <div class="ghl-card-inner w-full flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden">
                                <!-- Card Header -->
                                <div class="px-4 py-2.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $cfg['color'] }};"></span>
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            {{ $menuItem->getName() }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ count($menuItem->getChildren()) }}
                                    </span>
                                </div>

                                <!-- Submenu Options List -->
                                <div class="p-2 space-y-1">
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        @php
                                            $subKey = $subMenuItem->getKey();
                                            $subCfg = $subItemConfig[$subKey] ?? [
                                                'icon' => '🔹',
                                                'bg' => '#F1F5F9',
                                                'color' => '#64748B',
                                                'badge' => '',
                                            ];
                                        @endphp
                                        <a
                                            href="{{ $subMenuItem->getUrl() }}"
                                            class="ghl-subitem-row flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800 transition-all duration-150 {{ $subMenuItem->isActive() == 'active' ? 'ghl-subitem-active' : '' }}"
                                        >
                                            <div class="flex items-center gap-2.5">
                                                <span
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shadow-2xs shrink-0"
                                                    style="background-color: {{ $subCfg['bg'] }}; color: {{ $subCfg['color'] }};"
                                                >
                                                    {{ $subCfg['icon'] }}
                                                </span>
                                                <span class="whitespace-nowrap">{{ $subMenuItem->getName() }}</span>
                                            </div>

                                            @if(!empty($subCfg['badge']))
                                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                                    {{ $subCfg['badge'] }}
                                                </span>
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
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.02) !important;
    }
    .dark #admin-sidebar {
        background-color: #0f172a !important;
        border-right: 1px solid #1e293b !important;
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
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3) !important;
    }
    .ghl-menu-active .ghl-menu-arrow {
        color: #ffffff !important;
    }

    /* Hover Link */
    .ghl-menu-hover:hover {
        background-color: #f1f5f9 !important;
        border-radius: 0.75rem !important;
        transform: translateX(3px) !important;
    }
    .dark .ghl-menu-hover:hover {
        background-color: #1e293b !important;
    }

    /* Popout Flyout Card (GoHighLevel floating menu) */
    .ghl-flyout-card {
        margin-top: -16px !important; /* Statically centered next to the clicked menu item */
        animation: ghlFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .group-[.sidebar-collapsed]/container .ghl-flyout-card {
        left: 76px !important;
    }
    [dir="rtl"] .ghl-flyout-card {
        left: auto !important;
        right: 212px !important;
    }
    [dir="rtl"] .group-[.sidebar-collapsed]/container .ghl-flyout-card {
        right: 76px !important;
    }

    .ghl-card-inner {
        box-shadow: 0 20px 35px -8px rgba(0, 0, 0, 0.14), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
    }
    .dark .ghl-card-inner {
        box-shadow: 0 25px 45px -8px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
    }

    /* Active Subitem inside Popout */
    .ghl-subitem-active {
        background-color: #eff6ff !important;
        color: #2563eb !important;
        border: 1px solid #bfdbfe !important;
    }
    .dark .ghl-subitem-active {
        background-color: #1e3a8a !important;
        color: #93c5fd !important;
        border-color: #2563eb !important;
    }

    @keyframes ghlFadeIn {
        from {
            opacity: 0;
            transform: translateX(-6px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }
</style>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="fixed bottom-0 w-full max-w-[205px] cursor-pointer border-t border-gray-200 bg-white px-4 transition-all duration-300 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-950 max-lg:hidden"
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
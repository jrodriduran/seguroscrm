<div
    id="admin-sidebar"
    ref="sidebar"
    class="duration-80 fixed top-[60px] z-[10002] h-full w-[200px] border-gray-300 bg-white pt-4 transition-all group-[.sidebar-collapsed]/container:w-[70px] dark:border-gray-800 dark:bg-gray-900 max-lg:hidden ltr:border-r rtl:border-l"
>
    <div class="journal-scroll h-[calc(100vh-100px)] overflow-hidden group-[.sidebar-collapsed]/container:overflow-visible">
        <nav class="sidebar-rounded grid w-full gap-2">
            <!-- Navigation Menu -->
            @foreach (menu()->getItems('admin') as $menuItem)
                <div class="px-4 group/item {{ $menuItem->isActive() ? 'active' : 'inactive' }}">
                    <a
                        class="flex gap-2 p-1.5 items-center cursor-pointer hover:rounded-lg {{ $menuItem->isActive() == 'active' ? 'bg-brandColor rounded-lg' : ' hover:bg-gray-100 hover:dark:bg-gray-950' }} peer transition-colors"
                        :style="hoveringMenu == '{{$menuItem->getKey()}}' ? 'background-color: var(--brand-color, #0E90D9) !important; color: #ffffff !important; border-radius: 0.5rem !important;' : ''"
                        href="{{ ! in_array($menuItem->getKey(), ['settings', 'configuration']) && $menuItem->haveChildren() ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                        @mouseleave="!isMenuActive ? hoveringMenu = '' : {}"
                        @mouseover="hoveringMenu='{{$menuItem->getKey()}}'"
                        @click="isMenuActive = !isMenuActive"
                    >
                        <span
                            class="{{ $menuItem->getIcon() }} text-2xl {{ $menuItem->isActive() ? 'text-white' : ''}}"
                            :style="hoveringMenu == '{{$menuItem->getKey()}}' ? 'color: #ffffff !important;' : ''"
                        ></span>

                        <div
                            class="flex-1 flex justify-between items-center text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap group-[.sidebar-collapsed]/container:hidden {{ $menuItem->isActive() ? 'text-white' : ''}} group"
                            :style="hoveringMenu == '{{$menuItem->getKey()}}' ? 'color: #ffffff !important;' : ''"
                        >
                            <p>{{ $menuItem->getName() }}</p>
                        
                            @if ( ! in_array($menuItem->getKey(), ['settings', 'configuration']) && $menuItem->haveChildren())
                                <i
                                    class="icon-right-arrow rtl:icon-left-arrow invisible text-2xl group-hover/item:visible transition-transform duration-200 {{ $menuItem->isActive() ? 'text-white' : ''}}"
                                    :style="hoveringMenu == '{{$menuItem->getKey()}}' ? 'color: #ffffff !important; visibility: visible !important;' : ''"
                                ></i>
                            @endif
                        </div>
                    </a>

                    <!-- Submenu Flyout -->
                    @if (
                        ! in_array($menuItem->getKey(), ['settings', 'configuration'])
                        && $menuItem->haveChildren()
                    )
                        <div
                            class="sidebar-flyout-panel fixed top-[54px] z-[10005] h-[calc(100vh-54px)] w-[220px] max-lg:hidden ltr:left-[200px] rtl:right-[200px] group-[.sidebar-collapsed]/container:ltr:left-[70px] group-[.sidebar-collapsed]/container:rtl:right-[70px] shadow-2xl border-r border-slate-300 dark:border-slate-800"
                            :class="[isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}') ? 'flex flex-col' : 'hidden']"
                            style="background-color: #f1f5f9;"
                        >
                            <div class="journal-scroll h-full overflow-y-auto px-2 pt-2.5 pb-6">
                                <nav class="grid w-full gap-1">
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        <div class="group/item {{ $subMenuItem->isActive() ? 'active' : 'inactive' }}">
                                            <a
                                                href="{{ $subMenuItem->getUrl() }}"
                                                class="sidebar-flyout-item flex gap-2.5 px-3 py-2 items-center cursor-pointer rounded-lg text-slate-700 hover:text-slate-900 hover:bg-slate-200 {{ $subMenuItem->isActive() == 'active' ? 'active' : '' }} peer transition-colors"
                                                style="{{ $subMenuItem->isActive() == 'active' ? 'background-color: var(--brand-color, #0E90D9) !important; color: #ffffff !important;' : '' }}"
                                            >
                                                <p class="font-medium text-sm whitespace-nowrap">
                                                    {{ $subMenuItem->getName() }}
                                                </p>
                                            </a>
                                        </div>
                                    @endforeach
                                </nav>
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
    .dark .sidebar-flyout-panel {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark .sidebar-flyout-item {
        color: #cbd5e1 !important;
    }
    .dark .sidebar-flyout-item:hover {
        background-color: #334155 !important;
        color: #ffffff !important;
    }
</style>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sidebar-collapse-template"
    >
        <div
            class="fixed bottom-0 w-full max-w-[200px] cursor-pointer border-t border-gray-200 bg-white px-4 transition-all duration-300 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-950 max-lg:hidden"
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
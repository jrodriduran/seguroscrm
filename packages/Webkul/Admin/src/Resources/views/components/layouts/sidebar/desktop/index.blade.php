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
                        :class="{'!bg-blue-600 !text-white rounded-lg shadow-sm': isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}')}"
                        href="{{ ! in_array($menuItem->getKey(), ['settings', 'configuration']) && $menuItem->haveChildren() ? 'javascript:void(0)' : $menuItem->getUrl() }}"
                        @mouseleave="!isMenuActive ? hoveringMenu = '' : {}"
                        @mouseover="hoveringMenu='{{$menuItem->getKey()}}'"
                        @click="isMenuActive = !isMenuActive"
                    >
                        <span
                            class="{{ $menuItem->getIcon() }} text-2xl {{ $menuItem->isActive() ? 'text-white' : ''}}"
                            :class="{'!text-white': isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}')}"
                        ></span>

                        <div
                            class="flex-1 flex justify-between items-center text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap group-[.sidebar-collapsed]/container:hidden {{ $menuItem->isActive() ? 'text-white' : ''}} group"
                            :class="{'!text-white font-semibold': isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}')}"
                        >
                            <p>{{ $menuItem->getName() }}</p>
                        
                            @if ( ! in_array($menuItem->getKey(), ['settings', 'configuration']) && $menuItem->haveChildren())
                                <i
                                    class="icon-right-arrow rtl:icon-left-arrow invisible text-2xl group-hover/item:visible transition-transform duration-200 {{ $menuItem->isActive() ? 'text-white' : ''}}"
                                    :class="{'!visible !text-white ltr:translate-x-0.5 rtl:-translate-x-0.5': isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}')}"
                                ></i>
                            @endif
                        </div>
                    </a>

                    <!-- Submenu -->
                    @if (
                        ! in_array($menuItem->getKey(), ['settings', 'configuration'])
                        && $menuItem->haveChildren()
                    )
                        <div
                            class="absolute top-0 hidden flex-col ltr:left-[200px] rtl:right-[199px]"
                            :class="[isMenuActive && (hoveringMenu == '{{$menuItem->getKey()}}') ? '!flex' : 'hidden']"
                        >
                            <div class="sidebar-rounded fixed z-[1000] h-full min-w-[220px] max-w-max bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-md pt-3 pb-6 shadow-2xl border-y border-r border-slate-300 dark:border-slate-700 max-lg:hidden ltr:border-r rtl:border-x">
                                <!-- Section Category Header -->
                                <div class="px-4 pb-2.5 mb-2 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
                                    <span class="{{ $menuItem->getIcon() }} text-lg text-blue-600 dark:text-blue-400"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ $menuItem->getName() }}</span>
                                </div>

                                <div class="journal-scroll h-[calc(100vh-140px)] overflow-hidden">
                                    <nav class="grid w-full gap-1.5">
                                        @foreach ($menuItem->getChildren() as $subMenuItem)
                                            <div class="px-3 group/item {{ $subMenuItem->isActive() ? 'active' : 'inactive' }}">
                                                <a
                                                    href="{{ $subMenuItem->getUrl() }}"
                                                    class="flex gap-2.5 px-3 py-2 items-center cursor-pointer rounded-lg transition-colors {{ $subMenuItem->isActive() == 'active' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-700 hover:text-blue-700 hover:bg-blue-100/80 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800' }} peer"
                                                >
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $subMenuItem->isActive() == 'active' ? 'bg-white' : 'bg-slate-400 group-hover/item:bg-blue-600' }}"></span>
                                                    <p class="font-medium text-xs whitespace-nowrap {{ $subMenuItem->isActive() ? 'text-white' : ''}}">
                                                        {{ $subMenuItem->getName() }}
                                                    </p>
                                                </a>
                                            </div>
                                        @endforeach
                                    </nav>
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
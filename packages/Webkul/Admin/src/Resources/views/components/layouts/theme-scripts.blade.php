{{-- Shell behaviours shared by every admin page (included from theme.blade.php, in <head>). --}}
<script>
    /**
     * Report this computer's time zone (used when the agency lets each user
     * follow their own computer's zone).
     */
    (function () {
        try {
            var zone = Intl.DateTimeFormat().resolvedOptions().timeZone;

            if (zone && document.cookie.indexOf('crm_tz=' + encodeURIComponent(zone)) === -1) {
                document.cookie = 'crm_tz=' + encodeURIComponent(zone) + '; path=/; max-age=31536000; SameSite=Lax';
            }
        } catch (error) {}
    })();

    /**
     * "Detect" button next to the time zone select in Configuration.
     */
    window.addEventListener('load', function () {
        setTimeout(function () {
            var select = document.querySelector('select[name$="[timezone][timezone]"], select[name="general.general.timezone.timezone"]');

            if (! select || select.dataset.nxDetect) {
                return;
            }

            select.dataset.nxDetect = '1';

            var button = document.createElement('button');

            button.type = 'button';
            button.className = 'secondary-button';
            button.style.marginTop = '8px';
            button.textContent = @json(trans('teamwork::app.configuration.timezone.detect'));
            button.addEventListener('click', function () {
                select.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.dispatchEvent(new Event('input', { bubbles: true }));
            });

            select.insertAdjacentElement('afterend', button);
        }, 300);
    });

    /**
     * Clickable rows and cards on custom screens: when a row-sized block
     * holds exactly one "Edit / View / Open" action, the whole block opens
     * the record (datagrids already do this on their own).
     */
    (function () {
        var ACTION = /^(editar|edit|ver|view|abrir|open|ver detalle|detalles?|details?)$/i;

        function actionIn(container) {
            var interactive = container.querySelectorAll('a[href], button, [role="button"], span.cursor-pointer');

            if (interactive.length > 6) {
                return null;
            }

            var actions = Array.prototype.filter.call(interactive, function (element) {
                return ACTION.test((element.innerText || '').trim()) || element.matches('.icon-edit, .icon-eye');
            });

            return actions.length === 1 ? actions[0] : null;
        }

        function rowFor(target) {
            var element = target;

            for (var depth = 0; depth < 5; depth++) {
                element = element && element.parentElement;

                if (! element || ! element.closest('.admin-main-content') || element.matches('.admin-main-content, .table-responsive, form')) {
                    return null;
                }

                if (element.closest('.table-responsive, .tw-modal, [role="dialog"]')) {
                    return null;
                }

                if (element.getBoundingClientRect().height > 220) {
                    return null;
                }

                var action = actionIn(element);

                if (action) {
                    return { row: element, action: action };
                }
            }

            return null;
        }

        var hovered = null;

        document.addEventListener('mouseover', function (event) {
            if (! event.target.closest || ! event.target.closest('.admin-main-content')) {
                return;
            }

            var found = rowFor(event.target);
            var row = found ? found.row : null;

            if (row !== hovered) {
                hovered && hovered.classList.remove('nx-row-clickable');
                row && row.classList.add('nx-row-clickable');
                hovered = row;
            }
        });

        document.addEventListener('click', function (event) {
            var target = event.target;

            if (event.defaultPrevented || event.button !== 0 || ! target.closest || ! target.closest('.admin-main-content')) {
                return;
            }

            if (target.closest('a, button, input, label, select, textarea, [role="button"], [class*="icon-"], .cursor-pointer')) {
                return;
            }

            if (String(window.getSelection && window.getSelection()).trim()) {
                return;
            }

            var found = rowFor(target);

            if (found) {
                found.action.click();
            }
        });
    })();

    /**
     * Emoji used as icons in older screens are swapped for line icons of the
     * same family as the rest of the interface. Works on Vue-rendered content
     * too (re-applied when the DOM changes).
     */
    (function () {
        var P = {
            alert: "<circle cx='12' cy='12' r='10'/><path d='M12 8v4M12 16h.01'/>",
            warn: "<path d='m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3'/><path d='M12 9v4M12 17h.01'/>",
            shield: "<path d='M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z'/><path d='m9 12 2 2 4-4'/>",
            download: "<path d='M12 15V3'/><path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/><path d='m7 10 5 5 5-5'/>",
            scale: "<path d='m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z'/><path d='m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z'/><path d='M7 21h10M12 3v18M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2'/>",
            clipboard: "<rect width='8' height='4' x='8' y='2' rx='1'/><path d='M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2'/>",
            dot: "<circle cx='12' cy='12' r='5' fill='currentColor'/>",
            printer: "<path d='M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2'/><path d='M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6'/><rect x='6' y='14' width='12' height='8' rx='1'/>",
            refresh: "<path d='M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8'/><path d='M21 3v5h-5'/><path d='M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16'/><path d='M8 16H3v5'/>",
            dollar: "<path d='M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'/>",
            sparkles: "<path d='M9.94 14.06 12 21l2.06-6.94L21 12l-6.94-2.06L12 3 9.94 9.94 3 12z'/>",
            pen: "<path d='M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z'/>",
            phone: "<rect width='14' height='20' x='5' y='2' rx='2'/><path d='M12 18h.01'/>",
            call: "<path d='M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z'/>",
            file: "<path d='M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z'/><path d='M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8'/>",
            card: "<rect width='20' height='14' x='2' y='5' rx='2'/><path d='M2 10h20'/>",
            chat: "<path d='M7.9 20A9 9 0 1 0 4 16.1L2 22Z'/>",
            users: "<path d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'/>",
            user: "<circle cx='12' cy='8' r='5'/><path d='M20 21a8 8 0 0 0-16 0'/>",
            trophy: "<path d='M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2Z'/>",
            check: "<path d='M20 6 9 17l-5-5'/>",
            zap: "<path d='M13 2 3 14h9l-1 8 10-12h-9l1-8z'/>",
            gear: "<path d='M20 7h-9M14 17H5'/><circle cx='17' cy='17' r='3'/><circle cx='7' cy='7' r='3'/>",
            x: "<path d='M18 6 6 18M6 6l12 12'/>",
            tree: "<rect x='16' y='16' width='6' height='6' rx='1'/><rect x='2' y='16' width='6' height='6' rx='1'/><rect x='9' y='2' width='6' height='6' rx='1'/><path d='M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3M12 12V8'/>",
            chart: "<path d='M3 3v16a2 2 0 0 0 2 2h16'/><path d='m19 9-5 5-4-4-3 3'/>",
            calendar: "<path d='M8 2v4M16 2v4'/><rect width='18' height='18' x='3' y='4' rx='2'/><path d='M3 10h18'/>",
            search: "<circle cx='11' cy='11' r='8'/><path d='m21 21-4.3-4.3'/>",
            mail: "<rect width='20' height='16' x='2' y='4' rx='2'/><path d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/>",
            heart: "<path d='M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'/>",
            pill: "<path d='m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z'/><path d='m8.5 8.5 7 7'/>",
            bell: "<path d='M10.268 21a2 2 0 0 0 3.464 0'/><path d='M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326'/>",
            clock: "<circle cx='12' cy='12' r='10'/><path d='M12 6v6l4 2'/>",
            pin: "<path d='M12 17v5'/><path d='M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z'/>",
            target: "<circle cx='12' cy='12' r='10'/><circle cx='12' cy='12' r='6'/><circle cx='12' cy='12' r='2'/>",
            rocket: "<path d='M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z'/><path d='m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z'/>",
            home: "<path d='M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8'/><path d='M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/>",
            lock: "<rect width='18' height='11' x='3' y='11' rx='2'/><path d='M7 11V7a5 5 0 0 1 10 0v4'/>",
            star: "<path d='M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z'/>",
            building: "<rect width='16' height='20' x='4' y='2' rx='2'/><path d='M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01'/>",
            doc: "<path d='M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z'/><path d='M14 2v4a2 2 0 0 0 2 2h4'/>",
            link: "<path d='M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71'/><path d='M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71'/>"
        };

        // emoji => [icon, tone]
        var MAP = {
            '\u{1F6A8}': ['alert', 'alert'], '⚠': ['warn', 'warn'], '\u{1F6E1}': ['shield', 'accent'], '\u{1F4E5}': ['download', ''], '⬇': ['download', ''],
            '⚖': ['scale', 'accent'], '\u{1F4CB}': ['clipboard', ''], '\u{1F534}': ['dot', 'alert'], '\u{1F7E2}': ['dot', 'ok'], '\u{1F7E1}': ['dot', 'warn'], '\u{1F7E0}': ['dot', 'warn'],
            '\u{1F5A8}': ['printer', ''], '\u{1F504}': ['refresh', ''], '\u{1F4B0}': ['dollar', 'money'], '\u{1F4B5}': ['dollar', 'money'], '\u{1F4B2}': ['dollar', 'money'],
            '\u{1F389}': ['sparkles', 'accent'], '✨': ['sparkles', 'accent'], '✍': ['pen', ''], '\u{1F4F2}': ['phone', ''], '\u{1F4F1}': ['phone', ''],
            '\u{1F4C4}': ['file', ''], '\u{1F4D1}': ['file', ''], '\u{1F4C3}': ['file', ''], '\u{1F4DD}': ['pen', ''], '\u{1F4B3}': ['card', ''], '\u{1F4AC}': ['chat', 'accent'],
            '\u{1F465}': ['users', ''], '\u{1F464}': ['user', ''], '\u{1F3C6}': ['trophy', 'warn'], '\u{1F947}': ['trophy', 'warn'], '✅': ['check', 'ok'], '✔': ['check', 'ok'],
            '⚡': ['zap', 'warn'], '⚙': ['gear', ''], '❌': ['x', 'alert'], '✖': ['x', 'alert'], '\u{1F333}': ['tree', 'ok'], '\u{1F332}': ['tree', 'ok'],
            '\u{1F4CA}': ['chart', 'accent'], '\u{1F4C8}': ['chart', 'ok'], '\u{1F4C9}': ['chart', 'alert'], '\u{1F4C5}': ['calendar', ''], '\u{1F5D3}': ['calendar', ''], '\u{1F4C6}': ['calendar', ''],
            '\u{1F50D}': ['search', ''], '\u{1F50E}': ['search', ''], '\u{1F4DE}': ['call', ''], '☎': ['call', ''], '✉': ['mail', ''], '\u{1F4E7}': ['mail', ''], '\u{1F4E8}': ['mail', ''],
            '\u{1F3E5}': ['heart', 'alert'], '❤': ['heart', 'alert'], '\u{1F48A}': ['pill', 'accent'], '\u{1F514}': ['bell', ''], '⏰': ['clock', 'warn'], '⏳': ['clock', 'warn'], '⌛': ['clock', 'warn'],
            '\u{1F4CC}': ['pin', ''], '\u{1F3AF}': ['target', 'accent'], '\u{1F680}': ['rocket', 'accent'], '\u{1F3E0}': ['home', ''], '\u{1F512}': ['lock', ''], '\u{1F510}': ['lock', ''],
            '⭐': ['star', 'warn'], '\u{1F3E2}': ['building', ''], '\u{1F3E6}': ['building', ''], '\u{1F4C1}': ['doc', ''], '\u{1F4C2}': ['doc', ''], '\u{1F517}': ['link', '']
        };

        var pattern = new RegExp('(' + Object.keys(MAP).join('|') + ')\\uFE0F?', 'gu');
        var SKIP = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, OPTION: 1, SELECT: 1, TITLE: 1 };

        function svgFor(emoji) {
            var entry = MAP[emoji] || ['sparkles', ''];
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '2');
            svg.setAttribute('stroke-linecap', 'round');
            svg.setAttribute('stroke-linejoin', 'round');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('class', 'nx-emoji' + (entry[1] ? ' is-' + entry[1] : ''));
            svg.innerHTML = P[entry[0]];

            return svg;
        }

        function process(node) {
            var text = node.nodeValue;

            if (! text) {
                return;
            }

            var found = text.match(pattern);

            if (! found) {
                return;
            }

            // Keep Vue's text node (so reactivity still works); icons go right before it.
            node.nodeValue = text.replace(pattern, '').replace(/^\s+/, '');

            var previous = node.previousSibling;

            if (previous && previous.nodeType === 1 && previous.classList && previous.classList.contains('nx-emoji')) {
                return;
            }

            found.forEach(function (emoji) {
                node.parentNode.insertBefore(svgFor(emoji.replace('️', '')), node);
            });
        }

        function scan(root) {
            if (! root || (root.nodeType === 1 && SKIP[root.nodeName])) {
                return;
            }

            if (root.nodeType === 3) {
                if (root.parentNode && ! SKIP[root.parentNode.nodeName]) {
                    process(root);
                }

                return;
            }

            if (root.nodeType !== 1) {
                return;
            }

            var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    return node.parentNode && ! SKIP[node.parentNode.nodeName] ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                }
            });

            var nodes = [];

            while (walker.nextNode()) {
                nodes.push(walker.currentNode);
            }

            nodes.forEach(process);
        }

        var queued = [];
        var scheduled = false;

        function flush() {
            scheduled = false;

            var batch = queued;

            queued = [];
            batch.forEach(scan);
        }

        function queue(node) {
            queued.push(node);

            if (! scheduled) {
                scheduled = true;
                requestAnimationFrame(flush);
            }
        }

        window.addEventListener('load', function () {
            setTimeout(function () {
                document.querySelectorAll('.admin-main-content, .nx-header, #admin-sidebar').forEach(scan);

                new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'characterData') {
                            queue(mutation.target);
                        } else {
                            mutation.addedNodes.forEach(queue);
                        }
                    });
                }).observe(document.getElementById('app') || document.body, { childList: true, subtree: true, characterData: true });
            }, 0);
        });
    })();
</script>

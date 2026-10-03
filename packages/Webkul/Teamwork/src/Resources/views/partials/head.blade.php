{{-- Teamwork styles that live outside the main theme (stage playbook). Loaded in <head>. --}}
<style>
    .tw-progress { height: 6px; border-radius: 99px; background: rgba(15, 23, 42, 0.08); overflow: hidden; }
    .tw-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #4f6bff, #10b981); transition: width .3s ease; }
    .dark .tw-progress { background: rgba(148, 163, 184, 0.15); }

    .tw-milestone { display: flex; align-items: flex-start; gap: 10px; padding: 6px 8px; border-radius: 10px; }
    .tw-milestone:hover { background: rgba(79, 107, 255, 0.05); }
    .tw-milestone-name { display: block; font-size: 13px; font-weight: 600; color: #0f172a; }
    .dark .tw-milestone-name { color: #f1f5f9; }
    .tw-milestone.is-done .tw-milestone-name { color: #059669; text-decoration: line-through; text-decoration-color: rgba(5, 150, 105, .45); }
    .tw-milestone-check, .tw-milestone-mark { flex: none; width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; font-size: 13px; font-weight: 800; }
    .tw-milestone-check { border: 1.5px solid rgba(15, 23, 42, 0.25); color: #fff; background: #fff; cursor: pointer; }
    .tw-milestone.is-done .tw-milestone-check { border-color: transparent; background: linear-gradient(135deg, #10b981, #059669); }
    .tw-milestone-check:hover { border-color: #4f6bff; }
    .tw-milestone-mark { color: #b45309; background: rgba(245, 158, 11, 0.12); }
    .tw-milestone.is-done .tw-milestone-mark { color: #059669; background: rgba(16, 185, 129, 0.14); }
    .dark .tw-milestone-check { background: #0b1220; border-color: rgba(148, 163, 184, 0.35); }

    .tw-playbook details summary { list-style: none; cursor: pointer; display: inline-flex; }
    .tw-playbook details summary::-webkit-details-marker { display: none; }

    .tw-milestone-row { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) auto auto; gap: 6px; align-items: center; }
    @media (max-width: 768px) { .tw-milestone-row { grid-template-columns: 1fr auto; } }

    .tw-kanban-milestones { display: inline-flex; align-items: center; gap: 4px; width: fit-content; padding: 1px 8px; border-radius: 99px; font-size: 11px; font-weight: 650; color: #4f46e5; background: rgba(79, 107, 255, 0.10); border: 1px solid rgba(79, 107, 255, 0.22); }
    .tw-kanban-milestones.is-ready { color: #047857; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.3); }
    .tw-kanban-milestones.is-blocked { color: #be123c; background: rgba(225, 29, 72, 0.10); border-color: rgba(225, 29, 72, 0.28); }
</style>

<script>
    (() => {
        const applyTheme = (theme) => {
            document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches));
            try { localStorage.setItem('atelier-theme', theme); } catch {}
            document.querySelectorAll('[data-theme-choice]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.themeChoice === theme)));
        };
        const selectedProjects = () => [...document.querySelectorAll('input[name="projects[]"]')];
        const updateSelection = () => {
            const inputs = selectedProjects();
            const checked = inputs.filter((input) => input.checked).length;
            const all = document.querySelector('[data-check-all]');
            if (all) { all.checked = inputs.length > 0 && checked === inputs.length; all.indeterminate = checked > 0 && checked < inputs.length; }
            document.querySelectorAll('[data-bulk-action]').forEach((button) => { button.disabled = checked === 0; button.toggleAttribute('data-disabled', checked === 0); });
            document.querySelectorAll('[data-selection-count]').forEach((label) => label.textContent = checked ? `Выбрано: ${checked}` : 'Выберите проекты для действия');
        };
        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-dialog-open]');
            if (opener) document.getElementById(opener.dataset.dialogOpen)?.showModal();
            if (event.target.closest('[data-theme-toggle]')) applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
            const choice = event.target.closest('[data-theme-choice]');
            if (choice) applyTheme(choice.dataset.themeChoice);
            if (event.target.closest('[data-dismiss]')) event.target.closest('[data-notice]')?.remove();
        });
        document.addEventListener('change', (event) => {
            if (event.target.matches('[data-check-all]')) selectedProjects().forEach((input) => input.checked = event.target.checked);
            if (event.target.matches('input[name="projects[]"], [data-check-all]')) updateSelection();
            const form = event.target.closest('form[data-auto-submit]');
            if (form) form.requestSubmit();
        });
        document.querySelectorAll('dialog[data-open-on-load]').forEach((dialog) => dialog.showModal());
        document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        }));
        try { applyTheme(localStorage.getItem('atelier-theme') || 'light'); } catch {}
        matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            try { if (localStorage.getItem('atelier-theme') === 'system') applyTheme('system'); } catch {}
        });
        updateSelection();
    })();
</script>

// E-Shongskar — small progressive enhancements. Every page works without this file.
(function () {
    'use strict';

    // Mobile navigation toggle
    document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var nav = document.querySelector('[data-nav]');
            var open = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.dropdown[open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });

    // Dismiss flash messages
    document.querySelectorAll('.alert-close').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('.alert').remove(); });
    });

    // Open / close <dialog> modals: <button data-open="dialogId">
    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var dlg = document.getElementById(btn.getAttribute('data-open'));
            if (!dlg) return;
            // Copy data-fill-* attributes into fields of the dialog
            Object.keys(btn.dataset).forEach(function (key) {
                if (key.indexOf('fill') !== 0) return;
                var name = key.slice(4).replace(/^./, function (c) { return c.toLowerCase(); });
                var target = dlg.querySelector('[data-field="' + name + '"]');
                if (!target) return;
                if ('value' in target && target.tagName !== 'DIV' && target.tagName !== 'SPAN') target.value = btn.dataset[key];
                else target.textContent = btn.dataset[key];
            });
            dlg.showModal();
        });
    });
    document.querySelectorAll('dialog.modal').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
        dlg.querySelectorAll('[data-close]').forEach(function (b) {
            b.addEventListener('click', function () { dlg.close(); });
        });
    });

    // Confirmation prompts: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Password confirmation match
    var pass = document.getElementById('password');
    var confirmPass = document.getElementById('password_confirm');
    if (pass && confirmPass) {
        var check = function () {
            confirmPass.setCustomValidity(confirmPass.value && confirmPass.value !== pass.value ? 'Passwords do not match' : '');
        };
        pass.addEventListener('input', check);
        confirmPass.addEventListener('input', check);
    }

    // Image preview for file inputs: <input type="file" data-preview="imgId">
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.getElementById(input.getAttribute('data-preview'));
            if (img && input.files && input.files[0]) {
                img.src = URL.createObjectURL(input.files[0]);
                img.classList.remove('hidden');
            }
        });
    });

    // Auto-submit filters: <select data-autosubmit>
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    // Keep the chat scrolled to the newest message
    var thread = document.getElementById('chatMessages');
    if (thread) thread.scrollTop = thread.scrollHeight;

    // SOS: attach the device location before sending, when the user allows it
    var sosForm = document.getElementById('sosForm');
    if (sosForm) {
        sosForm.addEventListener('submit', function (e) {
            if (sosForm.dataset.located || !navigator.geolocation) return;
            e.preventDefault();
            var btn = sosForm.querySelector('button[type=submit]');
            if (btn) btn.disabled = true;
            var send = function () { sosForm.dataset.located = '1'; sosForm.submit(); };
            navigator.geolocation.getCurrentPosition(function (pos) {
                sosForm.querySelector('[name=latitude]').value = pos.coords.latitude.toFixed(7);
                sosForm.querySelector('[name=longitude]').value = pos.coords.longitude.toFixed(7);
                send();
            }, send, { timeout: 6000, enableHighAccuracy: true });
        });
    }

    // Admin sidebar toggle on small screens
    var sideBtn = document.querySelector('[data-admin-menu]');
    if (sideBtn) {
        sideBtn.addEventListener('click', function () {
            document.querySelector('.admin-side').classList.toggle('open');
        });
    }
})();

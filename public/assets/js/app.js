(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function api(url, options = {}) {
        options.headers = Object.assign({}, options.headers, { 'X-CSRF-Token': csrfToken });
        return fetch(url, options).then(async (res) => {
            const data = await res.json().catch(() => ({ ok: false, error: 'Risposta non valida dal server.' }));
            if (!res.ok || !data.ok) {
                throw new Error(data.error || 'Errore sconosciuto.');
            }
            return data;
        });
    }

    function postForm(url, fields) {
        const body = new URLSearchParams(fields);
        body.append('csrf_token', csrfToken);
        return api(url, { method: 'POST', body });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // "day" is a plain 'YYYY-MM-DD' string (reservations only carry a day,
    // no time — the beauty advisor calls back the same day).
    function toDateInputValue(dateStr) {
        return dateStr || '';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function whatsappLink(phone, text) {
        const digits = (phone || '').replace(/\D+/g, '');
        if (!digits) return null;
        const full = digits.startsWith('39') || digits.length > 11 ? digits : '39' + digits.replace(/^0+/, '');
        let url = 'https://wa.me/' + full;
        if (text) url += '?text=' + encodeURIComponent(text);
        return url;
    }

    // ---- Reservation table ----

    const tbody = document.getElementById('reservations-body');
    let currentRows = [];

    function renderRows(rows) {
        currentRows = rows;
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6">Nessuna prenotazione trovata.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map((r) => {
            const name = `${r.nome || ''} ${r.cognome || ''}`.trim() || '(senza nome)';
            const wa = whatsappLink(r.telefono, `Ciao ${r.nome || ''}, la contattiamo da Human Clinica riguardo la sua prenotazione.`);
            const statusClass = 's-' + (r.status || '').toLowerCase();
            return `
            <tr data-id="${r.pk}">
                <td>${escapeHtml(name)}</td>
                <td>${escapeHtml(r.telefono || '')}<br><small>${escapeHtml(r.email || '')}</small></td>
                <td>${escapeHtml(formatDate(r.day))}</td>
                <td><span class="status-badge ${statusClass}">${escapeHtml(r.status || '')}</span></td>
                <td>${escapeHtml(r.beauty_advisor || '')}</td>
                <td class="actions-cell">
                    <button class="btn btn-sm btn-success" data-action="taken-in-charge">Presa in carico</button>
                    ${wa ? `<a class="btn btn-sm btn-outline" target="_blank" rel="noopener" href="${wa}">WhatsApp</a>` : ''}
                    <button class="btn btn-sm btn-outline" data-action="custom">Messaggio</button>
                    <button class="btn btn-sm btn-outline" data-action="edit">Modifica</button>
                    <button class="btn btn-sm btn-danger" data-action="delete">Elimina</button>
                </td>
            </tr>`;
        }).join('');
    }

    function findRow(id) {
        return currentRows.find((r) => String(r.pk) === String(id));
    }

    function loadList() {
        const params = new URLSearchParams({
            from: document.getElementById('filter-from').value,
            to: document.getElementById('filter-to').value,
            status: document.getElementById('filter-status').value,
            search: document.getElementById('filter-search').value,
        });
        tbody.innerHTML = '<tr><td colspan="6">Caricamento…</td></tr>';
        api('api/list.php?' + params.toString())
            .then((data) => renderRows(data.reservations))
            .catch((err) => {
                tbody.innerHTML = `<tr><td colspan="6">Errore: ${escapeHtml(err.message)}</td></tr>`;
            });
    }

    document.getElementById('filters-form').addEventListener('submit', (e) => {
        e.preventDefault();
        loadList();
    });

    document.getElementById('btn-today').addEventListener('click', () => {
        const today = new Date().toISOString().slice(0, 10);
        document.getElementById('filter-from').value = today;
        document.getElementById('filter-to').value = today;
        loadList();
    });

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const tr = btn.closest('tr');
        const id = tr.dataset.id;
        const row = findRow(id);
        const action = btn.dataset.action;

        if (action === 'delete') {
            if (!confirm('Eliminare definitivamente questa prenotazione?')) return;
            postForm('api/reservation_delete.php', { id })
                .then(() => loadList())
                .catch((err) => alert('Errore: ' + err.message));
        } else if (action === 'taken-in-charge') {
            if (!confirm('Inviare il messaggio di presa in carico a ' + (row.email || '(nessuna email)') + '?')) return;
            postForm('api/reservation_send_template.php', { id, template: 'taken_in_charge' })
                .then(() => { alert('Messaggio inviato.'); loadList(); })
                .catch((err) => alert('Errore: ' + err.message));
        } else if (action === 'edit') {
            openEditModal(row);
        } else if (action === 'custom') {
            openCustomModal(row);
        }
    });

    // ---- Modals ----

    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => btn.closest('.modal-backdrop').classList.remove('open'));
    });
    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.classList.remove('open'); });
    });

    function openEditModal(row) {
        document.getElementById('edit-id').value = row.pk;
        document.getElementById('edit-nome').value = row.nome || '';
        document.getElementById('edit-cognome').value = row.cognome || '';
        document.getElementById('edit-telefono').value = row.telefono || '';
        document.getElementById('edit-email').value = row.email || '';
        document.getElementById('edit-day').value = toDateInputValue(row.day);
        document.getElementById('edit-status').value = (row.status || 'NUOVO').toUpperCase();
        document.getElementById('edit-beauty-advisor').value = row.beauty_advisor || '';
        openModal('modal-edit');
    }

    document.getElementById('form-edit').addEventListener('submit', (e) => {
        e.preventDefault();
        const f = e.target;
        postForm('api/reservation_update.php', {
            id: f.id.value,
            nome: f.nome.value,
            cognome: f.cognome.value,
            telefono: f.telefono.value,
            email: f.email.value,
            day: f.day.value,
            status: f.status.value,
            beauty_advisor: f.beauty_advisor.value,
        }).then(() => {
            closeModal('modal-edit');
            loadList();
            if (window.calendarInstance) window.calendarInstance.refetchEvents();
        }).catch((err) => alert('Errore: ' + err.message));
    });

    function openCustomModal(row) {
        document.getElementById('custom-id').value = row.pk;
        document.getElementById('custom-subject').value = 'Human Clinica';
        document.getElementById('custom-message').value = `Gentile ${row.nome || ''},\n\n`;
        openModal('modal-custom');
    }

    document.getElementById('form-custom').addEventListener('submit', (e) => {
        e.preventDefault();
        const f = e.target;
        postForm('api/reservation_send_custom.php', {
            id: f.id.value,
            subject: f.subject.value,
            message: f.message.value,
        }).then(() => {
            closeModal('modal-custom');
            alert('Messaggio inviato.');
        }).catch((err) => alert('Errore: ' + err.message));
    });

    // ---- Prenotazione manuale ----

    const btnNewReservation = document.getElementById('btn-new-reservation');
    if (btnNewReservation) {
        btnNewReservation.addEventListener('click', () => {
            const form = document.getElementById('form-new-reservation');
            form.reset();
            document.getElementById('new-res-day').value = document.getElementById('filter-from').value || '';
            openModal('modal-new-reservation');
        });
    }

    const formNewReservation = document.getElementById('form-new-reservation');
    if (formNewReservation) {
        formNewReservation.addEventListener('submit', (e) => {
            e.preventDefault();
            const f = e.target;
            postForm('api/reservation_create.php', {
                nome: f.nome.value,
                cognome: f.cognome.value,
                telefono: f.telefono.value,
                email: f.email.value,
                day: f.day.value,
                status: f.status.value,
                beauty_advisor: f.beauty_advisor.value,
                send_email: f.send_email.checked ? '1' : '',
            }).then((data) => {
                closeModal('modal-new-reservation');
                loadList();
                if (window.calendarInstance) window.calendarInstance.refetchEvents();
                if (data.warning) alert(data.warning);
            }).catch((err) => alert('Errore: ' + err.message));
        });
    }

    // ---- Promemoria ----

    const btnNewReminder = document.getElementById('btn-new-reminder');
    if (btnNewReminder) {
        btnNewReminder.addEventListener('click', () => openReminderModal(null, document.getElementById('filter-from').value));
    }

    function openReminderModal(reminder, defaultDay) {
        const form = document.getElementById('form-reminder');
        form.reset();
        const isEdit = !!reminder;
        document.getElementById('reminder-modal-title').textContent = isEdit ? 'Modifica promemoria' : 'Nuovo promemoria';
        document.getElementById('reminder-id').value = isEdit ? reminder.pk : '';
        document.getElementById('reminder-title').value = isEdit ? (reminder.title || '') : '';
        document.getElementById('reminder-note').value = isEdit ? (reminder.note || '') : '';
        document.getElementById('reminder-day').value = isEdit ? (reminder.day || '') : (defaultDay || '');
        document.getElementById('reminder-email').value = isEdit ? (reminder.email || '') : '';
        document.getElementById('reminder-send-email').checked = isEdit ? !!reminder.send_email : false;
        document.getElementById('btn-delete-reminder').style.display = isEdit ? '' : 'none';
        openModal('modal-reminder');
    }

    const formReminder = document.getElementById('form-reminder');
    if (formReminder) {
        formReminder.addEventListener('submit', (e) => {
            e.preventDefault();
            const f = e.target;
            const isEdit = !!f.id.value;
            const url = isEdit ? 'api/reminder_update.php' : 'api/reminder_create.php';
            postForm(url, {
                id: f.id.value,
                title: f.title.value,
                note: f.note.value,
                day: f.day.value,
                email: f.email.value,
                send_email: f.send_email.checked ? '1' : '',
            }).then((data) => {
                closeModal('modal-reminder');
                if (window.calendarInstance) window.calendarInstance.refetchEvents();
                if (data.warning) alert(data.warning);
            }).catch((err) => alert('Errore: ' + err.message));
        });
    }

    document.getElementById('btn-delete-reminder')?.addEventListener('click', () => {
        const id = document.getElementById('reminder-id').value;
        if (!id) return;
        if (!confirm('Eliminare questo promemoria?')) return;
        postForm('api/reminder_delete.php', { id })
            .then(() => {
                closeModal('modal-reminder');
                if (window.calendarInstance) window.calendarInstance.refetchEvents();
            })
            .catch((err) => alert('Errore: ' + err.message));
    });

    // ---- Calendar ----

    const calendarEl = document.getElementById('calendar');
    if (calendarEl && window.FullCalendar) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'it',
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
            events: function (info, successCallback, failureCallback) {
                api(`api/events.php?start=${info.startStr}&end=${info.endStr}`)
                    .then((data) => successCallback(Array.isArray(data.events) ? data.events : []))
                    .catch((err) => failureCallback(err));
            },
            dateClick: function (info) {
                document.getElementById('filter-from').value = info.dateStr;
                document.getElementById('filter-to').value = info.dateStr;
                loadList();
            },
            eventClick: function (info) {
                const props = info.event.extendedProps || {};
                if (props.type === 'reminder') {
                    openReminderModal({
                        pk: props.pk,
                        title: props.title,
                        note: props.note,
                        email: props.email,
                        send_email: props.send_email,
                        day: info.event.startStr.slice(0, 10),
                    });
                    return;
                }
                const dateStr = info.event.startStr.slice(0, 10);
                document.getElementById('filter-from').value = dateStr;
                document.getElementById('filter-to').value = dateStr;
                loadList();
            },
        });
        calendar.render();
        window.calendarInstance = calendar;
    }

    loadList();
})();

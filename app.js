const API_BASE = '';

// State
let state = {
    user: null, // {id, nome, ruolo, token}
    events: [],
    myRegistrations: [],
    stats: []
};

// DOM Elements
const appContent = document.getElementById('app-content');
const navMenu = document.getElementById('nav-menu');
const loading = document.getElementById('loading');
const toastEl = document.getElementById('toast');
const modalContainer = document.getElementById('modal-container');
const modalTitle = document.getElementById('modal-title');
const modalBody = document.getElementById('modal-body');

// --- Initialization ---
document.addEventListener('DOMContentLoaded', () => {
    const savedUser = localStorage.getItem('hub_user');
    if (savedUser) {
        state.user = JSON.parse(savedUser);
    }

    document.getElementById('btn-close-modal').addEventListener('click', closeModal);

    renderApp();
});

// --- API Helpers ---
async function fetchAPI(endpoint, options = {}) {
    showLoading();
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    if (state.user && state.user.token) {
        headers['Authorization'] = `Bearer ${state.user.token}`;
    }

    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers: {
                ...headers,
                ...options.headers
            }
        });

        const data = await response.json();
        hideLoading();

        if (!response.ok) {
            if (response.status === 401) logout(); // Token expired/invalid
            throw new Error(data.message || 'Errore API sconosciuto');
        }
        return data;
    } catch (error) {
        hideLoading();
        showToast(error.message, 'error');
        throw error;
    }
}

// --- UI Helpers ---
function showLoading() { loading.classList.remove('hidden'); }
function hideLoading() { loading.classList.add('hidden'); }

function showToast(message, type = 'success') {
    toastEl.textContent = message;
    toastEl.className = `toast ${type}`;
    setTimeout(() => toastEl.classList.add('hidden'), 3000);
}

function openModal(title, contentHTML) {
    modalTitle.textContent = title;
    modalBody.innerHTML = contentHTML;
    modalContainer.classList.remove('hidden');
}
function closeModal() {
    modalContainer.classList.add('hidden');
}

// --- Routing & Rendering ---
function renderApp() {
    renderNavbar();
    if (!state.user) {
        renderLogin();
    } else {
        if (state.user.ruolo === 'Organizzatore') {
            renderOrganizerDashboard();
        } else {
            renderEmployeeDashboard();
        }
    }
}

function renderNavbar() {
    if (!state.user) {
        navMenu.innerHTML = `<a href="#" onclick="renderLogin()">Login</a> <a href="#" onclick="renderRegister()">Registrati</a>`;
    } else {
        let links = '';
        if (state.user.ruolo === 'Organizzatore') {
            links = `
                <a href="#" onclick="renderOrganizerDashboard()">Eventi</a>
                <a href="#" onclick="renderStats()">Statistiche</a>
            `;
        } else {
            links = `<a href="#" onclick="renderEmployeeDashboard()">Dashboard</a>`;
        }

        navMenu.innerHTML = `
            ${links}
            <span class="user-badge">${state.user.nome} (${state.user.ruolo})</span>
            <a href="#" onclick="logout()" class="btn-logout">Esci</a>
        `;
    }
}

// --- Auth Views ---
function renderLogin() {
    appContent.innerHTML = `
        <div class="auth-container">
            <h2 class="auth-title">Accedi</h2>
            <form id="login-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="l-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="l-password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Accedi</button>
            </form>
            <p class="auth-footer">Non hai un account? <a href="#" onclick="renderRegister()">Registrati</a></p>
        </div>
    `;

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('l-email').value;
        const password = document.getElementById('l-password').value;
        try {
            const res = await fetchAPI('/login.php', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            state.user = { ...res.data.utente, token: res.data.token };
            localStorage.setItem('hub_user', JSON.stringify(state.user));
            showToast('Login completato!');
            renderApp();
        } catch (err) { }
    });
}

function renderRegister() {
    appContent.innerHTML = `
        <div class="auth-container">
            <h2 class="auth-title">Registrazione</h2>
            <form id="register-form">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" id="r-nome" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Cognome</label>
                    <input type="text" id="r-cognome" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="r-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="r-password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ruolo</label>
                    <select id="r-ruolo" class="form-control">
                        <option value="Dipendente">Dipendente</option>
                        <option value="Organizzatore">Organizzatore</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Crea Account</button>
            </form>
            <p class="auth-footer">Hai già un account? <a href="#" onclick="renderLogin()">Accedi</a></p>
        </div>
    `;

    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = {
            nome: document.getElementById('r-nome').value,
            cognome: document.getElementById('r-cognome').value,
            email: document.getElementById('r-email').value,
            password: document.getElementById('r-password').value,
            ruolo: document.getElementById('r-ruolo').value
        };
        try {
            await fetchAPI('/register.php', { method: 'POST', body: JSON.stringify(body) });
            showToast('Registrazione completata! Ora puoi fare login.');
            renderLogin();
        } catch (err) { }
    });
}

function logout() {
    state.user = null;
    localStorage.removeItem('hub_user');
    renderApp();
}

// --- Organizer Dashboard ---
async function renderOrganizerDashboard() {
    appContent.innerHTML = `
        <div class="page-header">
            <h2>Gestione Eventi</h2>
            <button class="btn btn-primary" onclick="openEventForm()">+ Nuovo Evento</button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Data</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody id="org-events-list"></tbody>
            </table>
        </div>
    `;

    try {
        const res = await fetchAPI('/eventi.php');
        state.events = res.data;
        const tbody = document.getElementById('org-events-list');

        if (state.events.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" style="text-align:center">Nessun evento presente</td></tr>`;
            return;
        }

        tbody.innerHTML = state.events.map(ev => `
            <tr>
                <td><strong>${ev.titolo}</strong></td>
                <td>${new Date(ev.data).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                <td>
                    <button class="btn btn-outline" onclick="openCheckin('${ev.evento_id}', '${ev.titolo}')">Check-in</button>
                    <button class="btn btn-outline" onclick='openEventForm(${JSON.stringify(ev)})'>Modifica</button>
                    <button class="btn btn-danger" onclick="deleteEvent('${ev.evento_id}')">Elimina</button>
                </td>
            </tr>
        `).join('');
    } catch (err) { }
}

function openEventForm(event = null) {
    const isEdit = !!event;
    const title = isEdit ? 'Modifica Evento' : 'Nuovo Evento';

    let dateStr = '';
    if (isEdit) {
        // format expected by input type="datetime-local" is YYYY-MM-DDTHH:mm
        const d = new Date(event.data);
        const pad = n => n.toString().padStart(2, '0');
        dateStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    const html = `
        <form id="event-form">
            ${isEdit ? `<input type="hidden" id="ev-id" value="${event.evento_id}">` : ''}
            <div class="form-group">
                <label>Titolo</label>
                <input type="text" id="ev-titolo" class="form-control" required value="${isEdit ? event.titolo : ''}">
            </div>
            <div class="form-group">
                <label>Data</label>
                <input type="datetime-local" id="ev-data" class="form-control" required value="${dateStr}">
            </div>
            <div class="form-group">
                <label>Descrizione</label>
                <textarea id="ev-desc" class="form-control" rows="4">${isEdit ? (event.descrizione || '') : ''}</textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Salva Cambiamenti</button>
        </form>
    `;
    openModal(title, html);

    document.getElementById('event-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = isEdit ? document.getElementById('ev-id').value : null;
        const body = {
            titolo: document.getElementById('ev-titolo').value,
            data: document.getElementById('ev-data').value,
            descrizione: document.getElementById('ev-desc').value
        };

        if (isEdit) body.evento_id = id;

        try {
            await fetchAPI('/eventi.php', {
                method: isEdit ? 'PUT' : 'POST',
                body: JSON.stringify(body)
            });
            showToast(isEdit ? 'Evento aggiornato' : 'Evento creato');
            closeModal();
            renderOrganizerDashboard();
        } catch (err) { }
    });
}

async function deleteEvent(id) {
    if (!confirm('Sei sicuro di voler eliminare questo evento?')) return;
    try {
        await fetchAPI(`/eventi.php?id=${id}`, { method: 'DELETE' });
        showToast('Evento eliminato');
        renderOrganizerDashboard();
    } catch (err) { }
}

async function openCheckin(eventoId, eventoTitolo) {
    try {
        const res = await fetchAPI(`/checkin.php?evento_id=${eventoId}`);
        const iscritti = res.data;

        let html = `<p style="margin-bottom:15px; color:var(--text-muted)">Iscritti all'evento: ${iscritti.length}</p>`;

        if (iscritti.length === 0) {
            html += `<p>Nessun iscritto a questo evento.</p>`;
        } else {
            html += `
            <div style="max-height: 400px; overflow-y:auto; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                <table style="margin-bottom:0">
                    <thead><tr><th>Dipendente</th><th>Check-in</th></tr></thead>
                    <tbody>
                        ${iscritti.map(i => `
                            <tr>
                                <td>${i.cognome} ${i.nome}<br><small>${i.email}</small></td>
                                <td style="width:80px">
                                    <label class="switch">
                                      <input type="checkbox" ${i.checkin_effettuato ? 'checked' : ''} onchange="toggleCheckin('${i.iscrizione_id}', this.checked)">
                                      <span class="slider"></span>
                                    </label>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>`;
        }

        openModal(`Check-in: ${eventoTitolo}`, html);
    } catch (err) { }
}

async function toggleCheckin(iscrizioneId, isChecked) {
    try {
        await fetchAPI('/checkin.php', {
            method: 'PUT',
            body: JSON.stringify({
                iscrizione_id: iscrizioneId,
                status: isChecked
            })
        });
        showToast('Check-in aggiornato');
    } catch (err) {
        // Revert switch visually
        closeModal();
        showToast('Errore di aggiornamento', 'error');
    }
}

// --- Employee Dashboard ---
async function renderEmployeeDashboard() {
    appContent.innerHTML = `
        <div class="page-header">
            <h2>Il tuo hub formazione</h2>
        </div>
        
        <h3 style="margin-bottom:15px">Le tue iscrizioni</h3>
        <div class="cards-grid" id="my-regs-list">
            <p>Caricamento...</p>
        </div>
        
        <h3 style="margin-bottom:15px">Prossimi eventi disponibili</h3>
        <div class="cards-grid" id="avail-events-list">
            <p>Caricamento...</p>
        </div>
    `;

    try {
        const [regRes, eventsRes] = await Promise.all([
            fetchAPI('/iscrizioni.php'),
            fetchAPI('/eventi.php')
        ]);

        state.myRegistrations = regRes.data;
        state.events = eventsRes.data;

        renderEmployeeLists();

    } catch (err) {
        console.error(err);
    }
}

function renderEmployeeLists() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // My Registrations List
    const regContainer = document.getElementById('my-regs-list');
    if (state.myRegistrations.length === 0) {
        regContainer.innerHTML = `<p style="grid-column: 1/-1">Nessuna iscrizione attiva.</p>`;
    } else {
        regContainer.innerHTML = state.myRegistrations.map(reg => {
            const evDate = new Date(reg.data);
            evDate.setHours(0, 0, 0, 0);
            const canCancel = evDate > today;

            const exactTime = new Date(reg.data);
            const isPast = exactTime < new Date();

            let statusBadge = '';
            if (reg.checkin_effettuato) {
                statusBadge = '<span class="badge badge-success">Presente</span>';
            } else if (isPast) {
                statusBadge = '<span class="badge badge-danger">Assente</span>';
            } else {
                statusBadge = '<span class="badge badge-pending">Iscritto (in attesa)</span>';
            }

            return `
            <div class="card">
                <h4 class="card-title">${reg.titolo}</h4>
                <div class="card-date">${new Date(reg.data).toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' })}</div>
                <div class="card-desc">Stato: ${statusBadge}</div>
                <div class="card-actions">
                    ${canCancel && !reg.checkin_effettuato ?
                    `<button class="btn btn-outline btn-block" onclick="cancelRegistration('${reg.evento_id}')">Annulla Iscrizione</button>` :
                    `<span style="font-size:0.8rem; color:var(--text-muted)">Annullamento non più possibile</span>`
                }
                </div>
            </div>`;
        }).join('');
    }

    // Available Events
    const availContainer = document.getElementById('avail-events-list');
    // Filter out past events and events already registered
    const registeredIds = state.myRegistrations.map(r => r.evento_id);
    const available = state.events.filter(ev => {
        const ed = new Date(ev.data);
        ed.setHours(0, 0, 0, 0);
        return ed > today && !registeredIds.includes(ev.evento_id);
    });

    if (available.length === 0) {
        availContainer.innerHTML = `<p style="grid-column: 1/-1">Nessun nuovo evento disponibile.</p>`;
    } else {
        availContainer.innerHTML = available.map(ev => `
            <div class="card">
                <h4 class="card-title">${ev.titolo}</h4>
                <div class="card-date">${new Date(ev.data).toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' })}</div>
                <div class="card-desc">${ev.descrizione || 'Nessuna descrizione disponibile.'}</div>
                <div class="card-actions">
                    <button class="btn btn-primary btn-block" onclick="registerForEvent('${ev.evento_id}')">Iscriviti Ora</button>
                </div>
            </div>
        `).join('');
    }
}

async function registerForEvent(eventoId) {
    try {
        await fetchAPI('/iscrizioni.php', {
            method: 'POST',
            body: JSON.stringify({ evento_id: eventoId })
        });
        showToast('Iscrizione completata con successo!');
        renderEmployeeDashboard(); // Refresh
    } catch (err) { }
}

async function cancelRegistration(eventoId) {
    if (!confirm('Sei sicuro di voler annullare l\'iscrizione a questo evento?')) return;
    try {
        await fetchAPI(`/iscrizioni.php?evento_id=${eventoId}`, {
            method: 'DELETE'
        });
        showToast('Iscrizione annullata');
        renderEmployeeDashboard(); // Refresh
    } catch (err) { }
}

// --- Organizer Stats ---
async function renderStats() {
    appContent.innerHTML = `
        <div class="page-header">
            <h2>Statistiche Eventi Passati</h2>
            <div>
                <input type="date" id="stat-from" class="form-control" style="display:inline-block; width:auto">
                <input type="date" id="stat-to" class="form-control" style="display:inline-block; width:auto">
                <button class="btn btn-primary" onclick="loadStats()">Filtra</button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Data</th>
                        <th>Iscritti</th>
                        <th>Check-in</th>
                        <th>Partecipazione</th>
                    </tr>
                </thead>
                <tbody id="stats-list">
                    <tr><td colspan="5" style="text-align:center">Caricamento...</td></tr>
                </tbody>
            </table>
        </div>
    `;
    loadStats();
}

async function loadStats() {
    const from = document.getElementById('stat-from')?.value || '';
    const to = document.getElementById('stat-to')?.value || '';

    let url = '/stats.php?';
    if (from) url += `dal=${from}&`;
    if (to) url += `al=${to}`;

    try {
        const res = await fetchAPI(url);
        const tbody = document.getElementById('stats-list');

        if (res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center">Nessun evento passato in questo periodo.</td></tr>`;
            return;
        }

        tbody.innerHTML = res.data.map(stat => `
            <tr>
                <td><strong>${stat.titolo}</strong></td>
                <td><span class="card-date" style="margin-bottom:0">${new Date(stat.data).toLocaleDateString('it-IT')}</span></td>
                <td><span class="badge badge-pending">${stat.total_iscritti} iscritti</span></td>
                <td><span class="badge badge-success">${stat.total_checkin || 0} presenti</span></td>
                <td>
                    <div style="font-weight:bold; color:var(--primary-color)">${stat.percentage}%</div>
                    <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:${stat.percentage}%"></div></div>
                </td>
            </tr>
        `).join('');
    } catch (err) { }
}

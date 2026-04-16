/**
 * Control Taller - Lógica Frontend (app.js)
 * Sistema de gestión de Reparaciones y Creaciones.
 * Refactorizado para mayor limpieza y con comentarios en castellano.
 */

// --- ESTADO GLOBAL ---
const STATE = {
    currentView: 'home',
    nextRepairId: null,
    nextCreateId: null,
    // Lista de técnicos disponibles en el taller
    technicians: ['Alex Linares', 'Dani Honrado', 'Stephane Geronimi', 'Gerard Anta', 'Carlos Muñoz', 'Xavier Lamarca']
};

// --- ELEMENTOS DEL DOM ---
const screens = {
    home: document.getElementById('home-screen'),
    repair: document.getElementById('repair-screen'),
    create: document.getElementById('create-screen'),
    history: document.getElementById('history-screen')
};

const btns = {
    toRepair: document.getElementById('btn-repair'),
    toCreate: document.getElementById('btn-create'),
    toHistory: document.getElementById('btn-history'),
    back: document.querySelectorAll('.btn-back'),
    addComponent: document.getElementById('add-component'),
    search: document.getElementById('btn-search')
};

const forms = {
    repair: document.getElementById('form-repair'),
    create: document.getElementById('form-create')
};

const idDisplays = {
    repair: document.getElementById('repair-id-display'),
    create: document.getElementById('create-id-display')
};

const componentsList = document.getElementById('components-list');
const toast = document.getElementById('toast');
const historyTableBody = document.querySelector('#history-table tbody');
const searchRefInput = document.getElementById('search-ref');

// --- INICIALIZACIÓN ---
function init() {
    // Poblar los selectores de técnicos en los formularios
    const techSelects = [document.getElementById('rep-tech'), document.getElementById('cre-tech')];
    techSelects.forEach(select => {
        if (!select) return;
        STATE.technicians.forEach(tech => {
            const opt = document.createElement('option');
            opt.value = tech;
            opt.textContent = tech;
            select.appendChild(opt);
        });
    });
    
    // Iniciar reloj y actualizar cada minuto
    updateDateTime();
    setInterval(updateDateTime, 60000);
}
init();

/**
 * Actualiza la fecha y hora en la cabecera
 */
function updateDateTime() {
    const dateEl = document.getElementById('current-date');
    if (!dateEl) return;
    const now = new Date();
    const options = { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' };
    dateEl.textContent = now.toLocaleDateString('es-ES', options).replace(',', '');
}

// --- NAVEGACIÓN Y VISTAS ---

/**
 * Cambia la vista activa y dispara la carga de datos necesaria (IDs o Historial)
 */
function showView(viewName) {
    Object.values(screens).forEach(s => s.classList.remove('active'));
    screens[viewName].classList.add('active');
    STATE.currentView = viewName;

    if (viewName === 'repair') {
        idDisplays.repair.textContent = `REF: Cargando...`;
        disableSubmit(forms.repair, true);
        fetchNextIds('repair');
    } else if (viewName === 'create') {
        idDisplays.create.textContent = `REF: Cargando...`;
        disableSubmit(forms.create, true);
        fetchNextIds('create');
    } else if (viewName === 'history') {
        triggerLiveSearch();
    }
}

/**
 * Bloquea/Desbloquea el botón de envío de un formulario
 */
function disableSubmit(form, disabled) {
    const btn = form.querySelector('.submit-btn');
    if (btn) btn.disabled = disabled;
}

/**
 * Obtiene del servidor el siguiente número de referencia disponible
 */
async function fetchNextIds(type = 'both') {
    try {
        const res = await fetch('get_next_id.php');
        const data = await res.json();
        
        if (data.status === 'success') {
            STATE.nextRepairId = data.nextRepairId;
            STATE.nextCreateId = data.nextCreateId;
            
            if (type === 'repair' || type === 'both') {
                idDisplays.repair.textContent = `REF: R-${STATE.nextRepairId}`;
                disableSubmit(forms.repair, false);
            }
            if (type === 'create' || type === 'both') {
                idDisplays.create.textContent = `REF: C-${STATE.nextCreateId}`;
                disableSubmit(forms.create, false);
            }
        }
    } catch (err) {
        console.error('Error al sincronizar IDs:', err);
        showToast('Error al conectar con el servidor para obtener IDs');
    }
}

// --- GESTIÓN DE FORMULARIOS ---

// Reparación (Submit)
forms.repair.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!STATE.nextRepairId) return showToast('Error: ID no disponible');

    const data = {
        id: `R-${STATE.nextRepairId}`,
        type: 'repair',
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        client: document.getElementById('rep-client').value,
        technician: document.getElementById('rep-tech').value,
        problem: document.getElementById('rep-problem').value,
        accessories: document.getElementById('rep-accessories').value
    };

    saveRecord(data).then(res => {
        if (res.status === 'success') {
            openPrintWindow(data.id);
            showToast('Reparación registrada ✅');
            forms.repair.reset();
            showView('home');
        } else {
            showToast('Error: ' + res.message);
        }
    });
});

// Creación (Submit)
forms.create.addEventListener('submit', (e) => {
    e.preventDefault();
    const fields = Array.from(forms.create.querySelectorAll('.component-field'));
    const components = fields.map(f => ({
        label: f.querySelector('.component-name')?.value || f.querySelector('.component-label')?.textContent || 'Extra',
        pn: f.querySelector('.comp-pn')?.value.trim() || '',
        sn: f.querySelector('.comp-sn')?.value.trim() || ''
    })).filter(c => c.pn !== '' || c.sn !== '');

    if (!STATE.nextCreateId) return showToast('Error: ID no disponible');

    const data = {
        id: `C-${STATE.nextCreateId}`,
        type: 'creation',
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        client: document.getElementById('cre-client').value,
        technician: document.getElementById('cre-tech').value,
        components: components
    };

    saveRecord(data).then(res => {
        if (res.status === 'success') {
            openPrintWindow(data.id);
            showToast('Creación registrada 🏗️');
            forms.create.reset();
            resetCreationForm();
            showView('home');
        } else {
            showToast('Error: ' + res.message);
        }
    });
});

/**
 * Resetea la lista de componentes por defecto en el formulario de creación
 */
function resetCreationForm() {
    componentsList.innerHTML = `
        <div class="component-field"><span class="component-label">Placa Base</span><input type="text" class="comp-pn" placeholder="P/N"><input type="text" class="comp-sn" placeholder="S/N"></div>
        <div class="component-field"><span class="component-label">CPU</span><input type="text" class="comp-pn" placeholder="P/N"><input type="text" class="comp-sn" placeholder="S/N"></div>
        <div class="component-field"><span class="component-label">RAM</span><input type="text" class="comp-pn" placeholder="P/N"><input type="text" class="comp-sn" placeholder="S/N"></div>
        <div class="component-field"><span class="component-label">Caja</span><input type="text" class="comp-pn" placeholder="P/N"><input type="text" class="comp-sn" placeholder="S/N"></div>
        <div class="component-field"><span class="component-label">PCI-e (Opc.)</span><input type="text" class="comp-pn" placeholder="P/N"><input type="text" class="comp-sn" placeholder="S/N"></div>
    `;
}

// Botón para añadir componentes extra
btns.addComponent.addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'component-field';
    div.innerHTML = `
        <input type="text" class="component-name" placeholder="Nombre" required>
        <input type="text" class="comp-pn" placeholder="P/N">
        <input type="text" class="comp-sn" placeholder="S/N">
        <button type="button" class="remove-component">✖</button>
    `;
    componentsList.appendChild(div);
    div.querySelector('.remove-component').onclick = () => div.remove();
});

// --- COMUNICACIÓN CON SERVIDOR (PROMISES) ---

function saveRecord(record) {
    return fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(record)
    }).then(res => res.json());
}

function updateRecord(record) {
    return fetch('update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(record)
    }).then(res => res.json());
}

// --- HISTORIAL Y BÚSQUEDA ---

async function loadHistory(filters = {}) {
    try {
        const query = new URLSearchParams({ id: filters.ref || '' }).toString();
        const res = await fetch(`get_records.php?${query}`);
        const records = await res.json();

        // Filtrado en el cliente (para no sobrecargar el servidor con cada tecla)
        const filtered = records.filter(r => {
            const matchClient = !filters.client || (r.client || '').toLowerCase().includes(filters.client.toLowerCase());
            const matchTech = !filters.tech || (r.technician || '').toLowerCase().includes(filters.tech.toLowerCase());
            const matchProblem = !filters.problem || (r.problem || '').toLowerCase().includes(filters.problem.toLowerCase());
            const matchDelivered = filters.delivered === '' || String(r.delivered) === filters.delivered;
            return matchClient && matchTech && matchProblem && matchDelivered;
        });

        renderHistoryTable(filtered);
    } catch (err) {
        showToast('Error al cargar historial');
    }
}

function renderHistoryTable(records) {
    historyTableBody.innerHTML = '';
    if (records.length === 0) {
        historyTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center">Sin registros</td></tr>';
        return;
    }

    records.forEach(r => {
        const tr = document.createElement('tr');
        tr.className = (r.type === 'repair' ? 'row-repair' : 'row-creation') + (r.delivered == 1 ? ' is-delivered' : '');
        tr.innerHTML = `
            <td>${r.id}</td>
            <td>${r.type === 'repair' ? 'Reparación' : 'Creación'}</td>
            <td>${r.client}</td>
            <td>${r.technician}</td>
            <td>${new Date(r.date).toLocaleDateString()}</td>
            <td><span class="status-badge ${r.delivered == 1 ? 'status-delivered' : 'status-pending'}">${r.delivered == 1 ? '✅ Entregado' : '⏳ Pendiente'}</span></td>
            <td style="text-align:center"><button class="btn-action btn-status" data-id="${r.id}" data-type="${r.type}" data-delivered="${r.delivered}">${r.delivered == 1 ? '⏳' : '✅'}</button></td>
            <td>${r.problem || ''}</td>
            <td class="actions-cell">
                <button class="btn-action btn-edit" data-id="${r.id}" data-type="${r.type}">✏️</button>
                <button class="btn-action btn-delete" data-id="${r.id}" data-type="${r.type}">🗑️</button>
                <button class="btn-action btn-print-ref" data-id="${r.id}">🖨️</button>
            </td>
        `;
        historyTableBody.appendChild(tr);
    });
}

/**
 * Función que centraliza la búsqueda en tiempo real
 */
function triggerLiveSearch() {
    loadHistory({
        ref: searchRefInput.value.trim(),
        type: document.getElementById('history-type-filter').value,
        client: document.getElementById('history-client-filter').value.trim(),
        tech: document.getElementById('history-tech-filter').value.trim(),
        problem: document.getElementById('history-problem-filter').value.trim(),
        delivered: document.getElementById('history-delivered-filter').value
    });
}

// Eventos de Navegación Home
btns.toRepair.onclick = () => showView('repair');
btns.toCreate.onclick = () => showView('create');
btns.toHistory.onclick = () => showView('history');
btns.back.forEach(b => b.onclick = () => showView('home'));

// Debounce para búsqueda
let searchTimer;
const debounceSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(triggerLiveSearch, 300);
};

[searchRefInput, 'history-client-filter', 'history-tech-filter', 'history-problem-filter'].forEach(id => {
    const el = typeof id === 'string' ? document.getElementById(id) : id;
    if (el) el.addEventListener('input', debounceSearch);
});

['history-type-filter', 'history-delivered-filter'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', triggerLiveSearch);
});

// Limpiar filtros
document.getElementById('btn-clear-filters').onclick = () => {
    ['search-ref', 'history-client-filter', 'history-tech-filter', 'history-problem-filter'].forEach(id => document.getElementById(id).value = '');
    ['history-type-filter', 'history-delivered-filter'].forEach(id => document.getElementById(id).value = '');
    triggerLiveSearch();
};

// --- MODAL DE EDICIÓN ---

const editModal = document.getElementById('edit-modal');
const editForm = document.getElementById('edit-form');

function openEditModal(id, type) {
    fetch(`get_records.php?id=${id}`).then(res => res.json()).then(records => {
        const r = records.find(rec => rec.id === id);
        if (!r) return;

        document.getElementById('edit-id').value = r.id;
        document.getElementById('edit-type').value = r.type;
        document.getElementById('edit-client').value = r.client;
        document.getElementById('edit-delivered').checked = r.delivered == 1;

        // Poblar técnicos en el modal
        const sel = document.getElementById('edit-tech');
        sel.innerHTML = '';
        STATE.technicians.forEach(t => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = t;
            if (t === r.technician) opt.selected = true;
            sel.appendChild(opt);
        });

        // Mostrar u ocultar campos según tipo
        const isRepair = r.type === 'repair';
        document.getElementById('edit-problem-group').style.display = isRepair ? 'block' : 'none';
        document.getElementById('edit-accessories-group').style.display = isRepair ? 'block' : 'none';
        document.getElementById('edit-components-group').style.display = isRepair ? 'none' : 'block';

        if (isRepair) {
            document.getElementById('edit-problem').value = r.problem;
            document.getElementById('edit-accessories').value = r.accessories || '';
        } else {
            renderEditComponents(r.components || []);
        }

        editModal.classList.remove('hidden');
    });
}

function renderEditComponents(components) {
    const list = document.getElementById('edit-components-list');
    list.innerHTML = '';
    const defaults = ['Placa Base', 'CPU', 'RAM', 'Caja', 'PCI-e (Opc.)'];
    
    // Mostramos los por defecto rellenados o vacíos
    defaults.forEach(label => {
        const c = components.find(comp => comp.label === label) || { pn: '', sn: '' };
        list.insertAdjacentHTML('beforeend', `
            <div class="component-field">
                <span class="component-label">${label}</span>
                <input type="text" class="comp-pn" value="${c.pn}" placeholder="P/N">
                <input type="text" class="comp-sn" value="${c.sn}" placeholder="S/N">
            </div>
        `);
    });

    // Añadimos extras
    components.forEach(c => {
        if (!defaults.includes(c.label)) {
            list.insertAdjacentHTML('beforeend', `
                <div class="component-field">
                    <input type="text" class="component-name" value="${c.label}" required>
                    <input type="text" class="comp-pn" value="${c.pn}" placeholder="P/N">
                    <input type="text" class="comp-sn" value="${c.sn}" placeholder="S/N">
                    <button type="button" class="remove-component">✖</button>
                </div>
            `);
        }
    });
}

// Guardar edición
editForm.onsubmit = (e) => {
    e.preventDefault();
    const type = document.getElementById('edit-type').value;
    const data = {
        id: document.getElementById('edit-id').value,
        type: type,
        client: document.getElementById('edit-client').value,
        technician: document.getElementById('edit-tech').value,
        delivered: document.getElementById('edit-delivered').checked ? 1 : 0
    };

    if (type === 'repair') {
        data.problem = document.getElementById('edit-problem').value;
        data.accessories = document.getElementById('edit-accessories').value;
    } else {
        const fields = Array.from(document.querySelectorAll('#edit-components-list .component-field'));
        data.components = fields.map(f => ({
            label: f.querySelector('.component-label')?.textContent || f.querySelector('.component-name')?.value || 'Extra',
            pn: f.querySelector('.comp-pn').value.trim(),
            sn: f.querySelector('.comp-sn').value.trim()
        })).filter(c => c.pn !== '' || c.sn !== '');
    }

    updateRecord(data).then(() => {
        editModal.classList.add('hidden');
        showToast('Guardado ✅');
        triggerLiveSearch();
    });
};

document.querySelector('.modal-close').onclick = () => editModal.classList.add('hidden');

// --- ACCIONES DE TABLA ---

document.onclick = (e) => {
    const id = e.target.dataset.id;
    const type = e.target.dataset.type;
    if (!id) return;

    if (e.target.classList.contains('btn-edit')) openEditModal(id, type);
    if (e.target.classList.contains('btn-delete')) deleteRecord(id, type);
    if (e.target.classList.contains('btn-print-ref')) openPrintWindow(id);
    if (e.target.classList.contains('btn-status')) {
        const next = e.target.dataset.delivered == 1 ? 0 : 1;
        fetch('change_status.php', {
            method: 'POST',
            body: JSON.stringify({ id, type, delivered: next })
        }).then(() => triggerLiveSearch());
    }
};

function deleteRecord(id, type) {
    if (confirm(`¿Eliminar ${id}?`)) {
        fetch('delete.php', { method: 'POST', body: JSON.stringify({ id, type }) })
            .then(() => { showToast('Eliminado 🗑️'); triggerLiveSearch(); });
    }
}

// --- UTILIDADES ---

function showToast(msg) {
    toast.querySelector('#toast-message').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function openPrintWindow(id) {
    window.open(`print.php?id=${encodeURIComponent(id)}`, '_blank');
}

// Animación de botones (Glow)
document.querySelectorAll('.massive-btn').forEach(btn => {
    btn.onmousemove = (e) => {
        const rect = btn.getBoundingClientRect();
        btn.style.setProperty('--x', `${e.clientX - rect.left}px`);
        btn.style.setProperty('--y', `${e.clientY - rect.top}px`);
    };
});
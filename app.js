/**
 * TPV Logic - Vanilla JS + MySQL (Actualizado con Historial)
 */

const STATE = {
    currentView: 'home',
    nextRepairId: parseInt(localStorage.getItem('nextRepairId')) || 1000,
    nextCreateId: parseInt(localStorage.getItem('nextCreateId')) || 5000,
    technicians: ['Alex Linares', 'Carlos Muñoz', 'Stephane Geronimi', 'Dani Honrado', 'Gerard Anta', 'Xavier Lamarca', 'Daniel Palacios']
};

// --- DOM ELEMENTS ---
const screens = {
    home: document.getElementById('home-screen'),
    repair: document.getElementById('repair-screen'),
    create: document.getElementById('create-screen'),
    history: document.getElementById('history-screen')
};

const btns = {
    toRepair: document.getElementById('btn-repair'),
    toCreate: document.getElementById('btn-create'),
    toHistory: document.getElementById('btn-history'), // nuevo
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

// --- INITIALIZATION ---
function init() {
    const techSelects = [document.getElementById('rep-tech'), document.getElementById('cre-tech')];
    techSelects.forEach(select => {
        STATE.technicians.forEach(tech => {
            const opt = document.createElement('option');
            opt.value = tech;
            opt.textContent = tech;
            select.appendChild(opt);
        });
    });
    updateDateTime();
    setInterval(updateDateTime, 60000);
}
init();

function updateDateTime() {
    const dateEl = document.getElementById('current-date');
    if (!dateEl) return;

    const now = new Date();
    const options = { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' };
    dateEl.textContent = now.toLocaleDateString('es-ES', options).replace(',', '');
}

// --- VIEW NAVIGATION ---
function showView(viewName) {
    Object.values(screens).forEach(s => s.classList.remove('active'));
    screens[viewName].classList.add('active');
    STATE.currentView = viewName;

    if (viewName === 'repair') {
        idDisplays.repair.textContent = `REF: R-${STATE.nextRepairId}`;
    } else if (viewName === 'create') {
        idDisplays.create.textContent = `REF: C-${STATE.nextCreateId}`;
    } else if (viewName === 'history') {
        loadHistory(); // cargar registros al entrar a historial
    }
}

// --- EVENT LISTENERS ---
btns.toRepair.addEventListener('click', () => showView('repair'));
btns.toCreate.addEventListener('click', () => showView('create'));
btns.toHistory.addEventListener('click', () => showView('history'));

document.querySelectorAll('.massive-btn').forEach(btn => {
    btn.addEventListener('mousemove', e => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        btn.style.setProperty('--x', `${x}px`);
        btn.style.setProperty('--y', `${y}px`);
    });
});

btns.back.forEach(btn => {
    btn.addEventListener('click', () => showView('home'));
});

btns.addComponent.addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'component-field';
    div.innerHTML = `
        <input type="text" class="component-name" placeholder="Nombre componente" required>
        <input type="text" class="component-value" placeholder="S/N o P/N" required>
        <button type="button" class="remove-component">✖</button>
    `;
    componentsList.appendChild(div);

    div.querySelector('.remove-component').addEventListener('click', () => {
        div.remove();
    });
});

// --- FORM HANDLING ---
forms.repair.addEventListener('submit', (e) => {
    e.preventDefault();

    const data = {
        id: `R-${STATE.nextRepairId}`,
        type: 'repair',
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        client: document.getElementById('rep-client').value,
        technician: document.getElementById('rep-tech').value,
        problem: document.getElementById('rep-problem').value
    };

    saveRecord(data)
        .then(res => {
            if (res.status === 'success') {
                STATE.nextRepairId++;
                localStorage.setItem('nextRepairId', STATE.nextRepairId);
                printLabel(data.id); // Flujo automático v2.22
                showToast('Reparación registrada con éxito');
                forms.repair.reset();
                showView('home');
            } else {
                console.error('Error al guardar reparación:', res.message);
                showToast('Error al guardar reparación');
            }
        })
        .catch(err => {
            console.error('Error al guardar reparación:', err);
            showToast('Error de conexión al guardar reparación');
        });
});

forms.create.addEventListener('submit', (e) => {
    e.preventDefault();

    const componentFields = Array.from(document.querySelectorAll('.component-field'));
    const components = componentFields.map(field => {
        const nameInput = field.querySelector('.component-name');
        const valueInput = field.querySelector('.component-value');

        let label;
        let value;

        if (nameInput && valueInput) {
            label = nameInput.value.trim() || 'Extra';
            value = valueInput.value.trim();
        } else {
            label = field.querySelector('.component-label')?.textContent || 'Extra';
            value = field.querySelector('input')?.value.trim() || '';
        }

        return { label, value };
    }).filter(c => c.value.trim() !== '');

    const data = {
        id: `C-${STATE.nextCreateId}`,
        type: 'creation',
        date: new Date().toISOString().slice(0, 19).replace('T', ' '),
        client: document.getElementById('cre-client').value,
        technician: document.getElementById('cre-tech').value,
        components: components
    };

    saveRecord(data)
        .then(res => {
            if (res.status === 'success') {
                STATE.nextCreateId++;
                localStorage.setItem('nextCreateId', STATE.nextCreateId);
                printLabel(data.id); // Flujo automático v2.22
                showToast('Creación registrada con éxito');
                forms.create.reset();
                resetCreationForm();
                showView('home');
            } else {
                console.error('Error al guardar creación:', res.message);
                showToast('Error al guardar creación');
            }
        })
        .catch(err => {
            console.error('Error al guardar creación:', err);
            showToast('Error de conexión al guardar creación');
        });
});

function resetCreationForm() {
    componentsList.innerHTML = `
        <div class="component-field">
            <span class="component-label">Placa Base</span>
            <input type="text" name="serial" class="serial-input" placeholder="S/N o P/N" required>
        </div>
        <div class="component-field">
            <span class="component-label">CPU</span>
            <input type="text" name="serial" class="serial-input" placeholder="S/N o P/N" required>
        </div>
        <div class="component-field">
            <span class="component-label">RAM</span>
            <input type="text" name="serial" class="serial-input" placeholder="S/N o P/N" required>
        </div>
        <div class="component-field">
            <span class="component-label">Caja</span>
            <input type="text" name="serial" class="serial-input" placeholder="S/N o P/N" required>
        </div>
        <div class="component-field">
            <span class="component-label">PCI-e (Opcional)</span>
            <input type="text" name="serial" class="serial-input" placeholder="S/N o P/N">
        </div>
    `;
}

// --- MYSQL SAVE ---
function saveRecord(record) {
    return fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(record)
    })
        .then(res => {
            if (!res.ok) throw new Error('Respuesta no OK');
            return res.json();
        });
}

// --- HISTORY SCREEN ---
function loadHistory(searchRef = '', typeFilter = '', clientFilter = '', techFilter = '', problemFilter = '', sortFilter = 'date_desc') {
    const params = new URLSearchParams();
    if (searchRef) params.set('ref', searchRef);
    if (typeFilter) params.set('type', typeFilter);

    fetch(`get_records.php?${params.toString()}`)
        .then(res => res.json())
        .then(records => {
            // Filtros adicionales del lado cliente
            const filtered = records.filter(r => {
                const matchClient = !clientFilter || (r.client || '').toLowerCase().includes(clientFilter.toLowerCase());
                const matchTech = !techFilter || (r.technician || '').toLowerCase().includes(techFilter.toLowerCase());
                const reviewText = r.problem || '';
                const matchProblem = !problemFilter || reviewText.toLowerCase().includes(problemFilter.toLowerCase());
                return matchClient && matchTech && matchProblem;
            });

            // Ordenado
            const sorted = filtered.sort((a, b) => {
                if (sortFilter === 'date_desc') {
                    return new Date(b.date) - new Date(a.date);
                }
                if (sortFilter === 'date_asc') {
                    return new Date(a.date) - new Date(b.date);
                }
                if (sortFilter === 'client') {
                    return (a.client || '').localeCompare(b.client || '');
                }
                if (sortFilter === 'technician') {
                    return (a.technician || '').localeCompare(b.technician || '');
                }
                if (sortFilter === 'problem') {
                    const pa = a.problem || '';
                    const pb = b.problem || '';
                    return pa.localeCompare(pb);
                }
                if (sortFilter === 'id') {
                    return (a.id || '').localeCompare(b.id || '');
                }
                if (sortFilter === 'type') {
                    return (a.type || '').localeCompare(b.type || '');
                }
                return 0;
            });

            historyTableBody.innerHTML = '';

            if (sorted.length === 0) {
                const tr = document.createElement('tr');
                tr.className = 'row-empty';
                tr.innerHTML = `<td colspan="6" class="empty-row">No hay registros con estos filtros.</td>`;
                historyTableBody.appendChild(tr);
                return;
            }

            sorted.forEach(r => {
                const tr = document.createElement('tr');
                tr.className = r.type === 'repair' ? 'row-repair' : 'row-creation';
                tr.innerHTML = `
                    <td>${r.id}</td>
                    <td>${r.type === 'repair' ? 'Reparación' : 'Creación'}</td>
                    <td>${r.client}</td>
                    <td>${r.technician}</td>
                    <td>${new Date(r.date).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })}</td>
                    <td>${r.type === 'repair' ? r.problem : ''}</td>
                    <td class="actions-cell">
                        <button class="btn-action btn-edit" data-id="${r.id}" data-type="${r.type}">✏️ Editar</button>
                        <button class="btn-action btn-delete" data-id="${r.id}" data-type="${r.type}">🗑️ Eliminar</button>
                        <button class="btn-action btn-print-ref" data-id="${r.id}">🏷️ Ref</button>
                        <button class="btn-action btn-print" data-id="${r.id}">🖨️ Parte</button>
                    </td>
                `;
                historyTableBody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error cargando historial:', err);
            showToast('No se pudo cargar el historial');
        });
}

btns.search.addEventListener('click', () => {
    const ref = searchRefInput.value.trim();
    const type = document.getElementById('history-type-filter').value;
    const client = document.getElementById('history-client-filter').value.trim();
    const tech = document.getElementById('history-tech-filter').value.trim();
    const problem = document.getElementById('history-problem-filter').value.trim();
    const sort = document.getElementById('history-sort-filter').value;
    loadHistory(ref, type, client, tech, problem, sort);
});

// Ordenación por click en columnas
const headerSortButtons = document.querySelectorAll('#history-table thead th.sort-header');
let currentSortDirection = {
    id: 'asc',
    type: 'asc',
    client: 'asc',
    technician: 'asc',
    date: 'desc',
    problem: 'asc'
};

headerSortButtons.forEach(th => {
    th.addEventListener('click', () => {
        const sortKey = th.getAttribute('data-sort');

        // Alternar dirección para date y por defecto asc para otros
        const direction = currentSortDirection[sortKey] === 'asc' ? 'desc' : 'asc';
        currentSortDirection[sortKey] = direction;

        // Actualizar selector visual y estado
        document.getElementById('history-sort-filter').value = sortKey === 'date' ? `date_${direction}` : sortKey;
        headerSortButtons.forEach(h => h.classList.remove('active-sort', 'asc', 'desc'));
        th.classList.add('active-sort', direction);
        th.setAttribute('data-sort-dir', direction);

        const ref = searchRefInput.value.trim();
        const type = document.getElementById('history-type-filter').value;
        const client = document.getElementById('history-client-filter').value.trim();
        const tech = document.getElementById('history-tech-filter').value.trim();
        const problem = document.getElementById('history-problem-filter').value.trim();

        let sortValue;
        if (sortKey === 'date') {
            sortValue = `date_${direction}`;
        } else if (sortKey === 'problem') {
            sortValue = 'problem';
        } else {
            sortValue = sortKey;
        }

        loadHistory(ref, type, client, tech, problem, sortValue);
    });
});

// Cargar siempre al mostrar historial
function showView(viewName) {
    Object.values(screens).forEach(s => s.classList.remove('active'));
    screens[viewName].classList.add('active');
    STATE.currentView = viewName;

    if (viewName === 'repair') {
        idDisplays.repair.textContent = `REF: R-${STATE.nextRepairId}`;
    } else if (viewName === 'create') {
        idDisplays.create.textContent = `REF: C-${STATE.nextCreateId}`;
    } else if (viewName === 'history') {
        const ref = searchRefInput.value.trim();
        const type = document.getElementById('history-type-filter').value;
        const client = document.getElementById('history-client-filter').value.trim();
        const tech = document.getElementById('history-tech-filter').value.trim();
        const problem = document.getElementById('history-problem-filter').value.trim();
        const sort = document.getElementById('history-sort-filter').value;
        loadHistory(ref, type, client, tech, problem, sort);
    }
}

document.getElementById('btn-clear-filters').addEventListener('click', () => {
    document.getElementById('search-ref').value = '';
    document.getElementById('history-type-filter').value = '';
    document.getElementById('history-client-filter').value = '';
    document.getElementById('history-tech-filter').value = '';
    document.getElementById('history-problem-filter').value = '';
    document.getElementById('history-sort-filter').value = 'date_desc';
    loadHistory();
});

// Event listeners para acciones de tabla
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-edit')) {
        const id = e.target.getAttribute('data-id');
        const type = e.target.getAttribute('data-type');
        openEditModal(id, type);
    } else if (e.target.classList.contains('btn-delete')) {
        const id = e.target.getAttribute('data-id');
        const type = e.target.getAttribute('data-type');
        deleteRecord(id, type);
    } else if (e.target.classList.contains('btn-print-ref')) {
        const id = e.target.getAttribute('data-id');
        printLabel(id, 'ql-570', 1, 'ref');
    } else if (e.target.classList.contains('btn-print')) {
        const id = e.target.getAttribute('data-id');
        printLabel(id); // Dispara el flujo automático (Ref + Informe) v2.29
    }
});

// Modal de edición
const editModal = document.getElementById('edit-modal');
const editForm = document.getElementById('edit-form');
const editTechSelect = document.getElementById('edit-tech');
const editComponentsList = document.getElementById('edit-components-list');

function openEditModal(id, type) {
    // Cargar datos del registro
    fetch(`get_records.php?ref=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(records => {
            const record = records.find(r => r.id === id);
            if (!record) return;

            document.getElementById('edit-id').value = record.id;
            document.getElementById('edit-type').value = record.type;
            document.getElementById('edit-client').value = record.client;
            document.getElementById('edit-tech').value = record.technician;

            // Poblar técnicos
            editTechSelect.innerHTML = '';
            STATE.technicians.forEach(tech => {
                const opt = document.createElement('option');
                opt.value = tech;
                opt.textContent = tech;
                if (tech === record.technician) opt.selected = true;
                editTechSelect.appendChild(opt);
            });

            if (record.type === 'repair') {
                document.getElementById('edit-problem-group').style.display = 'block';
                document.getElementById('edit-components-group').style.display = 'none';
                document.getElementById('edit-problem').value = record.problem;
            } else {
                document.getElementById('edit-problem-group').style.display = 'none';
                document.getElementById('edit-components-group').style.display = 'block';

                const defaultComponents = [
                    'Placa Base',
                    'CPU',
                    'RAM',
                    'Caja',
                    'PCI-e (Opcional)'
                ];

                const filledComponents = {};
                (record.components || []).forEach(comp => {
                    filledComponents[comp.label] = comp.value;
                });

                editComponentsList.innerHTML = '';
                defaultComponents.forEach(label => {
                    const div = document.createElement('div');
                    div.className = 'component-field';
                    const value = filledComponents[label] || '';
                    div.innerHTML = `
                        <span class="component-label">${label}</span>
                        <input type="text" value="${value}" placeholder="S/N o P/N" ${label === 'PCI-e (Opcional)' ? '' : 'required'}>
                    `;
                    editComponentsList.appendChild(div);
                });

                // Añadir adicionales si hay más componentes de la creación
                (record.components || []).forEach(comp => {
                    if (!defaultComponents.includes(comp.label)) {
                        const div = document.createElement('div');
                        div.className = 'component-field';
                        div.innerHTML = `
                            <span class="component-label">${comp.label}</span>
                            <input type="text" value="${comp.value}" placeholder="S/N o P/N" required>
                        `;
                        editComponentsList.appendChild(div);
                    }
                });
            }

            editModal.classList.remove('hidden');
        });
}

document.querySelector('.modal-close').addEventListener('click', () => {
    editModal.classList.add('hidden');
});

editForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const data = {
        id: document.getElementById('edit-id').value,
        type: document.getElementById('edit-type').value,
        client: document.getElementById('edit-client').value,
        technician: document.getElementById('edit-tech').value,
    };

    if (data.type === 'repair') {
        data.problem = document.getElementById('edit-problem').value;
    } else {
        const componentFields = Array.from(editComponentsList.querySelectorAll('.component-field'));
        data.components = componentFields.map(field => ({
            label: field.querySelector('.component-label').textContent,
            value: field.querySelector('input').value
        })).filter(c => c.value.trim() !== '');
    }

    updateRecord(data)
        .then(res => {
            if (res.status === 'success') {
                editModal.classList.add('hidden');
                showToast('Registro actualizado con éxito');
                loadHistory(); // Recargar historial
            } else {
                showToast('Error al actualizar registro');
            }
        })
        .catch(err => {
            showToast('Error de conexión');
        });
});

document.getElementById('edit-add-component').addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'component-field';
    div.innerHTML = `
        <input type="text" class="component-name" placeholder="Nombre componente" required>
        <input type="text" class="component-value" placeholder="S/N o P/N" required>
        <button type="button" class="remove-component">✖</button>
    `;
    editComponentsList.appendChild(div);

    div.querySelector('.remove-component').addEventListener('click', () => {
        div.remove();
    });
});

function updateRecord(record) {
    return fetch('update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(record)
    })
        .then(res => res.json());
}

function deleteRecord(id, type) {
    if (!confirm(`¿Estás seguro de eliminar el registro ${id}?`)) return;

    fetch('delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, type })
    })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                showToast('Registro eliminado con éxito');
                loadHistory();
            } else {
                showToast('Error al eliminar registro');
            }
        })
        .catch(err => {
            showToast('Error de conexión');
        });
}

// --- TOAST ---
function showToast(msg) {
    document.getElementById('toast-message').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function printLabels(id, copies = 1, printer = 'gk420d') {
    printLabel(id, printer, copies);
}

function printLabel(id, printer = null, copies = 1, mode = 'full') {
    let url = `print.php?id=${encodeURIComponent(id)}`;
    if (printer) url += `&printer=${encodeURIComponent(printer)}`;
    if (copies > 1) url += `&copies=${encodeURIComponent(copies)}`;
    if (mode !== 'full') url += `&mode=${encodeURIComponent(mode)}`;

    const targetLabel = printer ? `(${printer})` : '(Auto)';
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(`Impresión enviada ${targetLabel}: ${id}`);
            } else {
                console.error('Print error:', data);
                showToast(`Error al imprimir ${targetLabel}`);
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            showToast('Error al conectar con servidor de impresión');
        });
}
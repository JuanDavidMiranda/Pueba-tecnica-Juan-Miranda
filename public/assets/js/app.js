const state = {
    token: sessionStorage.getItem("sihos_token") || "",
    user: null,
    page: 1,
    perPage: 4,
    query: "",
    patients: [],
    meta: { page: 1, per_page: 8, total: 0, last_page: 1 },
    catalogs: null,
    editingId: null,
    pendingDelete: null,
    searchTimer: 0,
    municipioRequest: 0,
    listRequest: 0,
};

const loginView = document.querySelector("#login");
const appView = document.querySelector("#app");
const loginForm = document.querySelector("#login-form");
const loginError = document.querySelector("#login-error");
const loginSubmit = document.querySelector("#login-submit");
const userName = document.querySelector("#user-name");
const logoutButton = document.querySelector("#logout");
const searchForm = document.querySelector("#search-form");
const searchInput = document.querySelector("#search-input");
const newPatientButton = document.querySelector("#new-patient");
const summary = document.querySelector("#summary");
const listStatus = document.querySelector("#list-status");
const empty = document.querySelector("#empty");
const emptyTitle = document.querySelector("#empty-title");
const emptyText = document.querySelector("#empty-text");
const tableWrap = document.querySelector("#patient-table-wrap");
const tbody = document.querySelector("#patient-tbody");
const pager = document.querySelector("#pager");
const patientDialog = document.querySelector("#patient-dialog");
const patientForm = document.querySelector("#patient-form");
const patientTitle = document.querySelector("#patient-title");
const formError = document.querySelector("#form-error");
const saveButton = document.querySelector("#save-patient");
const fotoInput = document.querySelector("#foto");
const fotoHint = document.querySelector("#foto-hint");
const fotoPreview = document.querySelector("#foto-preview");
const departamentoSelect = document.querySelector("#departamento_id");
const municipioSelect = document.querySelector("#municipio_id");
const deleteDialog = document.querySelector("#delete-dialog");
const deleteName = document.querySelector("#delete-name");
const deleteCancel = document.querySelector("#delete-cancel");
const deleteConfirm = document.querySelector("#delete-confirm");
const toasts = document.querySelector("#toasts");

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function fullName(patient) {
    return patient.nombre_completo || [patient.nombre1, patient.nombre2, patient.apellido1, patient.apellido2]
        .filter(Boolean)
        .join(" ");
}

function initials(patient) {
    const first = (patient.nombre1 || "?").trim().charAt(0);
    const last = (patient.apellido1 || "").trim().charAt(0);
    return (first + last).toLocaleUpperCase("es");
}

function clearSession() {
    state.token = "";
    state.user = null;
    sessionStorage.removeItem("sihos_token");
}

function showLogin(message) {
    appView.hidden = true;
    loginView.hidden = false;
    if (message) {
        loginError.hidden = false;
        loginError.textContent = message;
    }
}

function toast(message, type) {
    const node = el("div", `toast toast-${type}`, message);
    toasts.append(node);
    window.setTimeout(() => node.remove(), 4200);
}

async function api(path, { method = "GET", json, body, auth = true } = {}) {
    const headers = {};
    let payload;
    if (json !== undefined) {
        headers["Content-Type"] = "application/json";
        payload = JSON.stringify(json);
    } else if (body) {
        payload = body;
    }
    if (auth && state.token) headers.Authorization = `Bearer ${state.token}`;

    let response;
    try {
        response = await fetch(path, { method, headers, body: payload });
    } catch {
        const error = new Error("No fue posible conectar con el servidor.");
        error.status = 0;
        throw error;
    }

    const data = await response.json().catch(() => ({}));
    if (response.status === 401 && auth) {
        clearSession();
        showLogin("La sesión expiró. Ingrese de nuevo.");
        const error = new Error(data.message || "Sesión no válida o expirada.");
        error.status = 401;
        throw error;
    }
    if (!response.ok) {
        const error = new Error(data.message || "Ocurrió un error.");
        error.status = response.status;
        error.errors = data.errors || {};
        throw error;
    }
    return data;
}

function fillSelect(select, items, placeholder) {
    select.replaceChildren();
    select.append(new Option(placeholder, ""));
    for (const item of items) {
        select.append(new Option(item.nombre, String(item.id)));
    }
}

async function loadCatalogs() {
    if (state.catalogs) return;
    const [departamentos, tipos, generos] = await Promise.all([
        api("/api/departamentos"),
        api("/api/tipos-documento"),
        api("/api/generos"),
    ]);
    state.catalogs = {
        departamentos: departamentos.data,
        tipos: tipos.data,
        generos: generos.data,
    };
    fillSelect(document.querySelector("#tipo_documento_id"), state.catalogs.tipos, "Seleccione");
    fillSelect(document.querySelector("#genero_id"), state.catalogs.generos, "Seleccione");
    fillSelect(departamentoSelect, state.catalogs.departamentos, "Seleccione");
}

async function loadMunicipios(departamentoId, selectedId) {
    const requestId = ++state.municipioRequest;
    municipioSelect.replaceChildren(new Option("Cargando municipios…", ""));
    municipioSelect.disabled = true;
    if (!departamentoId) {
        municipioSelect.replaceChildren(new Option("Seleccione un departamento", ""));
        return;
    }

    const payload = await api(`/api/municipios?departamento_id=${encodeURIComponent(departamentoId)}`);
    if (requestId !== state.municipioRequest) return;
    municipioSelect.replaceChildren(new Option("Seleccione", ""));
    for (const item of payload.data) {
        municipioSelect.append(new Option(item.nombre, String(item.id)));
    }
    municipioSelect.disabled = false;
    if (selectedId) municipioSelect.value = String(selectedId);
}

function avatar(patient) {
    const name = fullName(patient);
    if (patient.foto_url && /^\/uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(patient.foto_url)) {
        const image = el("img", "avatar");
        image.src = patient.foto_url;
        image.alt = `Fotografía de ${name}`;
        return image;
    }
    const fallback = el("span", "avatar avatar-fallback", initials(patient));
    fallback.setAttribute("aria-hidden", "true");
    return fallback;
}

function renderPatients() {
    const meta = state.meta;
    listStatus.hidden = true;
    tbody.replaceChildren();

    if (meta.total === 0) {
        tableWrap.hidden = true;
        pager.hidden = true;
        empty.hidden = false;
        summary.textContent = state.query ? "Sin coincidencias" : "Sin pacientes registrados";
        emptyTitle.textContent = state.query ? "Nadie coincide con la búsqueda" : "El censo está vacío";
        emptyText.textContent = state.query
            ? "Pruebe con otro nombre o correo."
            : "Registre el primer paciente para empezar.";
        return;
    }

    empty.hidden = true;
    tableWrap.hidden = false;
    const start = (meta.page - 1) * meta.per_page + 1;
    const end = Math.min(meta.page * meta.per_page, meta.total);
    summary.textContent = `Mostrando ${start}–${end} de ${meta.total}`;

    for (const patient of state.patients) {
        const row = document.createElement("tr");
        const identity = document.createElement("td");
        identity.dataset.label = "Paciente";
        const who = el("div", "who");
        const text = el("div");
        text.append(el("strong", null, fullName(patient)), el("span", null, patient.genero));
        who.append(avatar(patient), text);
        identity.append(who);

        const documentCell = document.createElement("td");
        documentCell.dataset.label = "Documento";
        documentCell.append(
            el("strong", null, patient.numero_documento),
            document.createElement("br"),
            el("span", null, patient.tipo_documento),
        );

        const place = document.createElement("td");
        place.dataset.label = "Ubicación";
        place.textContent = `${patient.municipio}, ${patient.departamento}`;

        const mail = document.createElement("td");
        mail.dataset.label = "Correo";
        mail.textContent = patient.correo;

        const actions = document.createElement("td");
        actions.dataset.label = "Acciones";
        const bar = el("div", "row-actions");
        const edit = el("button", "btn btn-ghost", "Editar");
        edit.type = "button";
        edit.dataset.action = "edit";
        edit.dataset.id = String(patient.id);
        const remove = el("button", "btn btn-ghost", "Eliminar");
        remove.type = "button";
        remove.dataset.action = "delete";
        remove.dataset.id = String(patient.id);
        bar.append(edit, remove);
        actions.append(bar);
        row.append(identity, documentCell, place, mail, actions);
        tbody.append(row);
    }

    renderPager(meta);
}

function renderPager(meta) {
    pager.replaceChildren();
    if (meta.last_page <= 1) {
        pager.hidden = true;
        return;
    }
    pager.hidden = false;
    const previous = el("button", "btn btn-ghost", "Anterior");
    previous.type = "button";
    previous.disabled = meta.page <= 1;
    previous.addEventListener("click", () => goToPage(meta.page - 1));
    pager.append(previous);

    const start = Math.max(1, meta.page - 2);
    const end = Math.min(meta.last_page, start + 4);
    for (let page = start; page <= end; page += 1) {
        const button = el("button", "btn btn-ghost", String(page));
        button.type = "button";
        if (page === meta.page) button.setAttribute("aria-current", "page");
        button.addEventListener("click", () => goToPage(page));
        pager.append(button);
    }

    const next = el("button", "btn btn-ghost", "Siguiente");
    next.type = "button";
    next.disabled = meta.page >= meta.last_page;
    next.addEventListener("click", () => goToPage(meta.page + 1));
    pager.append(next);
}

function goToPage(page) {
    state.page = page;
    loadPatients();
}

async function loadPatients() {
    const requestId = ++state.listRequest;
    const params = new URLSearchParams({
        page: String(state.page),
        per_page: String(state.perPage),
    });
    if (state.query) params.set("q", state.query);

    listStatus.hidden = false;
    listStatus.replaceChildren(document.createTextNode("Cargando pacientes…"));
    empty.hidden = true;
    tableWrap.hidden = true;
    pager.hidden = true;

    try {
        const payload = await api(`/api/pacientes?${params.toString()}`);
        if (requestId !== state.listRequest) return;
        state.patients = payload.data;
        state.meta = payload.meta;
        state.page = payload.meta.page;
        renderPatients();
    } catch (error) {
        if (requestId !== state.listRequest || error.status === 401) return;
        listStatus.hidden = false;
        listStatus.replaceChildren();
        const retry = el("button", "btn btn-ghost", "Reintentar");
        retry.type = "button";
        retry.addEventListener("click", () => loadPatients());
        listStatus.append(el("p", null, error.message || "No fue posible cargar los pacientes."), retry);
        summary.textContent = "No fue posible cargar los pacientes.";
    }
}

function clearFormErrors() {
    patientForm.querySelectorAll("[data-error-for]").forEach((node) => {
        node.textContent = "";
    });
    patientForm.querySelectorAll(".is-invalid").forEach((node) => node.classList.remove("is-invalid"));
    formError.hidden = true;
    formError.textContent = "";
}

function showFieldErrors(errors) {
    let focused = false;
    for (const [field, messages] of Object.entries(errors)) {
        const text = Array.isArray(messages) ? messages[0] : String(messages);
        const slot = patientForm.querySelector(`[data-error-for="${CSS.escape(field)}"]`);
        const input = patientForm.elements.namedItem(field);
        if (slot) slot.textContent = text;
        if (input && "classList" in input) {
            input.classList.add("is-invalid");
            if (!focused && typeof input.focus === "function") {
                input.focus();
                focused = true;
            }
        }
        if (!slot) {
            formError.hidden = false;
            formError.textContent = text;
        }
    }
}

function resetForm() {
    patientForm.reset();
    clearFormErrors();
    fotoPreview.hidden = true;
    fotoPreview.removeAttribute("src");
    municipioSelect.disabled = true;
    municipioSelect.replaceChildren(new Option("Seleccione un departamento", ""));
    fotoHint.textContent = "JPG, PNG o WEBP. Máximo 2 MB.";
}

function validateClient(data, file) {
    const errors = {};
    const push = (field, message) => {
        if (!errors[field]) errors[field] = [];
        errors[field].push(message);
    };
    const name = (value, field, label, required) => {
        const clean = String(value || "").trim().replace(/\s+/g, " ");
        if (!clean) {
            if (required) push(field, `Ingrese ${label}.`);
            return;
        }
        if (!/^[\p{L}][\p{L}\s'-]{1,59}$/u.test(clean)) {
            push(field, `Use solo letras, espacios o guiones (entre 2 y 60 caracteres) en ${label}.`);
        }
    };

    if (!data.tipo_documento_id) push("tipo_documento_id", "Seleccione el tipo de documento.");
    if (!data.genero_id) push("genero_id", "Seleccione el género.");
    if (!data.departamento_id) push("departamento_id", "Seleccione el departamento.");
    if (!data.municipio_id) push("municipio_id", "Seleccione el municipio.");
    if (!/^[A-Za-z0-9]{5,20}$/.test(String(data.numero_documento || "").trim())) {
        push("numero_documento", "El documento debe tener entre 5 y 20 letras o números.");
    }
    name(data.nombre1, "nombre1", "el primer nombre", true);
    name(data.nombre2, "nombre2", "el segundo nombre", false);
    name(data.apellido1, "apellido1", "el primer apellido", true);
    name(data.apellido2, "apellido2", "el segundo apellido", false);

    const correo = String(data.correo || "").trim();
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(correo) || correo.length > 180) {
        push("correo", "Ingrese un correo electrónico válido.");
    }
    if (file && file.size > 0) {
        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            push("foto", "La fotografía debe ser JPG, PNG o WEBP.");
        }
        if (file.size > 2 * 1024 * 1024) {
            push("foto", "La fotografía no puede superar 2 MB.");
        }
    }
    return errors;
}

async function openCreate() {
    await loadCatalogs();
    state.editingId = null;
    resetForm();
    patientTitle.textContent = "Nuevo paciente";
    patientDialog.showModal();
}

async function openEdit(patient) {
    await loadCatalogs();
    state.editingId = patient.id;
    resetForm();
    patientTitle.textContent = "Editar paciente";
    patientForm.nombre1.value = patient.nombre1 || "";
    patientForm.nombre2.value = patient.nombre2 || "";
    patientForm.apellido1.value = patient.apellido1 || "";
    patientForm.apellido2.value = patient.apellido2 || "";
    patientForm.tipo_documento_id.value = String(patient.tipo_documento_id);
    patientForm.numero_documento.value = patient.numero_documento || "";
    patientForm.genero_id.value = String(patient.genero_id);
    patientForm.correo.value = patient.correo || "";
    departamentoSelect.value = String(patient.departamento_id);
    await loadMunicipios(patient.departamento_id, patient.municipio_id);
    if (patient.foto_url) {
        fotoPreview.src = patient.foto_url;
        fotoPreview.alt = `Fotografía actual de ${fullName(patient)}`;
        fotoPreview.hidden = false;
        fotoHint.textContent = "Si no elige un archivo, se conserva la fotografía actual.";
    }
    patientDialog.showModal();
}

function openDelete(patient) {
    state.pendingDelete = patient;
    deleteName.textContent = fullName(patient);
    deleteDialog.showModal();
}

async function enterApp() {
    loginView.hidden = true;
    appView.hidden = false;
    userName.textContent = state.user?.nombre || "";
    await loadCatalogs();
    await loadPatients();
}

loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    loginError.hidden = true;
    loginSubmit.disabled = true;
    loginSubmit.textContent = "Ingresando…";
    try {
        const result = await api("/api/auth/login", {
            method: "POST",
            auth: false,
            json: {
                email: loginForm.email.value.trim(),
                password: loginForm.password.value,
            },
        });
        state.token = result.token;
        state.user = result.user;
        sessionStorage.setItem("sihos_token", result.token);
        await enterApp();
    } catch (error) {
        loginError.hidden = false;
        loginError.textContent = error.message || "No fue posible iniciar sesión.";
    } finally {
        loginSubmit.disabled = false;
        loginSubmit.textContent = "Entrar al censo";
    }
});

logoutButton.addEventListener("click", async () => {
    try {
        await api("/api/auth/logout", { method: "POST" });
    } catch {
        // La sesión local se cierra aunque el token ya no sirva.
    }
    clearSession();
    showLogin();
});

searchForm.addEventListener("submit", (event) => {
    event.preventDefault();
    window.clearTimeout(state.searchTimer);
    state.query = searchInput.value.trim();
    state.page = 1;
    loadPatients();
});

searchInput.addEventListener("input", () => {
    window.clearTimeout(state.searchTimer);
    state.searchTimer = window.setTimeout(() => {
        state.query = searchInput.value.trim();
        state.page = 1;
        loadPatients();
    }, 300);
});

newPatientButton.addEventListener("click", () => {
    openCreate().catch((error) => toast(error.message || "No se pudo abrir el formulario.", "err"));
});

departamentoSelect.addEventListener("change", () => {
    loadMunicipios(departamentoSelect.value, "").catch((error) => {
        toast(error.message || "No se pudieron cargar los municipios.", "err");
    });
});

fotoInput.addEventListener("change", () => {
    const file = fotoInput.files && fotoInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.addEventListener("load", () => {
        if (typeof reader.result === "string") {
            fotoPreview.src = reader.result;
            fotoPreview.alt = "Vista previa de la fotografía";
            fotoPreview.hidden = false;
        }
    });
    reader.readAsDataURL(file);
});

tbody.addEventListener("click", (event) => {
    const button = event.target.closest("[data-action]");
    if (!button) return;
    const patient = state.patients.find((item) => item.id === Number(button.dataset.id));
    if (!patient) return;
    if (button.dataset.action === "edit") {
        openEdit(patient).catch((error) => toast(error.message || "No se pudo abrir el paciente.", "err"));
    }
    if (button.dataset.action === "delete") openDelete(patient);
});

patientForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearFormErrors();
    const data = {
        tipo_documento_id: patientForm.tipo_documento_id.value,
        numero_documento: patientForm.numero_documento.value,
        nombre1: patientForm.nombre1.value,
        nombre2: patientForm.nombre2.value,
        apellido1: patientForm.apellido1.value,
        apellido2: patientForm.apellido2.value,
        genero_id: patientForm.genero_id.value,
        departamento_id: patientForm.departamento_id.value,
        municipio_id: patientForm.municipio_id.value,
        correo: patientForm.correo.value,
    };
    const file = fotoInput.files && fotoInput.files[0];
    const clientErrors = validateClient(data, file);
    if (Object.keys(clientErrors).length > 0) {
        showFieldErrors(clientErrors);
        toast("Revise los campos marcados.", "err");
        return;
    }

    const wasDisabled = municipioSelect.disabled;
    municipioSelect.disabled = false;
    const body = new FormData(patientForm);
    municipioSelect.disabled = wasDisabled;
    const path = state.editingId ? `/api/pacientes/${state.editingId}` : "/api/pacientes";
    saveButton.disabled = true;
    saveButton.textContent = "Guardando…";
    try {
        const result = await api(path, { method: "POST", body });
        patientDialog.close();
        toast(result.message || "Paciente creado correctamente.", "ok");
        if (!state.editingId) {
            state.query = "";
            searchInput.value = "";
            state.page = 1;
        }
        await loadPatients();
    } catch (error) {
        if (error.status === 401) return;
        if (error.errors && Object.keys(error.errors).length > 0) showFieldErrors(error.errors);
        else {
            formError.hidden = false;
            formError.textContent = error.message || "No se pudo guardar el paciente.";
        }
        toast(error.message || "No se pudo guardar el paciente.", "err");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = "Guardar paciente";
    }
});

document.querySelectorAll("[data-close-dialog]").forEach((button) => {
    button.addEventListener("click", () => patientDialog.close());
});

deleteCancel.addEventListener("click", () => deleteDialog.close());

deleteConfirm.addEventListener("click", async () => {
    const patient = state.pendingDelete;
    if (!patient) return;
    deleteConfirm.disabled = true;
    try {
        const result = await api(`/api/pacientes/${patient.id}`, { method: "DELETE" });
        deleteDialog.close();
        toast(result.message || "Paciente eliminado correctamente.", "ok");
        if (state.patients.length === 1 && state.page > 1) state.page -= 1;
        await loadPatients();
    } catch (error) {
        if (error.status === 401) return;
        toast(error.message || "Error al eliminar paciente.", "err");
    } finally {
        deleteConfirm.disabled = false;
    }
});

async function boot() {
    if (!state.token) {
        showLogin();
        return;
    }
    try {
        const me = await api("/api/auth/me");
        state.user = me.data;
        await enterApp();
    } catch {
        clearSession();
        showLogin();
    }
}

boot();

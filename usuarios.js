// usuarios.js — CRUD de usuarios (módulo protegido por sesión + CSRF).
document.addEventListener("DOMContentLoaded", () => {
    const alertBox = document.getElementById("user-alert");
    const tbody = document.getElementById("users-body");
    const form = document.getElementById("user-form");
    const formTitle = document.getElementById("user-form-title");
    const btnNew = document.getElementById("btn-new");
    const btnCancel = document.getElementById("btn-cancel");
    const fId = document.getElementById("user-id");
    const fNombre = document.getElementById("user-nombre");
    const fEmail = document.getElementById("user-email");
    const fPass = document.getElementById("user-password");
    let editingId = null;

    function showAlert(msg, type) {
        alertBox.textContent = msg;
        alertBox.className = `alert ${type}`;
        alertBox.classList.remove("hidden");
    }

    async function api(payload) {
        const res = await fetch("users_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ csrf_token: window.CSRF_TOKEN, ...payload })
        });
        if (res.status === 401) {
            window.location.href = "index.html";
            return { status: "error" };
        }
        return res.json();
    }

    function esc(s) {
        return String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    }

    async function load() {
        const data = await api({ action: "list" });
        if (data.status !== "success") {
            tbody.innerHTML = `<tr><td colspan="5">Error al cargar.</td></tr>`;
            return;
        }
        if (data.users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">Sin usuarios.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.users.map(u => `
            <tr>
                <td>#${u.id}</td>
                <td>${esc(u.nombre)}${u.id === window.MY_USER_ID ? " (tú)" : ""}</td>
                <td>${esc(u.email)}</td>
                <td>${esc(u.created_at)}</td>
                <td class="row-actions">
                    <button type="button" class="link-button" data-edit="${u.id}">Editar</button>
                    ${u.id === window.MY_USER_ID ? "" : `<button type="button" class="link-button danger" data-del="${u.id}">Eliminar</button>`}
                </td>
            </tr>`).join("");
    }

    function openForm(id, nombre, email) {
        editingId = id;
        formTitle.textContent = id ? `Modificar usuario #${id}` : "Nuevo usuario";
        fId.value = id || "";
        fNombre.value = nombre || "";
        fEmail.value = email || "";
        fPass.value = "";
        fPass.placeholder = id ? "Vacío = conservar la actual" : "Mínimo 8 caracteres, letras y números";
        form.classList.remove("hidden");
        fNombre.focus();
    }

    btnNew.addEventListener("click", () => openForm(null));
    btnCancel.addEventListener("click", () => form.classList.add("hidden"));

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const payload = editingId
            ? { action: "update", id: editingId, nombre: fNombre.value.trim(), email: fEmail.value.trim(), password: fPass.value }
            : { action: "create", nombre: fNombre.value.trim(), email: fEmail.value.trim(), password: fPass.value };
        const data = await api(payload);
        if (data.status === "success") {
            showAlert(data.message, "success");
            form.classList.add("hidden");
            form.reset();
            editingId = null;
            load();
        } else {
            showAlert(data.message || "No se pudo guardar.", "error");
        }
    });

    tbody.addEventListener("click", async (e) => {
        const editBtn = e.target.closest("[data-edit]");
        const delBtn = e.target.closest("[data-del]");
        if (editBtn) {
            const row = editBtn.closest("tr").children;
            openForm(parseInt(editBtn.dataset.edit, 10), row[1].textContent.replace(" (tú)", ""), row[2].textContent);
        } else if (delBtn) {
            const id = parseInt(delBtn.dataset.del, 10);
            if (!confirm(`¿Eliminar al usuario #${id}?`)) return;
            const data = await api({ action: "delete", id });
            showAlert(data.message, data.status === "success" ? "success" : "error");
            if (data.status === "success") load();
        }
    });

    load();
});

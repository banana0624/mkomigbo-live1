<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
session_start();

require __DIR__ . '/auth.php';
requireLogin();

$title = "RBAC - Roles";
require __DIR__ . '/layout.php';
?>

<h2>Role Management</h2>

<div style="margin-bottom:10px;">
    <input type="text" id="newRoleName" placeholder="New role name">
    <button onclick="createRole()">Create Role</button>
</div>

<div id="rolesContainer"></div>

<script>
async function loadRoles() {
    const res = await fetch('rbac_roles_data.php');
    const json = await res.json();

    const container = document.getElementById('rolesContainer');
    container.innerHTML = '';

    json.roles.forEach(role => {
        const div = document.createElement('div');
        div.style.border = "1px solid #ccc";
        div.style.margin = "10px";
        div.style.padding = "10px";

        const caps = json.capabilities;

        let html = `<h3>${role.name}</h3>`;

        caps.forEach(cap => {
            const checked = role.capabilities.includes(cap.name) ? 'checked' : '';
            html += `
                <label>
                    <input type="checkbox"
                        data-role="${role.id}"
                        data-cap="${cap.id}"
                        ${checked}
                        onchange="toggleCap(this)">
                    ${cap.name}
                </label><br>
            `;
        });

        div.innerHTML = html;
        container.appendChild(div);
    });
}

async function toggleCap(el) {
    const roleId = el.dataset.role;
    const capId = el.dataset.cap;
    const assigned = el.checked;

    await fetch('rbac_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({roleId, capId, assigned})
    });
}

async function createRole() {
    const name = document.getElementById('newRoleName').value.trim();
    if (!name) return;

    await fetch('rbac_create_role.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({name})
    });

    document.getElementById('newRoleName').value = '';
    loadRoles();
}

loadRoles();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
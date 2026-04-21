<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
requireLogin();

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

$title = "Submissions";
require __DIR__ . '/layout.php';
?>

<h2>Submissions</h2>

<div style="margin-bottom:10px;">
    <button id="bulk-approve">Bulk Approve</button>
    <button id="bulk-reject">Bulk Reject</button>
</div>

<div style="margin-bottom:15px;">
  <input type="text" id="searchBox" placeholder="Search..." style="padding:6px; width:250px;">

  <select id="filterStatus">
    <option value="">All Status</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>

  <select id="filterType">
    <option value="">All Types</option>
    <option value="new_entry">New Entry</option>
    <option value="edit">Edit</option>
  </select>
</div>

<div><strong id="resultCount">0 results</strong></div>
<div id="pagination"></div>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th><input type="checkbox" id="select-all"></th>
    <th onclick="setSort('id')">ID</th>
    <th>Subject</th>
    <th>Type</th>
    <th onclick="setSort('status')">Status</th>
    <th>Message</th>
    <th onclick="setSort('created_at')">Date</th>
    <th>Actions</th>
</tr>
</thead>
<tbody id="submissionsTable"></tbody>
</table>

<!-- TIMELINE MODAL -->
<div id="timelineModal" style="
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
display:none;
overflow:auto;
">
<div style="background:#fff;margin:50px auto;padding:20px;width:80%;max-width:900px;">
<h3>Submission Timeline</h3>
<div id="timelineContent"></div>
<button onclick="closeTimeline()">Close</button>
</div>
</div>

<!-- TOAST -->
<div id="toast" style="position:fixed;bottom:20px;right:20px;background:#333;color:#fff;padding:10px 15px;display:none;border-radius:4px;"></div>

<script>
const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";

let timer = null;

let state = {
    page: 1,
    q: '',
    status: '',
    type: '',
    sort: 'created_at',
    order: 'desc'
};

function showToast(msg, ok=true) {
    const t = document.getElementById('toast');
    t.innerText = msg;
    t.style.background = ok ? '#28a745' : '#dc3545';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2500);
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, s => ({
        "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"
    }[s]));
}

function updateURL() {
    history.replaceState(null, '', '?' + new URLSearchParams(state));
}

function loadFromURL() {
    const p = new URLSearchParams(location.search);
    state.page = parseInt(p.get('page')) || 1;
    state.q = p.get('q') || '';
    state.status = p.get('status') || '';
    state.type = p.get('type') || '';
    state.sort = p.get('sort') || 'created_at';
    state.order = p.get('order') || 'desc';

    searchBox.value = state.q;
    filterStatus.value = state.status;
    filterType.value = state.type;
}

function fetchSubmissions(page = 1) {
    state.page = page;
    updateURL();

    fetch('submissions_search.php?' + new URLSearchParams(state))
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('submissionsTable');
        tbody.innerHTML = '';

        if (!res.data.length) {
            tbody.innerHTML = `<tr><td colspan="8">No submissions</td></tr>`;
            return;
        }

        res.data.forEach(row => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td><input type="checkbox" class="row-select" value="${row.id}"></td>
                <td>${row.id}</td>
                <td>${escapeHtml(row.subject_area)}</td>
                <td>${row.submission_type}</td>
                <td class="status-cell">${row.status}</td>
                <td onclick="editCell(this, ${row.id}, 'message')">
                    ${escapeHtml(row.message)}
                </td>
                <td>${row.created_at}</td>
                <td>
                    <button onclick="updateStatus(${row.id}, 'approved', this)">Approve</button>
                    <button onclick="updateStatus(${row.id}, 'rejected', this)">Reject</button>
                    <button onclick="softDelete(${row.id})">Delete</button>
                    <button onclick="viewTimeline(${row.id})">Timeline</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        resultCount.innerText = `${res.total} result(s)`;
        renderPagination(res.total, res.page, res.limit);
    });
}

function renderPagination(total, page, limit) {
    const el = document.getElementById('pagination');
    el.innerHTML = '';

    if (total === 0) return;

    const pages = Math.ceil(total / limit);

    for (let i = 1; i <= pages; i++) {
        el.innerHTML += `
            <button onclick="fetchSubmissions(${i})" ${i === page ? 'disabled' : ''}>
                ${i}
            </button>`;
    }
}

async function viewTimeline(id) {
    const modal = document.getElementById('timelineModal');
    const container = document.getElementById('timelineContent');

    modal.style.display = 'block';
    container.innerHTML = 'Loading...';

    try {
        const res = await fetch(`submission_timeline.php?id=${id}`);
        const data = await res.json();

        let html = '';

        if (data.actions?.length) {
            html += '<h4>Actions</h4>';
            data.actions.forEach(a => {
                html += `<div><strong>${a.action}</strong> by ${a.actor} at ${a.created_at}</div>`;
            });
        }

        if (data.versions?.length) {
            html += '<h4>Changes</h4>';

            data.versions.forEach(v => {
                html += `<div style="margin-bottom:15px;"><strong>${v.created_at}</strong>`;

                if (!v.diff || !Object.keys(v.diff).length) {
                    html += `<div>Initial version</div>`;
                } else {
                    Object.entries(v.diff).forEach(([field, change]) => {
                        html += `
                        <div style="display:flex;gap:10px;margin-top:5px;">
                            <div style="flex:1;background:#ffecec;padding:6px;">
                                ${escapeHtml(change.old ?? '')}
                            </div>
                            <div style="flex:1;background:#eaffea;padding:6px;">
                                ${escapeHtml(change.new ?? '')}
                            </div>
                        </div>`;
                    });
                }

                html += `</div>`;
            });

            html += `<a href="export_timeline.php?id=${id}" target="_blank">Export CSV</a>`;
        }

        container.innerHTML = html || '<p>No activity</p>';

    } catch {
        container.innerHTML = '<p>Error loading timeline</p>';
    }
}

function closeTimeline() {
    document.getElementById('timelineModal').style.display = 'none';
}

loadFromURL();
fetchSubmissions(state.page);
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
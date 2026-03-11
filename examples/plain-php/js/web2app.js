let statusInterval;

async function init() {
    const res = await fetch('?action=init');
    const data = await res.json();
    
    if (data.success) {
        const linkRes = await fetch('?action=link');
        const linkData = await linkRes.json();
        
        document.getElementById('smart-id-btn').href = linkData.url;
        
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('auth-container').classList.remove('hidden');
        
        statusInterval = setInterval(checkStatus, 2000);
    }
}

async function checkStatus() {
    const res = await fetch('?action=status');
    const data = await res.json();
    const statusEl = document.getElementById('status');

    if (data.state === 'WAITING_FOR_CALLBACK') {
        document.getElementById('smart-id-btn').classList.add('hidden');
        statusEl.innerHTML = `
            <span class="spinner"></span>
            <span>Authenticating...</span>`;
        return; // keep polling
    }

    if (data.state === 'COMPLETE') {
        clearInterval(statusInterval);
        statusEl.className = 'status';

        if (data.endResult === 'OK' && data.user) {
            document.getElementById('smart-id-btn').classList.add('hidden');
            let checksHtml = '';
            if (data.checks) {
                checksHtml = data.checks.map(c =>
                    `<div style="background: var(--smart-id-green-light); border-radius: 8px; padding: 8px 12px; margin: 4px 0; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--smart-id-green);">&#10003;</span>
                        <span style="color: var(--text-primary);">${c.label}</span>
                    </div>`
                ).join('');
            }
            statusEl.innerHTML = `
                <span class="success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Authentication verified!
                </span>
                ${checksHtml}
                <div class="user-info">
                    <p><strong>Name:</strong> ${data.user.fullName}</p>
                    <p><strong>Identity Code:</strong> ${data.user.identityCode}</p>
                    <p><strong>Country:</strong> ${data.user.country}</p>
                    ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${data.user.dateOfBirth}</p>` : ''}
                </div>`;
        } else if (data.endResult === 'VALIDATION_ERROR') {
            statusEl.innerHTML = `<span style="color: #dc2626;">Validation failed: ${data.error || 'Unknown error'}</span>`;
        } else {
            statusEl.innerHTML = `<span style="color: #dc2626;">Authentication failed: ${data.endResult || 'Unknown error'}</span>`;
        }
    }
}

// Only init if not on callback URL
if (!window.location.search.includes('action=callback')) {
    init();
}

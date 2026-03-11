let refreshInterval;
let statusInterval;

async function init() {
    const res = await fetch('?action=init');
    const data = await res.json();
    if (data.success) {
        refreshQR();
        refreshInterval = setInterval(refreshQR, 1000);
        statusInterval = setInterval(checkStatus, 2000);
    }
}

async function refreshQR() {
    const res = await fetch('?action=qr&t=' + Date.now());
    const data = await res.json();
    if (data.qrImage) {
        document.getElementById('qr-code').src = data.qrImage;
    }
}

async function checkStatus() {
    const res = await fetch('?action=status');
    const data = await res.json();
    if (data.state === 'COMPLETE') {
        clearInterval(refreshInterval);
        clearInterval(statusInterval);
        const statusEl = document.getElementById('status');
        statusEl.className = 'status';

        if (data.endResult === 'OK' && data.user) {
            // Display user information after successful authentication
            statusEl.innerHTML = `
                <span class="success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Authentication successful!
                </span>
                <div class="user-info">
                    <p><strong>Name:</strong> ${data.user.fullName}</p>
                    <p><strong>Identity Code:</strong> ${data.user.identityCode}</p>
                    <p><strong>Country:</strong> ${data.user.country}</p>
                    ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${data.user.dateOfBirth}</p>` : ''}
                    ${data.user.gender ? `<p><strong>Gender:</strong> ${data.user.gender === 'M' ? 'Male' : 'Female'}</p>` : ''}
                    ${data.user.age !== null ? `<p><strong>Age:</strong> ${data.user.age}</p>` : ''}
                </div>`;
        } else if (data.endResult === 'VALIDATION_ERROR') {
            statusEl.innerHTML = `<span style="color: #dc2626;">Validation failed: ${data.error || 'Unknown error'}</span>`;
        } else {
            statusEl.innerHTML = `<span style="color: #dc2626;">Authentication failed: ${data.endResult || 'Unknown error'}</span>`;
        }
    }
}

init();

let statusInterval;
let identifyMode = 'identity';

// Toggle between identity number and document number modes
document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        identifyMode = btn.dataset.mode;
        document.getElementById('identity-fields').classList.toggle('hidden', identifyMode !== 'identity');
        document.getElementById('document-fields').classList.toggle('hidden', identifyMode !== 'document');
    });
});

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = document.getElementById('submit-btn');
    const errorMessage = document.getElementById('error-message');

    let url;
    if (identifyMode === 'document') {
        const documentNumber = document.getElementById('documentNumber').value;
        if (!documentNumber) { errorMessage.textContent = 'Document number is required'; return; }
        url = `?action=init&documentNumber=${encodeURIComponent(documentNumber)}`;
    } else {
        const country = document.getElementById('country').value;
        const idCode = document.getElementById('idCode').value;
        if (!idCode) { errorMessage.textContent = 'Identity number is required'; return; }
        url = `?action=init&country=${encodeURIComponent(country)}&idCode=${encodeURIComponent(idCode)}`;
    }

    errorMessage.textContent = '';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Initiating...';

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.error) {
            errorMessage.textContent = data.error;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Continue with Smart-ID';
            return;
        }

        if (data.success) {
            document.getElementById('login-form').classList.add('hidden');
            document.getElementById('verification-container').style.display = 'block';
            document.getElementById('verification-code').textContent = data.verificationCode;

            statusInterval = setInterval(checkStatus, 2000);
        }
    } catch (err) {
        errorMessage.textContent = 'Connection error. Please try again.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Continue with Smart-ID';
    }
});

async function checkStatus() {
    const res = await fetch('?action=status');
    const data = await res.json();

    if (data.state === 'COMPLETE') {
        clearInterval(statusInterval);
        const statusEl = document.getElementById('status');
        statusEl.className = 'status';

        const tryAgainBtn = '<button class="btn" style="margin-top: 16px;" onclick="resetForm()">Try again</button>';

        if (data.endResult === 'OK' && data.user) {
            statusEl.innerHTML = `
                <span class="success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Authentication successful!
                </span>
                <div class="user-info">
                    <p><strong>Name:</strong> ${escapeHtml(data.user.fullName)}</p>
                    <p><strong>Identity Code:</strong> ${escapeHtml(data.user.identityCode)}</p>
                    <p><strong>Country:</strong> ${escapeHtml(data.user.country)}</p>
                    ${data.user.dateOfBirth ? `<p><strong>Date of Birth:</strong> ${escapeHtml(data.user.dateOfBirth)}</p>` : ''}
                    ${data.user.gender ? `<p><strong>Gender:</strong> ${data.user.gender === 'M' ? 'Male' : 'Female'}</p>` : ''}
                    ${data.user.age !== null ? `<p><strong>Age:</strong> ${escapeHtml(String(data.user.age))}</p>` : ''}
                </div>
                ${tryAgainBtn}`;
        } else if (data.endResult === 'VALIDATION_ERROR') {
            statusEl.innerHTML = `<span style="color: #dc2626;">Validation failed: ${escapeHtml(data.error || 'Unknown error')}</span>${tryAgainBtn}`;
        } else {
            statusEl.innerHTML = `<span style="color: #dc2626;">Authentication failed: ${escapeHtml(data.endResult || 'Unknown error')}</span>${tryAgainBtn}`;
        }
    }
}

function resetForm() {
    document.getElementById('login-form').classList.remove('hidden');
    document.getElementById('verification-container').style.display = 'none';
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('submit-btn').textContent = 'Continue with Smart-ID';
    document.getElementById('status').className = 'status waiting';
    document.getElementById('status').innerHTML = '<span class="spinner"></span><span>Waiting for confirmation...</span>';
    identifyMode = 'identity';
    document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.toggle-btn[data-mode="identity"]').classList.add('active');
    document.getElementById('identity-fields').classList.remove('hidden');
    document.getElementById('document-fields').classList.add('hidden');
}

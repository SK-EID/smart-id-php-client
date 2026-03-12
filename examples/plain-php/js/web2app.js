async function init() {
    const res = await fetch('?action=init');
    const data = await res.json();
    
    if (data.success) {
        const linkRes = await fetch('?action=link');
        const linkData = await linkRes.json();
        
        document.getElementById('smart-id-btn').href = linkData.url;
        
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('auth-container').classList.remove('hidden');
    }
}

init();

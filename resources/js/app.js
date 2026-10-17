import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* CSRF-aware fetch helper para sa AJAX (stock +/- at POS) */
window.api = async function (url, { method = 'GET', body = null } = {}) {
    const res = await fetch(url, {
        method,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: body ? JSON.stringify(body) : null,
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(first || data.message || 'Something went wrong. Please try again.');
    }
    return data;
};

window.peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

Alpine.start();

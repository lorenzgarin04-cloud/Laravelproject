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

/**
 * Kinukuha ang napiling larawan sa <input type="file">, pinapaliit (max 1200px) at ginagawang JPG
 * para laging pasok sa upload limit ng server (PNG, JPG, JPEG, WEBP, GIF, BMP, AVIF ay tanggap).
 * Ibinabalik ang preview URL. Nagta-throw ng malinaw na error kung hindi suportado ang file.
 */
window.prepareImage = async function (input, { maxDim = 1200, quality = 0.86 } = {}) {
    const file = input.files && input.files[0];
    if (!file) return null;

    const looksLikeImage = /^image\//.test(file.type) || /\.(png|jpe?g|webp|gif|bmp|avif|heic|heif)$/i.test(file.name);
    if (!looksLikeImage) {
        throw new Error('Please choose an image file (PNG, JPG, JPEG, WEBP, GIF or BMP).');
    }

    const unsupported = 'This image format cannot be read by your browser (HEIC/HEIF?). Please choose a PNG or JPG file.';
    let source;
    try {
        source = await createImageBitmap(file);
    } catch (e) {
        source = await new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(unsupported));
            img.src = URL.createObjectURL(file);
        });
    }

    const w = source.width || source.naturalWidth;
    const h = source.height || source.naturalHeight;
    if (!w || !h) throw new Error(unsupported);

    const scale = Math.min(1, maxDim / Math.max(w, h));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(w * scale);
    canvas.height = Math.round(h * scale);

    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff'; // puting background para sa transparent PNG
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(source, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
    if (!blob) throw new Error(unsupported);

    const base = (file.name.replace(/\.[^.]+$/, '') || 'photo').replace(/[^A-Za-z0-9_-]/g, '_');
    const out = new File([blob], base + '.jpg', { type: 'image/jpeg' });

    const dt = new DataTransfer();
    dt.items.add(out);
    input.files = dt.files;

    return URL.createObjectURL(out);
};

Alpine.start();

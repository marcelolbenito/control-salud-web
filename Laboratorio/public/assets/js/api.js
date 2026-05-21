/**
 * Wrapper centralizado de fetch().
 * Respeta APP_BASE_PATH (/laboratorio) via meta lab-base.
 * v6: meta lab-api-bridge → api.php (Nginx); query aparte de e=
 */
export const LAB_API_JS_VERSION = '9';

function apiBridgeBase() {
    const bridge = document.querySelector('meta[name="lab-api-bridge"]')?.getAttribute('content') || '';
    return bridge.replace(/\/$/, '');
}

export function labPath(path) {
    const meta = document.querySelector('meta[name="lab-base"]');
    const base = (meta?.getAttribute('content') || '').replace(/\/$/, '');
    const queryRouter = document.querySelector('meta[name="lab-query-router"]')?.getAttribute('content') === '1';

    if (!path.startsWith('/')) {
        path = '/' + path;
    }

    let extraQuery = '';
    const qPos = path.indexOf('?');
    if (qPos !== -1) {
        extraQuery = path.slice(qPos + 1);
        path = path.slice(0, qPos);
    }

    if (path.startsWith('/assets/')) {
        return base + path + (extraQuery ? `?${extraQuery}` : '');
    }

    const bridge = apiBridgeBase();
    if (bridge !== '' && path.startsWith('/api/')) {
        const ep = path.replace(/^\/api\//, '').replace(/\/$/, '');
        const url = `${bridge}?e=${encodeURIComponent(ep)}`;
        return extraQuery ? `${url}&${extraQuery}` : url;
    }

    if (queryRouter) {
        const r = path.replace(/^\//, '').replace(/\//g, '.');
        const url = `${base}/?r=${encodeURIComponent(r)}`;
        return extraQuery ? `${url}&${extraQuery}` : url;
    }

    return base + path + (extraQuery ? `?${extraQuery}` : '');
}

async function request(path, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    const url = labPath(path);
    const res = await fetch(url, { credentials: 'same-origin', ...options, headers });

    const text = await res.text();
    let body = null;
    if (text) {
        try {
            body = JSON.parse(text);
        } catch {
            const hint = text.trimStart().startsWith('<')
                ? ' (el servidor devolvió HTML, no JSON)'
                : '';
            const err = new Error('Respuesta no JSON' + hint + ` [${url}]`);
            err.fetchedUrl = url;
            throw err;
        }
    }

    if (!res.ok || (body && body.success === false)) {
        const err = new Error(body?.error?.message || `HTTP ${res.status}`);
        err.status = res.status;
        err.code = body?.error?.code || 'ERROR';
        err.fields = body?.error?.fields || {};
        err.fetchedUrl = url;
        throw err;
    }

    return body?.data ?? null;
}

export const api = {
    get: (path) => request(path),
    post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body) }),
    put: (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body) }),
    delete: (path) => request(path, { method: 'DELETE' }),
};

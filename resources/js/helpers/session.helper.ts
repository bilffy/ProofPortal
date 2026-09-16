const pingUrl = () => {
    // Always same-origin relative path — never use APP_URL/base_url (can point at production).
    return `${window.location.origin}/api/ping`;
};

const logoutUrl = () => `${window.location.origin}/logout`;

export const checkSession = async () => {
    try {
        const response = await fetch(pingUrl(), {
            method: 'GET',
            credentials: 'same-origin',
            redirect: 'manual', // never follow redirects to APP_URL/login (CORS)
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        // Same-origin redirect (e.g. to /login) or opaque redirect — treat as logged out.
        if (response.type === 'opaqueredirect' || response.status === 0 || (response.status >= 300 && response.status < 400)) {
            if (document.querySelector('meta[name="is-impersonating"]')?.getAttribute('content') !== '1') {
                closeSession();
            }
            return;
        }

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (data?.is_alive === false && document.querySelector('meta[name="is-impersonating"]')?.getAttribute('content') !== '1') {
            closeSession();
        }
    } catch {
        // Network blips / extensions — do not force logout.
    }
};

export const closeSession = async () => {
    localStorage.removeItem('api_token');
    localStorage.removeItem('api_token_id');
    window.location.href = logoutUrl();
};

export const createApiToken = () => {
    const { jQuery }: any = window;

    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('token_name', 'api_token');
        formData.append('_token', jQuery('meta[name="csrf-token"]').attr('content'));

        jQuery.ajax({
            url: `${window.location.origin}/tokens/create`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhrFields: { withCredentials: true },
            success: function (response: any) {
                localStorage.setItem('api_token', response.token);
                localStorage.setItem('api_token_id', String(response.id));
                resolve(response);
            },
            error: function (error: any) {
                console.error('Error creating API token:', error);
                reject(error);
            }
        });
    });
};

export const ensureApiTokenForUser = async (expectedUserId: number, forceRefresh = false) => {
    const token = localStorage.getItem('api_token') || '';
    const tokenUserId = localStorage.getItem('api_token_id');

    if (!forceRefresh && token !== '' && tokenUserId === String(expectedUserId)) {
        return;
    }

    localStorage.removeItem('api_token');
    localStorage.removeItem('api_token_id');
    await createApiToken();
};

/** Ensures local API token matches the active logged-in user (e.g. after impersonation). */
export const ensureUserIsAuthenticated = ensureApiTokenForUser;

// 360 seconds = 360000 milliseconds
export const startSessionPolling = (interval: number = 360000) => {
    setInterval(checkSession, interval);
};

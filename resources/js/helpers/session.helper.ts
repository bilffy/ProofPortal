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

/**
 * The CSRF token embedded in the page's <meta name="csrf-token"> can be stale
 * relative to the session by the time this fires (most noticeable right after
 * impersonating, where the token is refreshed more aggressively) - a plain
 * page fetch always reflects the current session's token, so re-reading it
 * from a fresh copy of the current page is a reliable way to resync without a
 * full reload.
 */
const refreshCsrfToken = async (): Promise<string | null> => {
    try {
        const response = await fetch(window.location.href, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'text/html' },
        });
        const html = await response.text();
        const match = html.match(/<meta\s+name="csrf-token"\s+content="([^"]+)"/i);
        if (!match) {
            return null;
        }
        const token = match[1];
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
        return token;
    } catch {
        return null;
    }
};

export const createApiToken = (retryOn419 = true): Promise<any> => {
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
            error: async function (error: any) {
                // 419 = CSRF token mismatch/expired. Resync the token from a
                // fresh copy of the page and retry once before giving up.
                if (retryOn419 && error?.status === 419) {
                    const freshToken = await refreshCsrfToken();
                    if (freshToken) {
                        try {
                            const retryResponse = await createApiToken(false);
                            resolve(retryResponse);
                            return;
                        } catch (retryError) {
                            console.error('Error creating API token (after CSRF retry):', retryError);
                            reject(retryError);
                            return;
                        }
                    }
                }
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

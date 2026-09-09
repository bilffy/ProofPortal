export const checkSession = async () => {
    const { base_url, jQuery }: any = window;

    jQuery.ajax({
        url: `${base_url}/api/ping`,
        method: 'GET',
        xhrFields: { withCredentials: true },
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        dataType: 'json',
        success: function (response: any) {
            if (response?.is_alive === false && document.querySelector('meta[name="is-impersonating"]')?.getAttribute('content') !== '1') {
                closeSession();
            }
        },
        error: function (error: any) {
            console.error('Error checking session:', error);
            // Only force logout when the API explicitly reports the session is dead.
            // A bare 401 (e.g. Sanctum auth mismatch) must not log the user out.
            if (error.status === 401 && error.responseJSON?.is_alive === false) {
                closeSession();
            }
        }
    });
}

export const closeSession = async () => {
    const { base_url }: any = window;

    localStorage.removeItem('api_token');
    localStorage.removeItem('api_token_id');

    // Logout, Redirect to login page
    window.location.href = `${base_url}/logout`;
}

export const createApiToken = () => {
    const { base_url, jQuery }: any = window;

    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('token_name', 'api_token');
        formData.append('_token', jQuery('meta[name="csrf-token"]').attr('content'));

        jQuery.ajax({
            url: `${base_url}/tokens/create`,
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
}

export const ensureApiTokenForUser = async (expectedUserId: number, forceRefresh = false) => {
    const token = localStorage.getItem('api_token') || '';
    const tokenUserId = localStorage.getItem('api_token_id');

    if (!forceRefresh && token !== '' && tokenUserId === String(expectedUserId)) {
        return;
    }

    localStorage.removeItem('api_token');
    localStorage.removeItem('api_token_id');
    await createApiToken();
}

/** Ensures local API token matches the active logged-in user (e.g. after impersonation). */
export const ensureUserIsAuthenticated = ensureApiTokenForUser;

// 360 seconds = 360000 milliseconds
export const startSessionPolling = (interval: number = 360000) => {
    setInterval(checkSession, interval);
}

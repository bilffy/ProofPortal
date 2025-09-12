export const checkSession = async () => {
    const { base_url, jQuery }: any = window;

    jQuery.ajax({
        url: `${base_url}/api/ping`,
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('api_token')}`
        },
        dataType: 'json',
        error: function (error: any) {
            console.error('Error checking session:', error);
            if (error.status === 401 && !error.responseJSON.is_alive) {
                // When session expires, close session
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

export const createApiToken = async () => {
    const { base_url, jQuery }: any = window;

    const formData = new FormData();
    formData.append('token_name', 'api_token');
    formData.append('_token', jQuery('meta[name="csrf-token"]').attr('content'));

    jQuery.ajax({
        url: `${base_url}/tokens/create`,
        method: 'POST',
        data: formData,
        processData: false, // Required for FormData
        contentType: false, // Required for FormData
        success: function (response: any) {
            localStorage.setItem('api_token', response.token);
            localStorage.setItem('api_token_id', response.id);
        },
        error: function (error: any) {
            console.error('Error checking session:', error);
        }
    });
}

// 360 seconds = 360000 milliseconds
export const startSessionPolling = (interval: number = 360000) => {
    setInterval(checkSession, interval);
}

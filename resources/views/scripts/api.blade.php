// Configuration
const config = {
    prefix: "{{ config('glosa.route_prefix', 'glosa') }}",
    csrf: "{{ csrf_token() }}"
};
const MAX_IMPORT_FILE_SIZE = 10 * 1024 * 1024;

// Shared API utilities
const api = {
    async handleResponse(response) {
        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const err = await response.json();
                throw new Error(err.message || `HTTP error! status: ${response.status}`);
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        if (response.status === 204) {
            return null;
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }

        return response.text();
    },
    async get(url) {
        const response = await fetch(`/${config.prefix}${url}`, {
            headers: { 'Accept': 'application/json' }
        });
        return this.handleResponse(response);
    },
    async post(url, body, isMultipart = false) {
        const headers = {
            'X-CSRF-TOKEN': config.csrf,
            'Accept': 'application/json'
        };
        if (!isMultipart) {
            headers['Content-Type'] = 'application/json';
        }

        const res = await fetch(`/${config.prefix}${url}`, {
            method: 'POST',
            headers: headers,
            body: isMultipart ? body : JSON.stringify(body)
        });
        return this.handleResponse(res);
    },
    async put(url, data) {
        const response = await fetch(`/${config.prefix}${url}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        return this.handleResponse(response);
    },
    async delete(url) {
        const response = await fetch(`/${config.prefix}${url}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
                'Accept': 'application/json'
            }
        });
        return this.handleResponse(response);
    }
};

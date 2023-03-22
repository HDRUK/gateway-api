const axios = require('axios');

class HttpClient {
    constructor() {
        this._axios = axios;
    }

    setHttpClientCookies(cookies) {
        return axios.defaults.headers.Cookie = cookies;
    }

    async post(url, body, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'application/json',
            'Content-Type': 'application/json;charset=UTF-8',
        };

        try {
            const response = await this._axios.post(url, body, {
                ...options,
                headers,
            });

            return response;
        } catch (err) {
            process.stdout.write(`HTTPCLIENT - POST : ${err.message}\n`);
            throw new Error(err.message);
        }
    }

    async put(url, body, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'application/json',
            'Content-Type': 'application/json;charset=UTF-8',
        };

        try {
            const response = await this._axios.put(url, body, {
                ...options,
                headers,
            });

            return response;
        } catch (err) {
            process.stdout.write(`HTTPCLIENT - PUT : ${err.message}\n`);
            throw new Error(err.message);
        }
    }

    async delete(url, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'application/json',
            'Content-Type': 'application/json;charset=UTF-8',
        };

        try {
            const response = await this._axios.delete(url, {
                ...options,
                headers,
            });

            return response;
        } catch (err) {
            process.stdout.write(`HTTPCLIENT - DELETE : ${err.message}\n`);
            throw new Error(err.message);
        }
    }
}

module.exports = HttpClient;
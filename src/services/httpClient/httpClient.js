const axios = require('axios');

class HttpClient {
    constructor() {
        this._axios = axios;
    }

    setHttpClientCookies(cookies) {
        axios.defaults.headers.Cookie = cookies;
    }

    async post(url, body, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'applications/json',
            'Content-Type': 'application/json',
        };

        try {
            const response = await this._axios.post(url, body, {
                ...options,
                headers,
            })
            .catch(function (error) {
                if (error.response) {
                  // The request was made and the server responded with a status code
                  // that falls out of the range of 2xx
                  console.log(error.response.data);
                  console.log(error.response.status);
                  console.log(error.response.headers);
                } else if (error.request) {
                  // The request was made but no response was received
                  // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                  // http.ClientRequest in node.js
                  console.log(error.request);
                } else {
                  // Something happened in setting up the request that triggered an Error
                  console.log('Error', error.message);
                }
                console.log(error.config);
            });

            return response;
        } catch (err) {
            console.error(err);
            throw new Error(err.message);
        }
    }

    async put(url, body, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'applications/json',
            'Content-Type': 'application/json',
        };

        try {
            const response = await this._axios.put(url, body, {
                ...options,
                headers,
            })
            .catch(function (error) {
                if (error.response) {
                  // The request was made and the server responded with a status code
                  // that falls out of the range of 2xx
                  console.log(error.response.data);
                  console.log(error.response.status);
                  console.log(error.response.headers);
                } else if (error.request) {
                  // The request was made but no response was received
                  // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                  // http.ClientRequest in node.js
                  console.log(error.request);
                } else {
                  // Something happened in setting up the request that triggered an Error
                  console.log('Error', error.message);
                }
                console.log(error.config);
            });

            return response;
        } catch (err) {
            console.error(err);
            throw new Error(err.message);
        }
    }

    async delete(url, options) {
        const headers = {
            ...(options && options.headers),
            Accept: 'applications/json',
            'Content-Type': 'application/json',
        };

        try {
            const response = await this._axios.delete(url, {
                ...options,
                headers,
            })
            .catch(function (error) {
                if (error.response) {
                  // The request was made and the server responded with a status code
                  // that falls out of the range of 2xx
                  console.log(error.response.data);
                  console.log(error.response.status);
                  console.log(error.response.headers);
                } else if (error.request) {
                  // The request was made but no response was received
                  // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                  // http.ClientRequest in node.js
                  console.log(error.request);
                } else {
                  // Something happened in setting up the request that triggered an Error
                  console.log('Error', error.message);
                }
                console.log(error.config);
            });

            return response;
        } catch (err) {
            console.error(err);
            throw new Error(err.message);
        }
    }
}

module.exports = HttpClient;
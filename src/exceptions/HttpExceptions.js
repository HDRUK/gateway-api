export default class HttpExceptions extends Error {
    constructor(message, statusCode = 500) {
        super(message);
        this.status = statusCode;
    }
}
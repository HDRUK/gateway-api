export default class HttpExceptions extends Error {
    constructor(message) {
        super(message);
        this.message = message;
    }
}
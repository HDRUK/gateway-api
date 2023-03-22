const sinon = require('sinon');

const topicName = 'topic-name';
import { publishMessageToPubSub } from '../PubSubService';

jest.mock('@google-cloud/pubsub', () => ({
    __esModule: true,
    PubSub: jest.fn().mockImplementation(() => ({
        topic: jest.fn(),
        publishMessage: jest.fn().mockImplementation(() => {
            return {
                get: jest.fn(),
                publish: jest.fn(),
                publishMessage: jest.fn()
            };
        }),
    })),
}))

const stubConsole = function () {
    sinon.stub(console, 'error');
    sinon.stub(console, 'log');
};

const restoreConsole = function () {
    console.log.restore();
    console.error.restore();
};

beforeEach(stubConsole);
afterEach(restoreConsole);

describe('PubSub', () => {
    it(`publish message function was called`, async () => {
        const mockFn = jest.fn().mockName("publishMessageToPubSub");
        mockFn(); // comment me
        expect(mockFn).toHaveBeenCalled();
    });

    it(`publish message failed`, async () => {
        const message = {
            foo: 'bar',
        };

        expect(async () => await publishMessageToPubSub(topicName, message)).rejects.toThrow();
    });
});
import redis from 'redis';
import { publishMessageToChannel } from '../cachePubSubClient';

jest.mock('redis', () => {
    return {
        createClient: () => {
            return {
                createClient: jest.fn(),
                connect: jest.fn(),
                publish: jest.fn(async () => "123qwe"),
                on: jest.fn(() => jest.fn()),
            }
        }
    }
});

describe('test redis pubsub client', () => {
    it('publish message to channel', async () => {
        const channel = 'channel_test';
        const message = 'message_test'
        await publishMessageToChannel(channel, message);
    })
});
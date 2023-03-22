// example params in .env file:
// PUBSUB_PROJECT_ID='hdruk-gateway-dev'
// PUBSUB_TOPIC_ENQUIRY='enquiry'
// PUBSUB_SUBSCRIPTION_ID='enquiry-sub'

const {v1} = require('@google-cloud/pubsub');

const publisherClient = new v1.PublisherClient({
    projectId: process.env.PUBSUB_PROJECT_ID,
});

export const publishMessageWithRetryToPubSub = async (topicName, message) => {
    const formattedTopic = publisherClient.projectTopicPath(
        process.env.PUBSUB_PROJECT_ID,
        topicName
    );

    const dataBuffer = Buffer.from(JSON.stringify(message));
    const messagesElement = {
      data: dataBuffer,
    };
    const messages = [messagesElement];

    // Build the request
    const request = {
      topic: formattedTopic,
      messages,
    };

    const initialRetryDelayMillis = parseInt(process.env.PUBSUB_RP_INITIAL_RETRY_DELAY) || 100;
    const retryDelayMultiplier = parseFloat(process.env.PUBSUB_RP_RETRY_DELAY_MULTIPLIER) || 2.0;
    const maxRetryDelayMillis = parseInt(process.env.PUBSUB_RP_MAX_RETRY_DELAY) || 60000;
    const initialRpcTimeoutMillis = parseInt(process.env.PUBSUB_RP_INITIAL_RPC_TIMEOUT) || 5000;
    const rpcTimeoutMultiplier = parseFloat(process.env.PUBSUB_RP_RPC_TIMEOUT_MULTIPLIER) || 2.0;
    const maxRpcTimeoutMillis = parseInt(process.env.PUBSUB_RP_MAX_RPC_TIMEOUT) || 600000;
    const totalTimeoutMillis = parseInt(process.env.PUBSUB_RP_TOTAL_TIMEOUT) || 600000;

    // Retry settings control how the publisher handles retryable failures. Default values are shown.
    // The `retryCodes` array determines which grpc errors will trigger an automatic retry.
    // The `backoffSettings` object lets you specify the behaviour of retries over time.
    const retrySettings = {
        retryCodes: [
            10, // 'ABORTED'
            1, // 'CANCELLED',
            4, // 'DEADLINE_EXCEEDED'
            13, // 'INTERNAL'
            8, // 'RESOURCE_EXHAUSTED'
            14, // 'UNAVAILABLE'
            2, // 'UNKNOWN'
        ],
        backoffSettings: {
            // The initial delay time, in milliseconds, between the completion
            // of the first failed request and the initiation of the first retrying request.
            initialRetryDelayMillis,
            // The multiplier by which to increase the delay time between the completion
            // of failed requests, and the initiation of the subsequent retrying request.
            retryDelayMultiplier,
            // The maximum delay time, in milliseconds, between requests.
            // When this value is reached, retryDelayMultiplier will no longer be used to increase delay time.
            maxRetryDelayMillis,
            // The initial timeout parameter to the request.
            initialRpcTimeoutMillis,
            // The multiplier by which to increase the timeout parameter between failed requests.
            rpcTimeoutMultiplier,
            // The maximum timeout parameter, in milliseconds, for a request. When this value is reached,
            // rpcTimeoutMultiplier will no longer be used to increase the timeout.
            maxRpcTimeoutMillis,
            // The total time, in milliseconds, starting from when the initial request is sent,
            // after which an error will be returned, regardless of the retrying attempts made meanwhile.
            totalTimeoutMillis,
        },
    };
  
    const [response] = await publisherClient.publish(request, {
        retry: retrySettings,
    });
    process.stdout.write(`Message ${response.messageIds} published.`);
}
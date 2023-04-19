// example params in .env file:
// PUBSUB_PROJECT_ID='hdruk-gateway-dev'
// PUBSUB_TOPIC_ENQUIRY='enquiry'
// PUBSUB_SUBSCRIPTION_ID='enquiry-sub'

const {PubSub} = require('@google-cloud/pubsub');

const pubSubClient = new PubSub({
    projectId: process.env.PUBSUB_PROJECT_ID,
});

export const publishMessageToPubSub = async (topicName, message) => {

    const dataBuffer = Buffer.from(JSON.stringify(message));

    try {
        const messageId = pubSubClient.topic(topicName).publishMessage({data: dataBuffer});
        process.stdout.write(`Message ${messageId} published.`);
    } catch (error) {
        process.stdout.write(`publishMessageToPubSub : ${error.message}`);
        throw new Error(`Received error while publishing a message to PubSub`);
    }
};
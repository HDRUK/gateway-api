import { createClient } from 'redis';

// await publishMessageToChannel(process.env.CACHE_CHANNEL, JSON.stringify(pubSubMessage));
export const publishMessageToChannel = async (channel, message) => {
    try {
        const client = createClient({ 
            url: process.env.CACHE_URL
        });

        if (!client.isOpen) {
            await client.connect();
        }
    
        client.on("connect", () => process.stdout.write(`Redis cache is ready`));
        client.on("error", (err) => process.stdout.write(`Redis Client Error : ${err.message}`));
        client.on('ready', () => process.stdout.write(`redis is running`));
    
        await client.publish(channel, message);
    
    } catch (e) {
        process.stdout.write(`Redis Create Client Error : ${e.message}`);
        throw new Error(e.message);
    }
}
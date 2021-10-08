import { PublisherModel } from '../src/resources/publisher/publisher.model';
import { Data as ToolModel } from '../src/resources/tool/data.model';

/**
 * Make any changes you need to make to the database here
 */
async function up () {
   await toolsUpdate();
   await publisherUpdate();
  
}

async function toolsUpdate() {
  const tools = await ToolModel.find({ type: "dataset", "datasetfields.publisher": { $regex: "HUBS" } }).lean(); 
  let tmpTool = [];
  tools.forEach((tool => {  
    const { _id } = tool;
        tmpTool.push({
          updateOne: {
            filter: { _id },
            update: {
              "datasetfields.publisher": replaceHubs(tool.datasetfields.publisher),
              "datasetfields.metadataquality.publisher": replaceHubs(tool.datasetfields.metadataquality.publisher),
              "datasetv2.summary.publisher.memberOf": replaceHubs(tool.datasetv2.summary.publisher.memberOf),
            }
          },
        });
  }));
  await ToolModel.bulkWrite(tmpTool);
}


async function publisherUpdate() {
  const publishers = await PublisherModel.find({ "publisherDetails.memberOf": "HUBS" }).lean();
  let tmpPub = [];
  publishers.forEach((pub => {
		const { _id } = pub;
    tmpPub.push({
			updateOne: {
				filter: { _id },
				update: {
          "publisherDetails.memberOf": replaceHubs(pub.publisherDetails.memberOf),
          "name" : replaceHubs(pub.name),
				}
			},
		});
  }));
  await PublisherModel.bulkWrite(tmpPub);
}


function replaceHubs(input) {
 return input.replace('HUBS','HUB')
}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down () {
  // Write migration here
}

module.exports = { up, down };

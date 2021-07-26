import Repository from '../base/repository';
import { TopicModel } from './topic.model';

export default class TopicRepository extends Repository {
	constructor() {
		super(TopicModel);
		this.topicModel = TopicModel;
	}

	getTopicsForDAR(title, messageType) {
		return TopicModel.find({
			title,
			messageType,
		}).lean();
	}

	getTopicForDAR(title, subTitle, messageType) {
		return TopicModel.findOne({
			title,
			subTitle,
			messageType,
		}).lean();
	}

	createTopicForDAR(title, subTitle, messageType) {
		return TopicModel.create({
			title,
			subTitle,
			createdDate: Date.now(),
			messageType,
		});
	}
}
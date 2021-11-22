import axios from 'axios';
import axiosRetry from 'axios-retry';
import _ from 'lodash';
import { authUtils } from '../../utils';

axiosRetry(axios, {
	retries: 3,
	retryDelay: () => {
		return 3000;
	},
});

const bpmnBaseUrl = process.env.BPMNBASEURL;
//Generate Bearer token for camunda endpoints
const config = {
	headers: { Authorization: `Bearer ${authUtils.camundaToken()}` },
};

module.exports = {
	//Generic Get Task Process Endpoints
	getProcess: async businessKey => {
		return await axios.get(`${bpmnBaseUrl}/engine-rest/task?processInstanceBusinessKey=${businessKey.toString()}`, config);
	},

	//Simple Workflow Endpoints
	postCreateProcess: async bpmContext => {
		// Create Axios requet to start Camunda process
		let { applicationStatus, dateSubmitted, publisher, actioner, businessKey } = bpmContext;
		let data = {
			variables: {
				applicationStatus: {
					value: applicationStatus,
					type: 'String',
				},
				dateSubmitted: {
					value: dateSubmitted,
					type: 'String',
				},
				publisher: {
					value: publisher,
					type: 'String',
				},
				actioner: {
					value: actioner,
					type: 'String',
				},
			},
			businessKey: businessKey.toString(),
		};
		await axios.post(`${bpmnBaseUrl}/engine-rest/process-definition/key/GatewayWorkflowSimple/start`, data, config).catch(err => {
			console.error(err.message);
		});
	},

	postUpdateProcess: async bpmContext => {
		// Create Axios requet to start Camunda process
		let { taskId, applicationStatus, dateSubmitted, publisher, actioner, archived } = bpmContext;
		let data = {
			variables: {
				applicationStatus: {
					value: applicationStatus,
					type: 'String',
				},
				dateSubmitted: {
					value: dateSubmitted,
					type: 'String',
				},
				publisher: {
					value: publisher,
					type: 'String',
				},
				actioner: {
					value: actioner,
					type: 'String',
				},
				archived: {
					value: archived,
					type: 'Boolean',
				},
			},
		};
		await axios.post(`${bpmnBaseUrl}/engine-rest/task/${taskId}/complete`, data, config).catch(err => {
			console.error(err.message);
		});
	},

	//Complex Workflow Endpoints
	postStartPreReview: async bpmContext => {
		//Start pre-review process
		let { applicationStatus, dateSubmitted, publisher, businessKey } = bpmContext;
		let data = {
			variables: {
				applicationStatus: {
					value: applicationStatus,
					type: 'String',
				},
				dateSubmitted: {
					value: dateSubmitted,
					type: 'String',
				},
				publisher: {
					value: publisher,
					type: 'String',
				},
			},
			businessKey: businessKey.toString(),
		};
		await axios.post(`${bpmnBaseUrl}/engine-rest/process-definition/key/GatewayReviewWorkflowComplex/start`, data, config).catch(err => {
			console.error(err.message);
		});
	},

	postStartManagerReview: async bpmContext => {
		// Start manager-review process
		let { applicationStatus, managerId, publisher, notifyManager, taskId } = bpmContext;
		let data = {
			variables: {
				applicationStatus: {
					value: applicationStatus,
					type: 'String',
				},
				userId: {
					value: managerId,
					type: 'String',
				},
				publisher: {
					value: publisher,
					type: 'String',
				},
				notifyManager: {
					value: notifyManager,
					type: 'String',
				},
			},
		};
		await axios.post(`${bpmnBaseUrl}/engine-rest/task/${taskId}/complete`, data, config).catch(err => {
			console.error(err.message);
		});
	},

	postManagerApproval: async bpmContext => {
		// Manager has approved sectoin
		let { businessKey } = bpmContext;
		await axios.post(`${bpmnBaseUrl}/api/gateway/workflow/v1/manager/completed/${businessKey}`, bpmContext.config).catch(err => {
			console.error(err.message);
		});
	},

	postStartStepReview: async bpmContext => {
		//Start Step-Review process
		let { businessKey } = bpmContext;
		await axios.post(`${bpmnBaseUrl}/api/gateway/workflow/v1/complete/review/${businessKey}`, bpmContext, config).catch(err => {
			console.error(err.message);
		});
	},

	postCompleteReview: async bpmContext => {
		//Start Next-Step process
		let { businessKey } = bpmContext;
		await axios.post(`${bpmnBaseUrl}/api/gateway/workflow/v1/reviewer/complete/${businessKey}`, bpmContext, config).catch(err => {
			console.error(err.message);
		});
	},
};

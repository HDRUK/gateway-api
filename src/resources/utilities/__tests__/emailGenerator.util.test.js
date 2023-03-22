import emailGenerator from '../emailGenerator.util';

describe('Email generator utility functions', () => {
	describe('_generateMetadataOnboardingRejected', () => {
		let isFederated;

		it('SHOULD include federated warning if isFederated is true', async () => {
			isFederated = true;

			const emailBody = emailGenerator.generateMetadataOnboardingRejected({ isFederated });

			// Federated warning should be present if dataset is from a federated publisher
			expect(emailBody.includes('Do not apply these changes directly to the Gateway')).toBe(true);
		});

		it('SHOULD NOT include federated warning if isFederated is false', async () => {
			isFederated = false;

			const emailBody = emailGenerator.generateMetadataOnboardingRejected({ isFederated });

			// Federated warning should not be present if dataset is not from a federated publisher
			expect(emailBody.includes('Do not apply these changes directly to the Gateway')).toBe(false);
		});
	});
});

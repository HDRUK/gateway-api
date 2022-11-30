import { Data } from '../../tool/data.model';
import { UserModel } from '../../user/user.model';
import emailGenerator from '../../utilities/emailGenerator.util';

export const sendEmailNotifications = async (review, activeflag) => {
    const tool = await Data.findOne({ id: review.toolID });
    const reviewer = await UserModel.findOne({ id: review.reviewerID });
    const toolLink = process.env.homeURL + '/tool/' + tool.id;
    const hdrukEmail = process.env.HDRUK_ENQUIRY_EMAIL || `enquiry@healthdatagateway.org`;

    const statement = UserModel.aggregate([
        { $match: { $or: [{ role: 'Admin' }, { id: { $in: tool.authors } }] } },
        {
            $lookup: {
                from: 'tools',
                localField: 'id',
                foreignField: 'id',
                as: 'tool',
            },
        },
        { $match: { 'tool.emailNotifications': true } },
        {
            $project: {
                _id: 1,
                firstname: 1,
                lastname: 1,
                email: 1,
                role: 1,
                'tool.emailNotifications': 1,
            },
        },
    ]);

    statement.exec((err, emailRecipients) => {
		if (err) {
			return new Error({ success: false, error: err });
		}

		let subject;
		if (activeflag === 'active') {
			subject = `A review has been added to the ${tool.type} ${tool.name}`;
		} else if (activeflag === 'rejected') {
			subject = `A review on the ${tool.type} ${tool.name} has been rejected`;
		} else if (activeflag === 'archive') {
			subject = `A review on the ${tool.type} ${tool.name} has been archived`;
		}

		let html = `<div>
						<div style="border: 1px solid #d0d3d4; border-radius: 15px; width: 700px; margin: 0 auto;">
							<table
							align="center"
							border="0"
							cellpadding="0"
							cellspacing="40"
							width="700"
							word-break="break-all"
							style="font-family: Arial, sans-serif">
								<thead>
									<tr>
										<th style="border: 0; color: #29235c; font-size: 22px; text-align: left;">
											${subject}
										</th>
										</tr>
										<tr>
										<th style="border: 0; font-size: 14px; font-weight: normal; color: #333333; text-align: left;">
											<p>
												${
													activeflag === 'active'
														? `${reviewer.firstname} ${reviewer.lastname} gave a ${review.rating}-star review to the tool ${tool.name}.`
														: activeflag === 'rejected'
														? `A ${review.rating}-star review from ${reviewer.firstname} ${reviewer.lastname} on the ${tool.type} ${tool.name} has been rejected.`
														: activeflag === 'archive'
														? `A ${review.rating}-star review from ${reviewer.firstname} ${reviewer.lastname} on the ${tool.type} ${tool.name} has been archived.`
														: ``
												}	
											</p>
										</th>
									</tr>
								</thead>
								<tbody style="overflow-y: auto; overflow-x: hidden;">
									<tr style="width: 100%; text-align: left;">
										<td style=" font-size: 14px; color: #3c3c3b; padding: 5px 5px; width: 50%; text-align: left; vertical-align: top;">
											<a href=${toolLink}>View ${tool.type}</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>`;

		emailGenerator.sendEmail(emailRecipients, `${hdrukEmail}`, subject, html, false);
	});
}
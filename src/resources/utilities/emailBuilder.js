const setMessageProperties = (emailRecipientType, body, user) => {

  const {
    researchAim,
    linkedDataSets,
    namesOfDataSets,
    dataRequirements,
    dataSetParts,
    startDate,
    icoRegistration,
    researchBenefits,
    ethicalProcessingEvidence,
    contactNumber,
    title,
    custodianEmail
  } = body;
  const hdrukEmail = `enquiry@healthdatagateway.org`;
  const dataCustodianEmail = process.env.DATA_CUSTODIAN_EMAIL || custodianEmail; 

  const msg = {
    from: `${hdrukEmail}`,
    subject: `Enquires for ${title} dataset healthdatagateway.org`,
    html: `
            An enquiry to access the ${title} dataset has been made. Please see the details of the enquiry below:<br /><br /><br />
            ${researchAim ? `<strong>Research Aim</strong>: ${researchAim} <br /><br />` : ''}
            ${linkedDataSets ? `<strong>Linked Datasets</strong>: ${namesOfDataSets} <br /><br />` : ''}
            ${dataRequirements ? `<strong>Data Field Requirements</strong>: ${dataSetParts}<br /><br />` : ''}
            ${startDate ? `<strong>Start date</strong>: ${startDate}<br /><br />` : ''}
            ${icoRegistration ? `<strong>ICO Registration number</strong>: ${icoRegistration}<br /><br />` : ''}
            ${researchBenefits ? `<strong>Research benefits</strong>: ${researchBenefits}<br /><br />` : ''}
            ${ethicalProcessingEvidence ? `<strong>Ethical processing evidence</strong>: ${ethicalProcessingEvidence}<br /><br />` : ''}
            ${contactNumber ? `<strong>Contact number</strong>: ${contactNumber}<br /><br />` : ''}
            <strong>Email: ${user.email}</strong><br /><br />
            The person requesting the data is: ${user.firstname} ${user.lastname}`
  };

  if (emailRecipientType === 'requester') {
    msg.to = user.email;
    msg.html = `Thank you for enquiring about access to the ${title} dataset through the 
        Health Data Research UK Innovation Gateway. The Data Custodian for this dataset 
        has been notified and they will contact you directly in due course.<br /><br />
        In order to facilitate the next stage of the request process, please make yourself 
        aware of the technical data terminology used by the NHS Data Dictionary 
        on the following link: <a href="https://www.datadictionary.nhs.uk/">https://www.datadictionary.nhs.uk/</a><br /><br />
        Please reply to this email, if you would like to provide feedback to the 
        Data Enquiry process facilitated by the Health Data Research 
        Innovation Gateway - <a href="mailto:support@healthdatagateway.org">support@healthdatagateway.org(opens in new tab)</a>
        <br></br>
        Please see a copy of the message that the Data Custodian will receive:
        <br></br>` + msg.html;
  }
  else if (emailRecipientType === 'dataCustodian') {
    msg.to = `${dataCustodianEmail}`
  }
  return msg;
};

module.exports.setMessageProperties = setMessageProperties;
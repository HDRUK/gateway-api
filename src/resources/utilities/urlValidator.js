const validateURL = (link) => {
    if (link && !/^https?:\/\//i.test(link)) {
      link = 'https://' + link;
    }
    return link;
  }

  const validateOrcidURL = (link) => {
    if (!/^https?:\/\/orcid.org\//i.test(link)) {
      link = 'https://orcid.org/' + link;
    }
    return link;
  }

  module.exports = {
      validateURL: validateURL,
      validateOrcidURL: validateOrcidURL 
  }
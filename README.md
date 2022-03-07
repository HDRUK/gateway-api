[![LICENCE](https://img.shields.io/github/license/HDRUK/gateway-api)](https://github.com/HDRUK/gateway-api/blob/master/LICENSE)
[![Support](https://img.shields.io/badge/Supported%20By-HDR%20UK-blue)](https://hdruk.ac.uk)

# HDR UK GATEWAY - API Server (Express)

This is a NodeJS Express server, which provides the Back End API Server to the Gateway. It negotiates with a back-end datasbase and metdata catalogues to allow users to perform user interactions via the Gateway Front End [gateway-web](https://github.com/HDRUK/gateway-web)

### Installation / Running Instructions

To set up the API on your local do the following steps

#### Step 1

Clone the API repository.

`git clone https://github.com/HDRUK/gateway-api`

#### Step 2

Run the npm install

```
npm install
```

#### Step 3

Create a .env file in the root of the project with this content:

```
# MongoDB connection parameters
user=
password=
cluster=
database=

GATEWAY_WEB_URL=http://localhost:3000

# Auth parameters
GOOGLE_OAUTH_ID=
GOOGLE_OAUTH_SECRET=
JWTSecret=
AUTH_PROVIDER_URI=
openidClientID=
openidClientSecret=
linkedinClientID=
linkedinClientSecret=

# Sendgrid API Key
SENDGRID_API_KEY=

# Datacustodian email address used for testing data access request locally, place with your email!
DATA_CUSTODIAN_EMAIL=your@email.com

# Discourse integration
DISCOURSE_API_KEY=
DISCOURSE_URL=
DISCOURSE_CATEGORY_TOOLS_ID=
DISCOURSE_CATEGORY_PROJECTS_ID=
DISCOURSE_CATEGORY_DATASETS_ID=
DISCOURSE_CATEGORY_PAPERS_ID=
DISCOURSE_SSO_SECRET=

```

#### Step 4

Start the API via command line.

`node server.js`

### Running using Google Cloud Run

Replace **MY-PROJECT** with the name of your project or use an alternative docker registry.

#### Create the docker image

from the root directory run:

```
docker build -t  gcr.io/MY-PROJECT/hdruk-gateway-api:latest .
docker push -t  gcr.io/MY-PROJECT/hdruk-gateway-api:latest .
```

This will create a docker image in the Google Cloud Container registry with tag latest.

#### Deploy to cloud run manually

The following is an example of using gcloud command to deploy the docker image to cloud run, please change region and other parameters as required.

```
gcloud run deploy latest-api --image gcr.io/MY-PROJECT/hdruk-gateway-api:latest --platform managed --region europe-west1 --allow-unauthenticated
```

#### Deploy using terraform

Create a config file (vars.tfvars) with the following parameters (values must be updated as required):

```
project_id="gcp-project-name"
environment="latest"
bucket_name="gcp-project-name"
region="europe-west1"
cloud_run_region="europe-west1"

dns_domain="mydomain.here"

database_name="latest"
database_cluster="cluster-XXXX.gcp.mongodb.net"
database_username="latest"

google_client_id="GOOGLE_OAUTH_ID_here"
google_client_secret="googlesecrethere"
jwt_secret="jwtscrethere"
home_url="https://uat.mydomain.here"

auth_provider_uri="https://connect.openathens.net"
openid_client_id="idhere"
openid_client_secret="secrethere"

linkedin_client_id="idhere"
linkedin_client_secret="secrethere"

sendgrid_api_key="apikeyhere"

data_custodian_email="support@mydomain.here"

api_release_version="gcr.io/gcp-project-name/hdruk-rdt-api:final"
web_release_version="gcr.io/gcp-project-name/hdruk-rdt-web:final"

mongodbatlas_public_key="pubkeyhere"
mongodbatlas_private_key="privatekeyhere"
mongodbatlas_project_id="projectidhere"

discourse_api_key="keyhere"
discourse_url="https://discourse.mydomain.here"
discourse_category_tools_id="12345"
discourse_category_projects_id="123456"
discourse_sso_secret="secrethere"

ga_view_id="123456789"
ga_client_email="gaemailhere"
ga_private_key="keyhere"
```

Initialise, plan and run the terraform build (please copy the below terrform to your terraform installation directory).

```
terraform init -var-file=vars.tfvars
terraform plan -var-file=vars.tfvars -out=tf_apply
terraform apply tf_apply && rm tf_apply
```

[Link to terraform file](deployment/GCP/api.tf)

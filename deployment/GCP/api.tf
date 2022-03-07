variable "cloud_run_region" {
  type      = string
}

variable "dns_domain" {
  type      = string
}

variable "api_release_version" {
  type      = string
  default   = "gcr.io/hdruk-gateway/hdruk-gateway:latest"
}

variable "database_name" {
  type      = string
  default   = "HDRUKRDT"
}

variable "database_cluster" {
  type      = string
  default   = "cluster0-cenin.gcp.mongodb.net"
}

variable "database_username" {
  type      = string
  default   = "first-user"
}

variable "metadata_username" {
  type      = string
  default   = ""
}

variable "metadata_password" {
  type      = string
  default   = ""
}

variable "google_client_id" {
  type      = string
}

variable "google_client_secret" {
  type      = string
}


variable "jwt_secret" {
  type      = string
}

variable "home_url" {
  type      = string
}

variable "auth_provider_uri" {
  type      = string
}

variable "openid_client_id" {
  type      = string
}

variable "openid_client_secret" {
  type      = string
}

variable "linkedin_client_id" {
  type      = string
}

variable "linkedin_client_secret" {
  type      = string
}

variable "sendgrid_api_key" {
  type      = string
}

variable "discourse_api_key" {
  type      = string
}

variable "discourse_url" {
  type      = string
}

variable "discourse_category_tools_id" {
  type      = string
}

variable "discourse_category_projects_id" {
  type      = string
}


variable "discourse_sso_secret" {
  type      = string
}

variable "data_custodian_email" {
  type      = string
}

variable "metadata_quality_url" {
  type      = string
  default   = "https://europe-west1-hdruk-gateway.cloudfunctions.net/metadataqualityscore"
}

variable "metadata_url" {
  type      = string
  default   = "https://metadata-catalogue.org/hdruk"
}

variable "ga_view_id" {
  type      = string
  default   = ""
}

variable "ga_client_email" {
  type      = string
  default   = ""
}

variable "ga_private_key" {
  type      = string
  default   = ""
}

resource "google_cloud_run_service" "api" {
  name     = "${var.environment}-api"
  location = var.cloud_run_region

  autogenerate_revision_name = true

  template {
    metadata {
        annotations = {
            "autoscaling.knative.dev/maxScale" = "10"
        }
        labels      = {}
    }
    spec {
        container_concurrency = 80
        containers {
            image = var.api_release_version
            resources {
                limits   = {
                    cpu    = "1000m"
                    memory = "256M"
                }
                requests = {}
            }
            env {
                name = "user"
                value = var.database_username
            }
            env {
                name = "password"
                value = mongodbatlas_database_user.user.password
            }
            env {
                name = "cluster"
                value = var.database_cluster
            }
            env {
                name = "database"
                value = var.database_name
            }
            env {
                name = "metadataUsername"
                value = var.metadata_username
            }
            env {
                name = "metadataPassword"
                value = var.metadata_password
            }
            env {
                name = "GOOGLE_OAUTH_ID"
                value = var.google_client_id
            }
            env {
                name = "GOOGLE_OAUTH_SECRET"
                value = var.google_client_secret
            }
            env {
                name = "JWTSecret"
                value = var.jwt_secret
            }
            env {
                name = "homeURL"
                value = var.home_url
            }
            env {
                name = "AUTH_PROVIDER_URI"
                value = var.auth_provider_uri
            }
            env {
                name = "openidClientID"
                value = var.openid_client_id
            }
            env {
                name = "openidClientSecret"
                value = var.openid_client_secret
            }
            env {
                name = "linkedinClientID"
                value = var.linkedin_client_id
            }
            env {
                name = "linkedinClientSecret"
                value = var.linkedin_client_secret
            }
            env {
                name = "SENDGRID_API_KEY"
                value = var.sendgrid_api_key
            }
            env {
                name = "DISCOURSE_API_KEY"
                value = var.discourse_api_key
            }
            env {
                name = "DISCOURSE_URL"
                value = var.discourse_url
            }
            env {
                name = "DISCOURSE_CATEGORY_TOOLS_ID"
                value = var.discourse_category_tools_id
            }
            env {
                name = "DISCOURSE_CATEGORY_PROJECTS_ID"
                value = var.discourse_category_projects_id
            }
            env {
                name = "DISCOURSE_SSO_SECRET"
                value = var.discourse_sso_secret
            }
            env {
                name = "DATA_CUSTODIAN_EMAIL"
                value = var.data_custodian_email
            }
            env {
                name = "metadataURL"
                value = var.metadata_url
            }           
            env {
                name = "metadataQualityURL"
                value = var.metadata_quality_url
            }
            env {
                name = "GA_VIEW_ID"
                value = var.ga_view_id
            }
            env {
                name = "GA_CLIENT_EMAIL"
                value = var.ga_client_email
            }
            env {
                name = "GA_PRIVATE_KEY"
                value = var.ga_private_key
            }

        } 
    }
  }

  traffic {
    percent         = 100
    latest_revision = true
  }
}

data "google_iam_policy" "noauth" {
  binding {
    role = "roles/run.invoker"
    members = [
      "allUsers",
    ]
  }
}

resource "google_cloud_run_service_iam_policy" "noauth" {
  location    = google_cloud_run_service.api.location
  project     = google_cloud_run_service.api.project
  service     = google_cloud_run_service.api.name

  policy_data = data.google_iam_policy.noauth.policy_data
}


# Experiments (in-app feedback)

Super admins create feedback prompts and design reviews that gateway-web shows on targeted pages. Each response is raised as a Helpdesk ticket in Jira Service Management. The Gateway does not store responses, only a count per experiment. The prompt UI and builder are the `@hdruk/experiments` package, used by gateway-web. gateway-web's `docs/FEATURES/feedback-experiments.md` covers the front end.

## Endpoints

| Method and path                                                   | Who                | Purpose                                                                                       |
| ----------------------------------------------------------------- | ------------------ | --------------------------------------------------------------------------------------------- |
| `GET/POST /v1/experiments`, `GET/PUT/DELETE /v1/experiments/{id}` | `hdruk.superadmin` | Manage experiments                                                                            |
| `GET /v1/experiments/active`                                      | Anyone             | Experiments running for this visitor                                                          |
| `GET /v1/experiments/active/me`                                   | Signed in          | As above, for audiences that need a user                                                      |
| `POST /v1/experiments/{id}/responses`                             | Anyone             | Send answers, with `contact` and `email` to opt in                                            |
| `POST /v1/experiments/{id}/responses/me`                          | Signed in          | Send answers. `contact: true` uses the account's preferred email; any `email` sent is ignored |

An experiment runs while its `status` is `active` and today falls between `starts_at` and `ends_at`. `targeting` controls who sees it:

-   `audience`: `all`, `logged_in`, `logged_out`, or `user_ids`. `logged_out` is only shown on `/active`, since signed-in users call `/active/me`.
-   `percentage`: assignment is stable per user, or per `x-request-session-id` when signed out (`crc32` bucket).

`trigger.on` is `load`, `delay` or `exitIntent`. The active endpoints also return `endsAt` (`Y-m-d` or null), so the web can expire its dismissal cookie when the experiment ends.

Only answers to question blocks (`rating`, `choice`, `textInput`, `abChoice`) are sent on:

-   A submission with none of those is rejected with 400.
-   A signed-out `contact: true` without a valid `email` is rejected with 400.
-   Each accepted response adds 1 to `experiments.responses_count`, shown in the admin list. Nothing else about it is written to the database.
-   There is no server-side check for repeat responses. The web's cookie stops the prompt reappearing.

`starts_at` and `ends_at` must be `Y-m-d`.

These routes sit behind the `api` group's global rate limit only. A per-route throttle would key on the load balancer's IP (`TrustProxies` trusts no proxies), so every anonymous visitor would share one bucket.

## Code

-   `ExperimentController`: CRUD, `active()`/`activeForMe()` and `respond()`/`respondForMe()`. The `/me` methods exist so each route has its own OpenAPI entry. Validation is in `app/Http/Requests/Experiment`, logic is in the services below.
-   `App\Services\Experiments\ExperimentService`: creates and updates experiments, lists active ones, and checks and submits responses. A user's roles are only looked up when a signed-in user responds, to set the ticket's User Type.
-   `App\Services\Experiments\ExperimentTargeting`: static audience and percentage checks.
-   `App\Services\JiraServiceDesk`: JSM configuration check and the create-request call.
-   `App\Models\Experiment`: status, audience, trigger, frequency and block type constants, the `running` scope and `knownAnswers()`.

OpenAPI schemas: `Experiment` (model), `ExperimentInput` (`CreateExperiment`), `ExperimentResponseInput` (`CreateExperimentResponse`) and `ActiveExperiment` (`ExperimentService`).

## Images

Image and design-review blocks upload through the shared upload endpoint with `POST /v1/files?entity_flag=experiments-media`:

-   Only super admins can upload: `is_admin`, or the `hdruk.superadmin` role. Anyone else gets 401.
-   `ScanFileUpload` checks that the file decodes as an image and copies it to `/experiments/` on the media disk. It does not use `validateImage`, which enforces the 600×300 banner ratio.
-   `GET /v1/files/{uuid}` returns a `url` (`MEDIA_URL` + the file location) once the upload is `PROCESSED`. The builder stores that URL in the block.

## Helpdesk tickets

`ExperimentService::submit()` queues `CreateServiceDeskRequest` straight away, with the submission (answers, page, contact email and so on) as its only data. The job calls `JiraServiceDesk` to create a **Gateway Enquiry** request (type 78) on the **Feedback Log** desk (7):

-   Summary: `Feedback: {name}`.
-   Description: the page, the contact email or "not requested", whether they were signed in, and every answer, labelled. A design choice also links the chosen option's image and prototype. The user ID is only included when they opted in to contact.
-   Initial Enquiry Type: Share Feedback.
-   "Happy to be asked your opinion on future developments?": always No. Opting in to contact about this feedback is not consent to that.
-   User Type: Data Custodian if the user has any `custodian.*` role, User if they are signed in, otherwise unset.

The job implements `ShouldBeEncrypted`, so the submission is encrypted while it waits in the queue and in `failed_jobs`. Once the ticket exists, Jira holds the only copy. For signed-in users, the account's preferred email is read when they submit.

A 4xx from JSM, such as a bad field ID, fails the job without retrying. A 5xx or a timeout is retried, so in the rare case JSM created the ticket but the reply was lost, a retry can raise a duplicate. Neither keeps JSM's reply body, which could echo the email, so the logged error and `failed_jobs` hold only the status code. `failed()` logs the experiment ID and that error. If the experiment was deleted before the job ran, a warning is logged and no ticket is raised.

If the Helpdesk is not configured, responses are counted, the answers are discarded and a warning is logged with the experiment ID.

Field and option IDs are in `config/services.php` under `jsm.fields`. `JSM_BASE_URL` defaults to `https://api.atlassian.com/ex/jira`. Each environment sets `JSM_EMAIL`, `JSM_API_TOKEN` and `JSM_CLOUD_ID`. `JSM_SERVICE_DESK_ID` (7) and `JSM_REQUEST_TYPE_ID` (78) default to the desk and request type the field IDs belong to; override them only together with `jsm.fields`:

-   The token belongs to an Atlassian service account, scoped `read:servicedesk-request` and `write:servicedesk-request`.
-   Calls go to `{JSM_BASE_URL}/{cloudId}/rest/servicedeskapi/request`.
-   If any of these values is missing, no job is queued.

`phpunit.xml` blanks `JSM_API_TOKEN`, so tests can never reach Atlassian.

**Any environment with these set creates real tickets**, including local development with a worker on the `default` queue. The local `gateway` stack only runs the `high` worker.

## Tests

-   `tests/Feature/ExperimentTest.php`: management, targeting and response validation.
-   `tests/Feature/ServiceDeskRequestTest.php`: contact opt-in, job dispatch and the request payload, with HTTP faked.
-   `tests/Unit/Services/Experiments/ExperimentTargetingTest.php`: audience and percentage rules.
-   `tests/Feature/UploadTest.php` (`test_experiment_image_*`): image upload, rejection of non-images and the super admin check.

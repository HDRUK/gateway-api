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

-   `audience`: `all`, `logged_in`, `logged_out`, or `user_ids`. `logged_out` is only shown on `/active`, since signed-in users call `/active/me`. `user_ids` are stored as integers, even when sent as numeric strings.
-   `percentage`: assignment is stable per user, or per `x-request-session-id` when signed out (`crc32` bucket).

`trigger.on` is `load`, `delay` or `exitIntent`. The active endpoints also return `endsAt` (`Y-m-d` or null), so the web can expire its dismissal cookie when the experiment ends.

Only answers to question blocks (`rating`, `choice`, `textInput`, `abChoice`) are sent on:

-   A submission with none of those is rejected with 400.
-   A signed-out `contact: true` without a valid `email` is rejected with 400.
-   A response without a `recaptcha` token that Google accepts for the `experiment_response` action, with a high enough score, is rejected with 400 and is not counted.
-   Each accepted response adds 1 to `experiments.responses_count`, shown in the admin list. Nothing else about it is written to the database.
-   There is no server-side check for repeat responses. The web's cookie stops the prompt reappearing.

`starts_at` and `ends_at` must be `Y-m-d`. Deleting an experiment soft-deletes it; `GET`, `PUT` and `DELETE` on a deleted ID return 400.

The public endpoints are behind the `POC_FeedbackExperiments` Pennant flag (`ExperimentService::FEATURE_FLAG`), off by default. While it is off, `/active` and `/active/me` return an empty list, and both `/responses` routes return 404.

## Flood protection

Each response raises a Jira ticket, so `/responses` has two protections against floods. The API stores nothing about the visitor; reCAPTCHA runs Google's script in the browser.

-   **reCAPTCHA v3.** When someone submits, gateway-web asks Google's invisible reCAPTCHA v3 for a token (action `experiment_response`) and sends it as `recaptcha`. `ExperimentService::verifyRecaptcha()` checks it with Google's siteverify API (`App\Services\ReCaptcha`) and accepts it only if it succeeded, is for that action, and scores at least `RECAPTCHA_MIN_SCORE` (default 0.5; 1.0 is very likely human, 0.0 very likely a bot). Google rejects a reused or expired token (tokens are single-use and last 2 minutes). `RECAPTCHA_SECRET_KEY` must be set in every environment. Without it, with `RECAPTCHA_MIN_SCORE` at 0 or blank, if Google can't be reached or returns an error, or if Google rejects the secret key, responses are rejected and a warning is logged. When Google rejects a token (a low score, the wrong action, or a reused, expired or invalid token), Google's reason (success, error codes, score, action and hostname, never the token) is logged at debug level only, so a bot can't flood the warning logs; set `LOG_LEVEL=debug` to see it. Google's public test keys are for v2 and return no action or score, so local development needs a real v3 key pair registered for `localhost`.
-   **Ticket cap.** Each experiment raises at most `ExperimentService::TICKETS_PER_HOUR` (50) Helpdesk tickets an hour. Past that, responses are still counted and get a 201, but the answers are discarded and a warning is logged with the experiment ID. The count is a cache counter keyed by experiment ID that resets an hour after its first hit. It uses the default cache store, so with more than one API pod on the `file` store each pod counts separately: treat 50 as a soft limit.

A per-visitor throttle would not work: `TrustProxies` trusts no proxies, so every visitor shares the ingress IP, and a bot can change its `x-request-session-id` on every request. Otherwise these routes sit behind the `api` group's global rate limit only.

## Code

-   `ExperimentController`: CRUD, `active()`/`activeForMe()` and `respond()`/`respondForMe()`. The `/me` methods exist so each route has its own OpenAPI entry. It reads the JWT user and `x-request-session-id` into an `ExperimentVisitor`, and turns the service's `InvalidArgumentException` into a 400. Validation is in `app/Http/Requests/Experiment`, logic is in the services below.
-   `App\Services\Experiments\ExperimentService`: creates and updates experiments, lists active ones, and checks and submits responses. It takes no `Request`. A user's roles are only looked up when a signed-in user responds, to set the ticket's User Type.
-   `App\Services\Experiments\ExperimentVisitor`: the user ID and session ID of the visitor, and the `subject` used for percentage bucketing.
-   `App\Services\Experiments\ExperimentSubmission`: the typed submission that `submit()` passes to `CreateServiceDeskRequest`.
-   `App\Services\ReCaptcha`: checks a reCAPTCHA v3 token, its action and its score with Google's siteverify API.
-   `App\Services\Experiments\ExperimentTargeting`: static audience and percentage checks.
-   `User::preferredEmailAddress()`: the contact email for a signed-in opt-in. It falls back to the primary email when no secondary is set.
-   `App\Services\JiraServiceDesk`: JSM configuration check and the create-request call.
-   `App\Enums\ExperimentStatus`: `draft`, `active`, `paused`, `archived`. The column is a string and the model casts it to this enum, so adding a status needs no migration.
-   `App\Models\Experiment`: audience, trigger, frequency and block type constants, the `running` scope and `knownAnswers()`.

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

If the Helpdesk is not configured, or the experiment has reached its hourly ticket limit, responses are counted, the answers are discarded and a warning is logged with the experiment ID.

Field and option IDs are in `config/services.php` under `jsm.fields`, each overridable from env and defaulting to the Feedback Log values: `JSM_FIELD_ENQUIRY_TYPE`, `JSM_OPTION_SHARE_FEEDBACK`, `JSM_FIELD_FUTURE_CONTACT`, `JSM_OPTION_FUTURE_CONTACT_NO`, `JSM_FIELD_USER_TYPE`, `JSM_OPTION_USER_TYPE_USER` and `JSM_OPTION_USER_TYPE_DATA_CUSTODIAN`. `JSM_BASE_URL` defaults to `https://api.atlassian.com/ex/jira`. Each environment sets `JSM_EMAIL`, `JSM_API_TOKEN` and `JSM_CLOUD_ID`. `JSM_SERVICE_DESK_ID` (7) and `JSM_REQUEST_TYPE_ID` (78) default to the desk and request type the field IDs belong to; override them only together with `jsm.fields`:

-   The token belongs to an Atlassian service account, scoped `read:servicedesk-request` and `write:servicedesk-request`.
-   Calls go to `{JSM_BASE_URL}/{cloudId}/rest/servicedeskapi/request`.
-   If any of these values is missing, no job is queued.

`phpunit.xml` blanks `JSM_API_TOKEN`, and `ServiceDeskRequestTest` fakes the JSM URL and calls `Http::preventStrayRequests()`, so tests can never reach Atlassian.

**Any environment with these set creates real tickets**, including local development with a worker on the `default` queue. The local `gateway` stack only runs the `high` worker.

## Tests

-   `tests/Feature/ExperimentTest.php`: management, targeting and response validation.
-   `tests/Feature/ServiceDeskRequestTest.php`: contact opt-in, job dispatch and the request payload, with HTTP faked.
-   `tests/Unit/Services/Experiments/ExperimentTargetingTest.php`: audience and percentage rules.
-   `tests/Feature/UploadTest.php` (`test_experiment_image_*`): image upload, rejection of non-images and the super admin check.

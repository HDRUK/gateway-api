# Partner context on team dataset writes

Every dataset is stored with a partner context (`datasets.partner_context`). It decides who can read the dataset and which template the web renders it with.

`PartnerContext::getPartner()` resolves a request's partner context from the `x-partner-context` header, falling back to `config('partners.default')`.

| Endpoint | Stores the request partner context |
|---|---|
| `POST /api/v2/teams/{id}/datasets` | always |
| `PUT /api/v2/teams/{id}/datasets/{id}` | while `SchemaOnboardingFormSelector` is on (`PartnerContext::selectedPartner()`); otherwise unchanged |

The web onboarding form sends the header with the partner schema chosen in its picker, so switching a dataset's schema on edit switches its partner context. Requests without the header store the deployment default.

Turn `SchemaOnboardingFormSelector` on only together with `SchemaOnboardingForm`: the legacy web form saves through the same update endpoint without the header.

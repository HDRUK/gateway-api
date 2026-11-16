# Partner context on team dataset writes

Every dataset is stored with a partner context (`datasets.partner_context`). It decides who can read the dataset and which template the web renders it with.

`PartnerContext::getPartner()` resolves a request's partner context from the `x-partner-context` header, falling back to `config('partners.default')`. `PartnerContext::requestedPartner()` returns the header only, or null when it is not sent.

| Endpoint | Partner context stored |
|---|---|
| `POST /api/v2/teams/{id}/datasets` | `getPartner()`: the header, else the deployment default |
| `PUT /api/v2/teams/{id}/datasets/{id}` | the header when sent; unchanged when not |

On update, a header that is not a known partner context (`HDRUK` or a key of `PartnerContext::CROSS_CONTEXT_FLAGS`, see `PartnerContext::isKnown()`) is rejected with a 400 and the stored partner context is left unchanged.

The web onboarding form sends the header with the schema chosen in its picker (`HDRUK` for non-partner schemas), so switching a dataset's schema on edit switches its partner context. Clients that don't send the header, such as the legacy web form, never change it.

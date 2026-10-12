## Screenshots (if relevant)

_N/A — no visual change; this fixes a silent JS error in the submit path._

## Describe your changes

Fixes the **"Save as draft"** button silently doing nothing on the dataset onboarding / form-hydration flow.

**Symptom:** Clicking *Save as draft* fired no network request and gave no error — the button appeared dead. Console showed:

```
Uncaught (in promise) TypeError: Cannot read properties of undefined (reading 'origin')
```

**Root cause:** In `src/utils/formHydration.tsx`, `mapFormFieldsForSubmission()` builds `formattedFormData` dynamically by `set()`-ing only the fields the user actually filled in. It then assigned `formattedFormData.provenance.origin.datasetType = …` directly — assuming `provenance.origin` already existed. When the provenance section was left empty (typical for a partial draft), `formattedFormData.provenance` was `undefined`, so reading `.origin` threw. Because this runs inside `postForm` *before* the request is built, the throw escaped as an unhandled promise rejection, so no request was ever sent and nothing surfaced to the user.

**Fix:** Replaced the raw nested assignment with the same lodash `set()` helper already used a few lines above, which safely creates the `provenance.origin` path when absent, and guarded the source array against being missing:

```js
set(
    formattedFormData,
    "provenance.origin.datasetType",
    (formData["Dataset Type Array"] ?? []).map(item => ({
        name: item["Dataset type"],
        subTypes: item["Dataset subtypes"] ?? [],
    }))
);
```

Drafts with an empty provenance section now save correctly, and fully-populated datasets still produce the same `provenance.origin.datasetType` payload.

**Notes:**

- Frontend-only; the API (`gateway-api-2`) was never reached, so no backend/GwdmHandler changes are involved.
- Reproduces on `dev` and production — pre-existing bug, not a regression from recent work.

## Issue ticket link

GAT-XXXX _(fill in — branch is `fix/onboarding-form`)_

## Checklist before requesting a review

- [x] I have performed a self-review of my code
- [ ] I have added appropriate unit tests
- [ ] I have created mocks for unit tests (where appropriate)
- [x] The interface is responsive (where appropriate) _— N/A, no UI change_
- [x] The interface is at least AA (where appropriate) _— N/A, no UI change_

# Dataset pipeline

The dataset pipeline is the one way dataset metadata is saved and read back, through `DatasetService`. It decides how a save becomes a version, and how versions are stored and rebuilt.

## Who decides what

`DatasetService` holds the domain rules and never looks at who is calling, so a job or a command can do anything the domain allows. Access rules belong to the caller, which decides what it passes in.

A dataset's team is fixed when it is created: saving a dataset takes the team from the dataset itself and can't change it. Moving a dataset between teams is a separate, deliberate action, not a side effect of a save. Fields that only some people may change, such as cohort discovery membership, are accepted by the service from anyone who passes them, and leaving them out means "no change"; it is the caller's job to drop them for people who aren't allowed to set them.

## Versions

A dataset's latest version is a working copy until it is published. A version is a published state, and a draft is a working copy on its way to becoming the next version.

Saving a dataset whose latest version has never been published replaces that version and keeps its number, so the first publish doesn't add a version. Saving a dataset whose latest version has been published adds the next version, published or not depending on the save. A published version never changes. Moving a published dataset to draft, editing it and republishing it therefore produces exactly one new version.

A version's `active_date` records when it was first published; a version that has never been published has none. Version history only shows published versions, so a working copy can't be listed or read through it.

## How versions are stored

Each version is stored either as a full copy of its metadata or as a diff (an RFC 6902 JSON Patch) against the version before it. A version is a full copy if it is the first version, every tenth version, or its GWDM version differs from the previous version's (`SnapshotRule`). Reading a version starts from the nearest full copy at or below it and applies the diffs after it, so a read applies at most nine diffs, and a diff never spans a change of GWDM version.

Replacing a working copy is safe because only the latest version can be replaced, so nothing depends on it yet. Everything about version N is worked out against version N-1: whether N is a full copy or a diff, and if a diff, the patch from N-1's content. Replacing updates the existing row rather than inserting one, so anything that refers to that version keeps pointing at it.

Before replacing, the service locks the version and checks again that it is still unpublished. If another save published it in the meantime, the save is refused rather than overwriting a published version.

## Not guaranteed yet

The pipeline only protects published versions on its own write path. Some older write paths still overwrite the latest version in place whether or not it is published, and rows they wrote may not follow the full-copy rule. Until every save goes through the pipeline, published versions aren't protected at the model level, and reads don't check that diffs stay within one GWDM version.

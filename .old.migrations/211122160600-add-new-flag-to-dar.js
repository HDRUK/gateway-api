import { DataRequest } from '../src/resources/datarequest/datarequest.model';

/**
 * Make any changes you need to make to the database here
 */
async function up() {
    // Write migration here

    /**
     * Update DAR to include an overriding published field to determine the published
     * state of a DAR edit form publication by a custodian
     */
    await DataRequest.updateMany(
        {
            $set: { "data_requests.published_form": false }
        }
    );

}

/**
 * Make any changes that UNDO the up function side effects here (if possible)
 */
async function down() {
	// Write migration here

    await DataRequest.updateMany(
        {
            $unset: { "data_requests.published_form": false }
        }
    );
}

module.exports = { up, down };

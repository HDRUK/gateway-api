module.exports = {
  async up(db, client) {
    // TODO write your migration here.
    // See https://github.com/seppevs/migrate-mongo/#creating-a-new-migration-script

    /**
     * Update DAR to include an overriding published field to determine the published
     * state of a DAR edit form publication by a custodian
     */
    // await db.collection('data_requests').updateMany({
    //   $set: { "published_form": false },
    // }); 

    await db.collection('data_requests').updateMany({},
      {
        $set: { "publishedForm": false }
      }
    );
  },

  async down(db, client) {
    // TODO write the statements to rollback your migration (if possible)

    await db.collection('data_requests').updateMany({},
      {
        $unset: { "publishedForm": false }
      }
    );
  }
};

const {BigQuery} = require('@google-cloud/bigquery');

export default class BigQueryService {
    _bigquery;
    _query;
    _table;
    // query;
    constructor() {
        this._table = `${process.env.BIG_QUERY_PROJECT_ID}.${process.env.BIG_QUERY_DATABASE}.${process.env.BIG_QUERY_TABLE}`;
        this._bigquery = new BigQuery({
            projectId: process.env.BIG_QUERY_PROJECT_ID,
        });
    }

    setQuery(statement) {
        this._query = statement;
    }

    async query() {
        const options = {
            query: this._query,
            location: process.env.BIG_QUERY_LOCATION || 'US',
        };

        // Run the query as a job
        const [job] = await this._bigquery.createQueryJob(options);

        try {
            // Wait for the query to finish
            const [rows] = await job.getQueryResults();

            return rows;
        } catch (err) {
            throw new Error(err.message);
        }
    }
}
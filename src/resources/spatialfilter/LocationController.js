import { normalizeOutput } from '../../help/index';
import HttpExceptions from '../../exceptions/HttpExceptions';

const BaseController = require('./BaseController');

class LocationController extends BaseController {
    constructor() {
        super();
    }

    async getData(req, res) {
        const { filter } = req.params;
        const table = `${process.env.BIG_QUERY_PROJECT_ID}.${process.env.BIG_QUERY_DATABASE}.${process.env.BIG_QUERY_TABLE}`;
        console.log(table);
        const statement = `SELECT name, country, level_one, level_two, level_three
                            FROM \`${table}\`
                            WHERE lower(\`name\`) LIKE '%${filter.toLowerCase()}%'
                            ORDER BY \`name\`
                            LIMIT ${process.env.BIG_QUERY_LIMIT_ROWS}`;

        try {
            this._bigQuery.setQuery(statement);
            const returnBigQuery = await this._bigQuery.query();

            this._logger.sendDataInLogging(
                {
                    filter: filter,
                    data: returnBigQuery.length,
                },
                'INFO',
            );

            res.setHeader('Content-Type', 'application/json');
            res.status(200).end(
                JSON.stringify(
                    {
                        success: true,
                        message: `List of Locations`,
                        data: normalizeOutput(returnBigQuery),
                    },
                    null,
                    3,
                ),
            );
        } catch (err) {
            throw new HttpExceptions(err.message);
        }
    }
}

module.exports = new LocationController();


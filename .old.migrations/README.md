# HDR UK GATEWAY - Data Migrations

The primary data source used by the Gateway Project is the noSQL solution provided by MongoDb.  Data migration strategy is a fundamental part of software development and release cycles for a data intensive web application.  The project team have chosen the NPM package Migrate-Mongoose - https://www.npmjs.com/package/migrate-mongoose to assist in the management of data migration scripts.  This package allows developers to write versioned, reversible data migration scripts using the Mongoose library.

For more information on what migration scripts are and their purpose, please see sample background reading here - https://www.red-gate.com/simple-talk/sql/database-administration/using-migration-scripts-in-database-deployments/

### Using migration scrips

To create a data migration script, follow these steps:

#### Step 1

Ensure your terminal's working directory is the Gateway API and that node packages have been installed using 'npm i'.

#### Step 2

Run the command below, replacing 'my_new_migration_script' with the name of the script you want to create.  The name does not need to be unique, as it will be prefixed automatically with a timestamp, but it should be easily recognisable and relate strongly to the database change that will take place if the script is executed.

./node_modules/.bin/migrate create my_new_migration_script

#### Step 3

Your new migration scripts should now be available in './migrations/', which you can now modify.  You can import the required Mongoose models as normal to interact with the MongoDb database.  The migration scripts that run locally will use the connection string taken from your .env file against the variable 'MIGRATE_dbConnectionUri'.

Complete the scripts required for the UP process, and if possible, the DOWN process.  For awareness, the UP scripts run automatically as part of our CI/CD pipeline, and the DOWN scripts exist to reverse database changes if necessary, this is a manual process.

#### Step 4

With the scripts written, the functions can be tested by running the following command, replacing 'my_new_migration_script' with the name of the script you want to execute without the time stamp so for example 
node -r esm migrations/migrate.js up add_globals 

node -r esm migrations/migrate.js up my_new_migration_script

When this process is completed, the connected database will have a new document representing your migration scripts inside the 'migrations' collection, which tracks the state of the migration.  If you need to run your scripts multiple times for test purposes, you can change the state of the migration to 'Down'.  

During this process, please ensure you are using a personal database.

#### Step 5

Commit the code to the relevant git branch and raise a pull request.  The migration script will run automatically as the code moves through each environment.

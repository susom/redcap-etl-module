REDCap-ETL Configuration
========================

REDCap-ETL requires a configuration file. In addition, a REDCap
configuration project can also be used. Using a configuration project
allows users who do not have access to the REDCap-ETL server to set
some configuration properties and to start the ETL process.
However, the configuration project has been deprecated, and the
plan is to replace its functionality with a REDCap external module.

If a configuration project is used, then REDCap-ETL will use the
configuration file to locate the configuration project, so in this case
the file needs to contain:

* the URL for your REDCap API
* the API token for your REDCap configuration project

The 3 main things that need to be specified in the configuration
are:

1. The data source - where the data is extracted from.
2. The transformation rules - how to transform the data in the data source
to the target for the data load.
3. The database - where the extracted and transformed data is loaded

Several properties can be specified in both the configuration file
and configuration project. For these properties, a non-blank value in the 
configuration project will replace the value in the configuration file
(which is read first).


Data Source
-----------------------------------
The data source is specified as the REDCap API token for the data project,
i.e., the REDCap project that has the data to be extracted.


Database
-----------------------------------

The database where the data is loaded is specified as a
database connection string. This string has the following format:

        <database-connection-type> : <database-connection-values>

Currently, the supported database connection types are

* **MySQL**
* **CSV** (comma-separated values).


### MySQL
For MySQL, the format of the database connection string is:

        MySQL:<host>:<username>:<password>:<database>[:<port>]

Example MySQL database connection strings:

        MySQL:localhost:etl_user:etl_password:etl_test_db

        MySQL:someplace.edu:admin:admin_password_123:etl_prod_db:3306

**Note:** Since the ':' character is used as a separator for the database
connection string, if any of the values
in your database connection contain a ':', it needs to be escaped with a blackslash.
For example, if your password is "my:password", then it would need to be specified
as "my\:password".


### CSV
For CSV, the database connection string format is:

        CSV:<output-directory>

For example:

        CSV:/home/redcap-etl/csv/project1


Transformation Rules
---------------------------------------------------
The transformation rules specify how the records in REDCap should be transformed into records in your database. 


There are 3 choices for the source of the rules:

1. __text__ - rules are entered in a text box in the configuration project (not 
available for file-only configuration)
2. __file__ - the rules are stored in a file:
    * on the REDCap-ETL server, for file-based configuration
    * uploaded to the REDCap configuration project, for project-based configuration
3. __auto-generated__ - simple rules (one table per form) are automatically
generated by REDCap-ETL. Note that at this point, auto-generated rules are
intended for testing that an installation has been set up correctly, or to generate
an initial set of rules that are then modified.
The auto-generated rules will currently only produce good results for very simple cases.

To auto-generate rules so that they can be modified, use the following script:

    bin/transformation_rules_generator.php

The script uses a REDCap-ETL configuration file to
generate rules.
For example, from the top-level directory of the installation, you
could use the following command to generate rules for the
config/test.ini configuration file:

    php bin/transformation_rules_generator.php config/test.ini > config/test-rules.txt


Details about the transformation rules can be found here:
[Transformation Rules Guide](TransformationRulesGuide.md)


REDCap-ETL Configuration Properties
--------------------------------------
This section provides a detailed list of the configuration properties. The property names
here are the ones that are used in the configuration file and set in the configuration project.

### REDCap Connection Properties

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>redcap_api_url</td>
<td> X </td> <td> &nbsp; </td>
<td>The URL for your REDCap API. Typically this will be your REDCap URL
with "/api/" appended to the end.
Not ending the URL with a slash (/) may cause an error.</td> 
</tr>

<tr>
<td>ssl_verify</td>
<td> X </td> <td> &nbsp; </td>
<td>Indicates if SSL verification is used for the connection to REDCap.
This defaults to true. Setting it to false is insecure.</td> 
</tr>

<tr>
<td>ca_cert_file</td>
<td> X </td> <td> &nbsp; </td>
<td>Certificate authority certificate file. This can be used to support
SSL verification of the connection to REDCap if your system does not
provide support for it by default</td>
</tr>

</tbody>
</table>


### REDCap Project Properties

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>config_api_token</td>
<td> X </td> <td> &nbsp; </td>
<td>The REDCap API token for the (optional, and now deprecated) configuration project</td>
</tr>

<tr>
<td>data_source_api_token</td>
<td> X </td> <td> X </td>
<td>The API token for the REDCap project from which the data
is being extracted from REDCap.</td>
</tr>

<tr>
<td>log_project_api_token</td>
<td> X </td> <td> X </td>
<td>REDCap API token of (optional, and now deprecated) logging project</td>
</tr>
</tbody>
</table>

<br />


### Database Properties

Properties for the database where the extracted data is loaded.

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>db_connection</td>
<td> X </td> <td> X </td>
<td>The database connection string for the database where the data
is loaded.</td>
</tr>

<tr>
<td>db_ssl</td>
<td> X </td> <td> </td>
<td>Flag that indicates if SSL should be used for MySQL database accesses
(true by default). Note: on Linux systems, having this set to true
may cause the database connection to fail when the database host is specified
as "localhost". To fix this problem, set db_ssl to 'false' (or 0), or specify
"127.0.0.1" for the database host instead of "localhost".</td>
</tr>

<tr>
<td>db_ssl_verify</td>
<td> X </td> <td> </td>
<td>Flag that indicates if the SSL certificate of the database
server should be verified. For this to work, a valid ca_cert_file
(certificate authority certificate file)
needs to be specified.
</td>
</tr>

</tbody>
</table>

### Database Logging Properties

REDCap-ETL logs to the load database by default. It creates and logs to 2 tables:

1. **etl_log** - contains one row for each ETL process run, where each row contains
    the start time, table prefix used (if any), batch size, and REDCap-ETL
    version number. 
2. **etl_event_log** - contains the individual event messages for each
    ETL process run.

The 2 tables can be joined on their **log_id** attributes.

Unlike the other tables REDCap-ETL generates,
the database logging tables are not deleted between runs, so these tables
accumulate the results of all ETL runs.

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>db_logging</td>
<td> X </td> <td> </td>
<td>A true/false property indicating if REDCap-ETL should log to the database.
The default value for this property is true. Database logging is not
supported for CSV (comma-separated value) file output.</td>
</tr>

<tr>
<td>db_log_table</td>
<td> X </td> <td> </td>
<td>The name of the main database logging table.
This name defaults to **etl_log**.</td>
</tr>

<tr>
<td>db_event_log_table</td>
<td> X </td> <td> </td>
<td>The name of the database logging event table.
This name defaults to **etl_event_log**.</td>
</tr>

</tbody>
</table>


### E-mail Properties

Properties for e-mail error notifications and processing summaries
that can be sent by REDCap-ETL.

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>email_errors</td>
<td> X </td> <td> </td>
<td>True/false value that indicates if an e-mail should be sent
when errors occur to the "email_to_list".
The default value is true.
</td>
</tr>

<tr>
<td>email_summary</td>
<td> X </td> <td> </td>
<td>True/false value that indicates if an e-mail summary of the
log messages should be sent to the "email_to_list".
The default value is false, and no summary will be sent
if the ETL process encounters an error.
</td>
</tr>

<tr>
<td>email_from_address</td>
<td> X </td> <td> </td>
<td>The from address for e-mail notifications sent by REDCap-ETL</td>
</tr>


<tr>
<td>email_subject</td>
<td> X </td> <td> </td>
<td>The subject for e-mail notifications sent by REDCap-ETL</td>
</tr>

<tr>
<td>email_to_list</td>
<td> X </td> <td> X </td>
<td>The to address list for e-mail notifications sent by REDCap-ETL</td>
</tr>


</tbody>
</table>

### Post-Processing Properties

For the MySQL database, REDCap-ETL supports a user-specified SQL file that will
be executed after the ETL process has completed. You can put SQL commands in here
that can add new tables or views, change the types of columns, set a 
column to a calculated value, etc. 

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>post_processing_sql_file</td>
<td> X </td> <td> &nbsp; </td>
<td>File with SQL statements to execute on the database after the ETL process
has finished. This can be used, for example, to create indexes on tables
generated by the ETL process. This feature is intended for running commands
that update the database, and select commands will generate no output.
Note that if a table prefix is specified for the ETL process, that prefix
will  NOT be automatically added to table names in the post-processing SQL file.
In addition, post-processing SQL is not supported for CSV files.
</td>
</tr>


</tbody>
</table>

### Generated Fields Type Properties

REDCap-ETL generates several fields automatically. The generated field type properties
can be used to modify the types of these fields.

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>generated_instance_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for the redcap_repeat_instance field that is generated by REDCap-ETL
when a REPEATING_INSTRUMENTS rows type is used for a table</td>
</tr>


<tr>
<td>generated_key_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for the primary and foreign keys generated by REDCap-ETL</td>
</tr>


<tr>
<td>generated_label_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for the label fields generated by REDCap-ETL
for the label views of tables</td>
</tr>

<tr>
<td>generated_name_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for name fields for events and instruments generated
by REDCap-ETL (the redcap_event_name and redcap_repeat_instrument
fields)</td>
</tr>

<tr>
<td>generated_record_id_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for the REDCap record ID fields
generated for each table by REDCap-ETL</td>
</tr>

<tr>
<td>generated_suffix_type</td>
<td> X </td> <td> &nbsp; </td>
<td>The type used for the suffix fields 
generated by REDCap-ETL when a suffixes rows type is specified</td>
</tr>


</tbody>
</table>


### Lookup Table Properties

REDCap-ETL uses a "Lookup" table internally to map REDCap's multiple choice
values to their corresponding labels. This internal table can be saved to your
database.

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>create_lookup_table</td>
<td> X </td> <td> &nbsp; </td>
<td>A true/false property that indicates whether or not a lookup table
should be created in the database.
</tr>

<tr>
<td>lookup_table_name</td>
<td> X </td> <td> &nbsp; </td>
<td>The name to use for the lookup table created in the database. The default table
name is "Lookup".</td>
</tr>

</tbody>
</table>






### Other Properties

<table>
<thead>
<tr> <th>Property</th> <th>File</th> <th>Project</th> <th>Description</th> </tr>
</thead>
<tbody>

<tr>
<td>calc_field_ignore_pattern</td>
<td> X </td> <td> &nbsp; </td>
<td>A pattern represented as a PHP regular expression that will be used in
determining what calculation fields values are considered when
evaluating REDCap data rows for inclusion in a table. By default, if a calculation field
is included in a table, then any REDCap data records which have a non-blank value for
that calculation field will be included in the table. If, however, you specified the
pattern '/^0$/' for this property, then REDCap-ETL would ignore calculation fields that
are blank or zero when determining what REDCap data records should be
included in the table.
</tr>

<tr>
<td>extracted_record_count_check</td>
<td> X </td> <td> &nbsp; </td>
<td>A true/false property that indicates whether or not a check
should be done to make sure that the
number of records extracted from REDCap is the number expected. 
This is done to catch cases where REDCap's API does not export all the records
that it should due to an internal error, but does not notify REDCap-ETL of the error.
By default this check is turned on, but you might need to turn it off if
records could be deleted or added when the ETL process runs, which could
also cause the counts not to match.
</tr>

<tr>
<td>log_file</td>
<td> X </td> <td> &nbsp; </td>
<td>File to use for logging</td>
</tr>



<tr>
<td>time_limit</td>
<td> X </td> <td> &nbsp; </td>
<td>
Maximum number of seconds the ETL process is allowed to run.
A value of zero (the default value) means there is no limit.
</td> 
</tr>

<tr>
<td>time_zone</td>
<td> X </td> <td> &nbsp; </td>
<td>
Timezone to use; see the URL below for valid values:
https://secure.php.net/manual/en/timezones.php
By default, the default PHP timezone on the system is used.
</td> 
</tr>


<tr>
<td>web_script</td>
<td> X </td> <td> &nbsp; </td>
<td>The file name to use for the web script that will be
generated by the web script installation process.
The file name should normally end with ".php".
This web script
is used to handle REDCap DETs (Data Entry Triggers), so if you are not using DETs,
you can leave this blank.
DETs are used to allow the ETL process to be started from REDCap, so that users
who do not have access to the REDCap-ETL server can run the ETL process.
</td> 
</tr>

<tr>
<td>web_script_log_file</td>
<td> X </td> <td> &nbsp; </td>
<td>The (optional) log file to use for the web script.</td> 
</tr>

<tr>
<td>web_script_url</td>
<td> X </td> <td> &nbsp; </td>
<td>This property is only used for the automated tests, so
is is not needed for normal ETL processing. It is used
to indicate to the tests the URL of the web script, for example:
http://localhost/visits.php</td> 
</tr>

</tbody>
</table>

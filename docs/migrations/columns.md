## Columns

### Remapping types in adapters
There are several types in Phoenix, but not all adapters allows all types. This table shows how types are implemented in adapters.

| Type          | MySQL      | PostgreSQL    |
|---------------|------------|---------------|
| tinyinteger   | tinyint    | int2          |
| smallinteger  | smallint   | int2          |
| mediuminteger | mediumint  | int4          |
| integer       | int        | int4          |
| biginteger    | bigint     | int8          |
| numeric       | decimal    | numeric       |
| decimal       | decimal    | numeric       |
| float         | float      | float4        |
| double        | double     | float8        |
| binary        | binary     | bytea         |
| varbinary     | varbinary  | bytea         |
| char          | char       | char          |
| string        | varchar    | varchar       |
| boolean       | tinyint(1) | bool          |
| bit           | bit        | bit           |
| date          | date       | date          |
| time          | time       | time          |
| datetime      | datetime   | timestamp(6)  |
| timestamp     | timestamp  | timestamp(6)  |
| year          | year       | numeric(4)    |
| tinytext      | tinytext   | text          |
| mediumtext    | mediumtext | text          |
| text          | text       | text          |
| longtext      | longtext   | text          |
| tinyblob      | tinyblob   | bytea         |
| mediumblob    | mediumblob | bytea         |
| blob          | blob       | bytea         |
| longblob      | longblob   | bytea         |
| uuid          | char(36)   | uuid          |
| json          | text/json* | json          |
| enum          | enum       | USER-DEFINED  |
| set           | set        | ARRAY         |
| point         | point      | point         |
| line          | linestring | line          |
| polygon       | polygon    | polygon       |

* Depends on version of MySQL (text for < 5.7.8, json for >= 5.7.8)

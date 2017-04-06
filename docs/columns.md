## Columns

### Remapping types in adapters
There are several types in Phoenix, but not all adapters allows all types. This table shows how types are implemented in adapters.

| Type          | MySQL      | PostgreSQL    | SQLite        |
|---------------|------------|---------------|---------------|
| tinyinteger   | tinyint    | int2          | tinyinteger   |
| smallinteger  | smallint   | int2          | smallinteger  |
| mediuminteger | mediumint  | int4          | mediuminteger |
| integer       | int        | int4          | integer       |
| biginteger    | bigint     | int8          | bigint        |
| numeric       | decimal    | numeric       | decimal       |
| decimal       | decimal    | numeric       | decimal       |
| float         | float      | float4        | float         |
| double        | double     | float8        | double        |
| binary        | binary     | bytea         | binary        |
| varbinary     | varbinary  | bytea         | varbinary     |
| char          | char       | char          | char          |
| string        | varchar    | varchar       | varchar       |
| boolean       | tinyint(1) | bool          | boolean       |
| date          | date       | date          | date          |
| datetime      | datetime   | timestamp(6)  | datetime      |
| tinytext      | tinytext   | text          | tinytext      |
| mediumtext    | mediumtext | text          | mediumtext    |
| text          | text       | text          | text          |
| longtext      | longtext   | text          | longtext      |
| tinyblob      | tinyblob   | bytea         | tinyblob      |
| mediumblob    | mediumblob | bytea         | mediumblob    |
| blob          | blob       | bytea         | blob          |
| longblob      | longblob   | bytea         | longblob      |
| uuid          | char(36)   | uuid          | char(36)      |
| json          | text       | json          | text          |
| enum          | enum       | USER-DEFINED  | enum          |
| set           | set        | ARRAY         | enum          |
| point         | point      | point         | point         |
| line          | linestring | line          | varchar(255)  |
| polygon       | polygon    | polygon       | text          |

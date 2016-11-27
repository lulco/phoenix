## Columns

| Type       | MySQL      | PostgreSQL    | SQLite   |
|------------|------------|---------------|----------|
| string     | varchar    | varchar       | varchar  |
| integer    | int        | int4          | integer  |
| boolean    | tinyint(1) | bool          | boolean  |
| text       | text       | text          | text     |
| char       | char       | char          | char     |
| float      | float      | real          | float    |
| decimal    | decimal    | decimal       | decimal  |
| date       | date       | date          | date     |
| datetime   | datetime   | timestamp(6)  | datetime |
| biginteger | bigint     | int8          | bigint   |
| uuid       | char(36)   | uuid          | char(36) |
| json       | text       | json          | text     |
| enum       | enum       | custom type   | enum     |
| set        | set        | custom type[] | enum     |
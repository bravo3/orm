Key Schemes
===========
A key scheme is the scheme by which document keys are generated. By implementing custom key schemes you can take control
of the "folder" or key structure inside the database. Each [driver](Driver.md) has a preferred key scheme which best
suits the style of database. For example, an S3 driver might want to mimic a folder structure, where a Redis driver will
want to use the popular convention of using a colon between key parts.

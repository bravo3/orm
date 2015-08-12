Database Drivers
================
The database drivers are responsible for the low-level communication with the database. Noting that databases should
typically be document databases (although you could use a relational database server) the entity manager will to some
degree organise the indices so that the database itself only needs to worry about sets and documents.

If the document database does not support sets, the driver will need additional logic to maintain a set as the entity
manager expects sets to be supported.

Redis Driver
------------
The Redis driver requires you to add Predis to your project, using Composer add to your `require` clause:
 
    "predis/predis": "~1.0"
    
Filesystem Driver
-----------------
The filesystem driver allows you to use the local filesystem as a database, storing all objects as files in a given
directory. This driver uses a I/O sub-driver allowing you to use the filesystem driver to write to any key/value store,
including tar/zip files.

When using the filesystem driver it is important to use the `FilesystemKeyScheme` (even when using tar/zip archives) -
changing the key scheme will result in potentially dangerous activity.

Bundled are 2 I/O sub-drivers for the filesystem driver:

### Native Filesystem Sub-Driver
Creates all database objects as literal files on the filesystem, this is quick but creates a lot of individual files:

    $em = EntityManager::build(
        new FilesystemDriver(new NativeIoDriver('/tmp/my_database')), 
        new AnnotationMapper()
    );

### Phar Sub-Driver
The Phar driver uses PHP's native PharData class to create tar or zip databases, useful for creating a single backup
file:

    $em = EntityManager::build(
        new FilesystemDriver(new PharIoDriver('/tmp/my_database.zip', ArchiveType::ZIP())), 
        new AnnotationMapper()
    );
    
You can compress tar databases, however compression isn't supported by PharData on zip archives, so when using a zip
database, you cannot provide a compression method.

    $em = EntityManager::build(
        new FilesystemDriver(new PharIoDriver('/tmp/my_database.tar', ArchiveType::TAR(), Compression::BZIP2())), 
        new AnnotationMapper()
    );
    
Bzip2 and Gzip compression is supported, for each you require the `bzip2` or `zlib` extension to be enabled 
respectively. Compression is applied on the drivers destructor, making the compression process the last thing that
happens when PHP is in its shutdown sequence (or the EntityManager is no longer referenced).

#### CAUTION WITH TAR DATABASES
When using tar files there is a 255 character limit on key names. Under general use this should not be a problem, 
however long table names or entity ID's could trip this limit.

#### CAUTION WITH ALL PHAR DATABASES
Due to a quirk in the PharData class, you require a file name extension on your database file. This extension can be 
anything and does not need to represent the archive type, but the file name must have an extension.

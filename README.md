#PHPDbManager

PHPDbManager (PDM) is designed to propagate database changes to other members of team. The PDM works with delta revisions of database schema and this revision files are stored in project's CMS (e.g. Git, Mercurial, e.t.c.).
PDM is written in pure PHP and supports all databases supported by PDO adapter.

##Installation

Create local copy of PDM repository by

    git clone https://github.com/elmordo/PhpDbManager.git

and run `make` command in PDM root directory. `make` merge source files and output file is stored in `build` directory. Copy file `pdm.php` to your project where DB revision will be stored.
Then run

    php pdm.php project init

to create configuration file `pdm.json`. This configuration file have to be added into CMS ignored files (DO NOT SHARE THIS FILE WITH YOUR TEAM!). 
Edit configuration file and setup DB connection params.

Now its ready to use :)

##Usage

To print help, run `php pdm.php`. Output of this command is list of operation groups:

* `project` - project settings
* `revision` - operations with revisions
* `db` - operations with database

###`project` command

Only supported action is `init`. This action is described above.

###`revision`

Revision command group supports following two actions:

* `create` - create new revision
* `rescan` - scan directory for unmanaged revisions (created by other members of team and synced by CMS)

When revision is created, three files are added into directory:

* `revision_name`.json contains internal information about revision. Do not change this file!
* `revision_name`_up.sql contains SQL code for update to upper version of DB
* `revision_name`_down.sql contains SQL code for revert changes

###`db` command

Command group `db` provides actions to change database itself:

* `update` action apply unapplied changes to database
* `revert` action revert DB schema to specified revision (not implemented yet)
* `reapply` run revert file of current revision and apply it again


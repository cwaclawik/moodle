<?php  //$Id$

require_once($CFG->libdir.'/dml/moodle_database.php');
require_once($CFG->libdir.'/dml/pgsql_native_moodle_recordset.php');

/**
 * Native pgsql class representing moodle database interface.
 * @package dml
 */
class pgsql_native_moodle_database extends moodle_database {

    protected $pgsql     = null;
    protected $bytea_oid = null;

    protected $last_debug;

    /**
     * Detects if all needed PHP stuff installed.
     * Note: can be used before connect()
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('pgsql')) {
            return get_string('pgsqlextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    /**
     * Returns database family type - describes SQL dialect
     * Note: can be used before connect()
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'postgres';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysql, pgsql, postgres7
     */
    protected function get_dbtype() {
        return 'pgsql';
    }

    /**
     * Returns general database library name
     * Note: can be used before connect()
     * @return string db type adodb, pdo, native
     */
    protected function get_dblibrary() {
        return 'native';
    }

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('nativepgsql', 'install'); // TODO: localise
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('nativepgsqlhelp', 'install');
    }

    /**
     * Returns localised database description
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_hints() {
        return get_string('databasesettingssub_postgres7', 'install'); // TODO: improve
    }

    /**
     * Connect to db
     * Must be called before other methods.
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param mixed $prefix string means moodle db prefix, false used for external databases where prefix not used
     * @param array $dboptions driver specific options
     * @return bool true
     * @throws dml_connection_exception if error
     */
    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        if ($prefix == '' and !$this->external) {
            //Enforce prefixes for everybody but mysql
            throw new dml_exception('prefixcannotbeempty', $this->get_dbfamily());
        }

        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        $this->store_settings($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);

        $pass = addcslashes($this->dbpass, "'\\");

        // Unix socket connections should have lower overhead
        if (!empty($this->dboptions['dbsocket']) and ($this->dbhost === 'localhost' or $this->dbhost === '127.0.0.1')) {
            $connection = "user='$this->dbuser' password='$pass' dbname='$this->dbname'";
        } else {
            $this->dboptions['dbsocket'] = 0;
            if (empty($this->dbname)) {
                // probably old style socket connection - do not add port
                $port = "";
            } else if (empty($this->dboptions['dbport'])) {
                $port = "port ='5432'";
            } else {
                $port = "port ='".$this->dboptions['dbport']."'";
            }
            $connection = "host='$this->dbhost' $port user='$this->dbuser' password='$pass' dbname='$this->dbname'";
        }

        ob_start();
        if (empty($this->dboptions['dbpersit'])) {
            $this->pgsql = pg_connect($connection, PGSQL_CONNECT_FORCE_NEW);
        } else {
            $this->pgsql = pg_pconnect($connection, PGSQL_CONNECT_FORCE_NEW);
        }
        $dberr = ob_get_contents();
        ob_end_clean();
        
        $status = pg_connection_status($this->pgsql);

        if ($status === false or $status === PGSQL_CONNECTION_BAD) {
            $this->pgsql = null;
            throw new dml_connection_exception($dberr);
        }

        $this->query_start("--pg_set_client_encoding()", null, SQL_QUERY_AUX);
        pg_set_client_encoding($this->pgsql, 'utf8');
        $this->query_end(true);

        // find out the bytea oid
        $sql = "SELECT oid FROM pg_type WHERE typname = 'bytea'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        $this->bytea_oid = pg_fetch_result($result, 0);
        pg_free_result($result);
        if ($this->bytea_oid === false) {
            $this->pgsql = null;
            throw new dml_connection_exception('Can not read bytea type.');
        }

        return true;
    }

    /**
     * Close database connection and release all resources
     * and memory (especially circular memory references).
     * Do NOT use connect() again, create a new instance if needed.
     */
    public function dispose() {
        if ($this->pgsql) {
            pg_close($this->pgsql);
            $this->pgsql = null;
        }
        parent::dispose();
    }


    /**
     * Called before each db query.
     * @param string $sql
     * @param array array of parameters
     * @param int $type type of query
     * @param mixed $extrainfo driver specific extra information
     * @return void
     */
    protected function query_start($sql, array $params=null, $type, $extrainfo=null) {
        parent::query_start($sql, $params, $type, $extrainfo);
        // pgsql driver tents to send debug to output, we do not need that ;-)
        $this->last_debug = error_reporting(0);
    }

    /**
     * Called immediately after each db query.
     * @param mixed db specific result
     * @return void
     */
    protected function query_end($result) {
        //reset original debug level
        error_reporting($this->last_debug);
        parent::query_end($result);
    }

    /**
     * Returns database server info array
     * @return array
     */
    public function get_server_info() {
        static $info;
        if (!$info) {
            $this->query_start("--pg_version()", null, SQL_QUERY_AUX);
            $info = pg_version($this->pgsql);
            $this->query_end(true);
        }
        return array('description'=>$info['server'], 'version'=>$info['server']);
    }

    protected function is_min_version($version) {
        $server = $this->get_server_info();
        $server = $server['version'];
        return version_compare($server, $version, '>=');
    }

    /**
     * Returns supported query parameter types
     * @return bitmask
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_DOLLAR;
    }

    /**
     * Returns last error reported by database engine.
     */
    public function get_last_error() {
        return pg_last_error($this->pgsql);
    }

    /**
     * Return tables in database WITHOUT current prefix
     * @return array of table names in lowercase and without prefix
     */
    public function get_tables($usecache=true) {
        if ($usecache and $this->tables !== null) {
            return $this->tables;
        }
        $this->tables = array();
        $prefix = str_replace('_', '\\\\_', $this->prefix);
        $sql = "SELECT tablename
                  FROM pg_catalog.pg_tables
                 WHERE tablename LIKE '$prefix%'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            while ($row = pg_fetch_row($result)) {
                $tablename = reset($row);
                if (strpos($tablename, $this->prefix) !== 0) {
                    continue;
                }
                $tablename = substr($tablename, strlen($this->prefix));
                $this->tables[$tablename] = $tablename;
            }
            pg_free_result($result);
        }
        return $this->tables;
    }

    /**
     * Return table indexes - everything lowercased
     * @return array of arrays
     */
    public function get_indexes($table) {
        $indexes = array();
        $tablename = $this->prefix.$table;

        $sql = "SELECT *
                  FROM pg_catalog.pg_indexes
                 WHERE tablename = '$tablename'";

        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                if (!preg_match('/CREATE (|UNIQUE )INDEX ([^\s]+) ON '.$tablename.' USING ([^\s]+) \(([^\)]+)\)/i', $row['indexdef'], $matches)) {
                    continue;
                }
                if ($matches[4] === 'id') {
                    continue;
                }
                $columns = explode(',', $matches[4]);
                $columns = array_map(array($this, 'trim_quotes'), $columns);
                $indexes[$matches[2]] = array('unique'=>!empty($matches[1]),
                                              'columns'=>$columns);
            }
            pg_free_result($result);
        }
        return $indexes;
    }

    /**
     * Returns datailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return array array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        if ($usecache and isset($this->columns[$table])) {
            return $this->columns[$table];
        }

        $this->columns[$table] = array();

        $tablename = $this->prefix.$table;

        $sql = "SELECT a.attnum, a.attname AS field, t.typname AS type, a.attlen, a.atttypmod, a.attnotnull, a.atthasdef, d.adsrc
                  FROM pg_catalog.pg_class c
                  JOIN pg_catalog.pg_attribute a ON a.attrelid = c.oid
                  JOIN pg_catalog.pg_type t ON t.oid = a.atttypid
             LEFT JOIN pg_catalog.pg_attrdef d ON (d.adrelid = c.oid AND d.adnum = a.attnum)
                 WHERE relkind = 'r' AND c.relname = '$tablename' AND c.reltype > 0 AND a.attnum > 0
              ORDER BY a.attnum";

        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if (!$result) {
            return array();
        }
        while ($rawcolumn = pg_fetch_object($result)) {

            $info = new object();
            $info->name = $rawcolumn->field;
            $matches = null;

            if ($rawcolumn->type === 'varchar') {
                //TODO add some basic enum support here
                $info->type          = 'varchar';
                $info->meta_type     = 'C';
                $info->max_length    = $rawcolumn->atttypmod - 4;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                        $info->default_value = trim($info->default_value, "'");
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                } else {
                    $info->default_value = null;
                }
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/int(\d)/i', $rawcolumn->type, $matches)) {
                $info->type = 'int';
                if (strpos($rawcolumn->adsrc, 'nextval') === 0) {
                    $info->primary_key   = true;
                    $info->meta_type     = 'R';
                    $info->unique        = true;
                    $info->auto_increment= true;
                    $info->has_default   = false;
                } else {
                    $info->primary_key   = false;
                    $info->meta_type     = 'I';
                    $info->unique        = null;
                    $info->auto_increment= false;
                    $info->has_default   = ($rawcolumn->atthasdef === 't');
                }
                $info->max_length    = $matches[1];
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                if ($info->has_default) {
                    $info->default_value = $rawcolumn->adsrc;
                } else {
                    $info->default_value = null;
                }
                $info->binary        = false;
                $info->unsigned      = false;

            } else if ($rawcolumn->type === 'numeric') {
                $info->type = $rawcolumn->type;
                $info->meta_type     = 'N';
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $info->default_value = $rawcolumn->adsrc;
                } else {
                    $info->default_value = null;
                }
                $info->max_length    = $rawcolumn->atttypmod >> 16;
                $info->scale         = ($rawcolumn->atttypmod & 0xFFFF) - 4;

            } else if (preg_match('/float(\d)/i', $rawcolumn->type, $matches)) {
                $info->type = 'float';
                $info->meta_type     = 'N';
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $info->default_value = $rawcolumn->adsrc;
                } else {
                    $info->default_value = null;
                }
                // just guess expected number of deciaml places :-(
                if ($matches[1] == 8) {
                    // total 15 digits
                    $info->max_length = 8;
                    $info->scale      = 7;
                } else {
                    // total 6 digits
                    $info->max_length = 4;
                    $info->scale      = 2;
                }

            } else if ($rawcolumn->type === 'text') {
                $info->type          = $rawcolumn->type;
                $info->meta_type     = 'X';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = ($rawcolumn->atthasdef === 't');
                if ($info->has_default) {
                    $parts = explode('::', $rawcolumn->adsrc);
                    if (count($parts) > 1) {
                        $info->default_value = reset($parts);
                        $info->default_value = trim($info->default_value, "'");
                    } else {
                        $info->default_value = $rawcolumn->adsrc;
                    }
                } else {
                    $info->default_value = null;
                }
                $info->primary_key   = false;
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if ($rawcolumn->type === 'bytea') {
                $info->type          = $rawcolumn->type;
                $info->meta_type     = 'B';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->attnotnull === 't');
                $info->has_default   = false;
                $info->default_value = null;
                $info->primary_key   = false;
                $info->binary        = true;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            }

            $this->columns[$table][$info->name] = new database_column_info($info);
        }

        pg_free_result($result);

        return $this->columns[$table];
    }

    /**
     * Is db in unicode mode?
     * @return bool
     */
    public function setup_is_unicodedb() {
    /// Get PostgreSQL server_encoding value
        $sql = "SHOW server_encoding";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if (!$result) {
            return false;
        }
        $rawcolumn = pg_fetch_object($result);
        $encoding = $rawcolumn->server_encoding;
        pg_free_result($result);

        return (strtoupper($encoding) == 'UNICODE' || strtoupper($encoding) == 'UTF8');
    }

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool true
     * @throws dml_exception if error
     */
    public function change_database_structure($sql) {
        $this->reset_caches();

        $this->query_start($sql, null, SQL_QUERY_STRUCTURE);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Execute general sql query. Should be used only when no other method suitable.
     * Do NOT use this to make changes in db structure, use database_manager::execute_sql() instead!
     * @param string $sql query
     * @param array $params query parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function execute($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        if (strpos($sql, ';') !== false) {
            throw new coding_exception('moodle_database::execute() Multiple sql statements found or bound parameters not used properly in query!');
        }

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Get a number of records as a moodle_recordset using a SQL statement.
     *
     * Since this method is a little less readable, use of it should be restricted to
     * code where it's possible there might be large datasets being returned.  For known
     * small datasets use get_records_sql - it leads to simpler code.
     *
     * The return type is as for @see function get_recordset.
     *
     * @param string $sql the SQL select query to execute.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return mixed an moodle_recordset object
     * @throws dml_exception if error
     */
    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        $limitfrom = (int)$limitfrom;
        $limitnum  = (int)$limitnum;
        $limitfrom = ($limitfrom < 0) ? 0 : $limitfrom;
        $limitnum  = ($limitnum < 0)  ? 0 : $limitnum;
        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitnum OFFSET $limitfrom";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        return $this->create_recordset($result);
    }

    protected function create_recordset($result) {
        return new pgsql_native_moodle_recordset($result, $this->bytea_oid);
    }

    /**
     * Get a number of records as an array of objects using a SQL statement.
     *
     * Return value as for @see function get_records.
     *
     * @param string $sql the SQL select query to execute. The first column of this SELECT statement
     *   must be a unique value (usually the 'id' field), as it will be used as the key of the
     *   returned array.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return mixed an array of objects, or empty array if no records were found
     * @throws dml_exception if error
     */
    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        $limitfrom = (int)$limitfrom;
        $limitnum  = (int)$limitnum;
        $limitfrom = ($limitfrom < 0) ? 0 : $limitfrom;
        $limitnum  = ($limitnum < 0)  ? 0 : $limitnum;
        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitnum OFFSET $limitfrom";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        // find out if there are any blobs
        $numrows = pg_num_fields($result);
        $blobs = array();
        for($i=0; $i<$numrows; $i++) {
            $type_oid = pg_field_type_oid($result, $i);
            if ($type_oid == $this->bytea_oid) {
                $blobs[] = pg_field_name($result, $i);
            }
        }

        $rows = pg_fetch_all($result);
        pg_free_result($result);

        $return = array();
        if ($rows) {
            foreach ($rows as $row) {
                $id = reset($row);
                if ($blobs) {
                    foreach ($blobs as $blob) {
                        $row[$blob] = pg_unescape_bytea($row[$blob]);
                    }
                }
                if (isset($return[$id])) {
                    $colname = key($row);
                    debugging("Did you remember to make the first column something unique in your call to get_records? Duplicate value '$id' found in column '$colname'.", DEBUG_DEVELOPER);
                }
                $return[$id] = (object)$row;
            }
        }

        return $return;
    }

    /**
     * Selects records and return values (first field) as an array using a SQL statement.
     *
     * @param string $sql The SQL query
     * @param array $params array of sql parameters
     * @return mixed array of values
     * @throws dml_exception if error
     */
    public function get_fieldset_sql($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        $return = pg_fetch_all_columns($result, 0);
        pg_free_result($result);

        return $return;
    }

    /**
     * Insert new record into database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool $returnit return it of inserted record
     * @param bool $bulk true means repeated inserts expected
     * @param bool $customsequence true if 'id' included in $params, disables $returnid
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }

        $returning = "";

        if ($customsequence) {
            if (!isset($params['id'])) {
                throw new coding_exception('moodle_database::insert_record_raw() id field must be specified if custom sequences used.');
            }
            $returnid = false;
        } else {
            if ($returnid) {
                if ($this->is_min_version('8.2.0')) {
                    $returning = "RETURNING id";
                    unset($params['id']);
                } else {
                    //ugly workaround for pg < 8.2
                    $seqsql = "SELECT NEXTVAL('{$this->prefix}{$table}_id_seq') AS id";
                    $this->query_start($seqsql, NULL, SQL_QUERY_AUX);
                    $result = pg_query($this->pgsql, $seqsql);
                    $this->query_end($result);
                    if ($result === false) {
                        throw new dml_exception('missingidsequence', "{$this->prefix}{$table}"); // TODO: add localised string
                    }
                    $row = pg_fetch_assoc($result);
                    $params['id'] = reset($row);
                    pg_free_result($result);
                }
            } else {
                unset($params['id']);
            }
        }

        if (empty($params)) {
            throw new coding_exception('moodle_database::insert_record_raw() no fields found.');
        }

        $fields = implode(',', array_keys($params));
        $values = array();
        $count = count($params);
        for ($i=1; $i<=$count; $i++) {
            $values[] = "\$".$i;
        }
        $values = implode(',', $values);

        $sql = "INSERT INTO {$this->prefix}$table ($fields) VALUES($values) $returning";
        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        if ($returning !== "") {
            $row = pg_fetch_assoc($result);
            $params['id'] = reset($row);
        }
        pg_free_result($result);

        if (!$returnid) {
            return true;
        }

        return (int)$params['id'];
    }

    /**
     * Insert a record into a table and return the "id" field if required.
     *
     * Some conversions and safety checks are carried out. Lobs are supported.
     * If the return ID isn't required, then this just reports success as true/false.
     * $data is an object containing needed data
     * @param string $table The database table to be inserted into
     * @param object $data A data object with values for one or more fields in the record
     * @param bool $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);

        unset($dataobject->id);
        $cleaned = array();
        $blobs   = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if ($column->meta_type == 'B') {
                if (is_null($value)) {
                    $cleaned[$field] = null;
                } else {
                    $blobs[$field] = $value;
                    $cleaned[$field] = '@#BLOB#@';
                }
                continue;

            } else if (is_bool($value)) {
                $value = (int)$value; // prevent false '' problems

            } else if ($value === '') {
                if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                    $value = 0; // prevent '' problems in numeric fields
                }
            }

            $cleaned[$field] = $value;
        }

        if (empty($blobs)) {
            return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);
        }

        $id = $this->insert_record_raw($table, $cleaned, true, $bulk);

        foreach ($blobs as $key=>$value) {
            $value = pg_escape_bytea($this->pgsql, $value);
            $sql = "UPDATE {$this->prefix}$table SET $key = '$value'::bytea WHERE id = $id";
            $this->query_start($sql, NULL, SQL_QUERY_UPDATE);
            $result = pg_query($this->pgsql, $sql);
            $this->query_end($result);
            if ($result !== false) {
                pg_free_result($result);
            }
        }

        return ($returnid ? $id : true);

    }

    /**
     * Import a record into a table, id field is required.
     * Safety checks are NOT carried out. Lobs are supported.
     *
     * @param string $table name of database table to be inserted into
     * @param object $dataobject A data object with values for one or more fields in the record
     * @return bool true
     * @throws dml_exception if error
     */
    public function import_record($table, $dataobject) {
        $dataobject = (object)$dataobject;

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $cleaned[$field] = $value;
        }

        return $this->insert_record_raw($table, $cleaned, false, true, true);
    }

    /**
     * Update record in database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception if error
     */
    public function update_record_raw($table, $params, $bulk=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }
        if (!isset($params['id'])) {
            throw new coding_exception('moodle_database::update_record_raw() id field must be specified.');
        }
        $id = $params['id'];
        unset($params['id']);

        if (empty($params)) {
            throw new coding_exception('moodle_database::update_record_raw() no fields found.');
        }

        $i = 1;

        $sets = array();
        foreach ($params as $field=>$value) {
            $sets[] = "$field = \$".$i++;
        }

        $params[] = $id; // last ? in WHERE condition

        $sets = implode(',', $sets);
        $sql = "UPDATE {$this->prefix}$table SET $sets WHERE id=\$".$i;

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Update a record in a table
     *
     * $dataobject is an object containing needed data
     * Relies on $dataobject having a variable "id" to
     * specify the record to update
     *
     * @param string $table The database table to be checked against.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified.
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception if error
     */
    public function update_record($table, $dataobject, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);
        $cleaned = array();
        $blobs   = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if ($column->meta_type == 'B') {
                if (is_null($value)) {
                    $cleaned[$field] = null;
                } else {
                    $blobs[$field] = $value;
                    $cleaned[$field] = '@#BLOB#@';
                }
                continue;

            } else if (is_bool($value)) {
                $value = (int)$value; // prevent false '' problems

            } else if ($value === '') {
                if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                    $value = 0; // prevent '' problems in numeric fields
                }
            }

            $cleaned[$field] = $value;
        }

        $this->update_record_raw($table, $cleaned, $bulk);

        if (empty($blobs)) {
            return true;
        }

        $id = (int)$dataobject->id;

        foreach ($blobs as $key=>$value) {
            $value = pg_escape_bytea($this->pgsql, $value);
            $sql = "UPDATE {$this->prefix}$table SET $key = '$value'::bytea WHERE id = $id";
            $this->query_start($sql, NULL, SQL_QUERY_UPDATE);
            $result = pg_query($this->pgsql, $sql);
            $this->query_end($result);

            pg_free_result($result);
        }

        return true;
    }

    /**
     * Set a single field in every table record which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $newfield the field to set.
     * @param string $newvalue the value to set the field to.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {

        if ($select) {
            $select = "WHERE $select";
        }
        if (is_null($params)) {
            $params = array();
        }
        list($select, $params, $type) = $this->fix_sql_params($select, $params);
        $i = count($params)+1;

    /// Get column metadata
        $columns = $this->get_columns($table);
        $column = $columns[$newfield];

        if ($column->meta_type == 'B') { /// If the column is a BLOB
        /// Update BYTEA and return
            $newvalue = pg_escape_bytea($this->pgsql, $newvalue);
            $sql = "UPDATE {$this->prefix}$table SET $newfield = '$newvalue'::bytea $select";
            $this->query_start($sql, NULL, SQL_QUERY_UPDATE);
            $result = pg_query_params($this->pgsql, $sql, $params);
            $this->query_end($result);
            pg_free_result($result);
            return true;
        }

        if (is_bool($newvalue)) {
            $newvalue = (int)$newvalue; // prevent "false" problems
        }
        if (is_null($newvalue)) {
            $newfield = "$newfield = NULL";
        } else {
            $newfield = "$newfield = \$".$i;
            $params[] = $newvalue;
        }
        $sql = "UPDATE {$this->prefix}$table SET $newfield $select";

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);

        return true;
    }

    /**
     * Delete one or more records from a table which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call (used to define the selection criteria).
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function delete_records_select($table, $select, array $params=null) {
        if ($select) {
            $select = "WHERE $select";
        }
        $sql = "DELETE FROM {$this->prefix}$table $select";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = pg_query_params($this->pgsql, $sql, $params);
        $this->query_end($result);

        pg_free_result($result);

        return true;
    }

    public function sql_ilike() {
        return 'ILIKE';
    }

    public function sql_bitxor($int1, $int2) {
        return '(' . $this->sql_bitor($int1, $int2) . ' - ' . $this->sql_bitand($int1, $int2) . ')';
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS INT) ';
    }

    public function sql_cast_char2real($fieldname, $text=false) {
        return " $fieldname::real ";
    }

    public function sql_concat() {
        $arr = func_get_args();
        $s = implode(' || ', $arr);
        if ($s === '') {
            return " '' ";
        }
        return " $s ";
    }

    public function sql_concat_join($separator="' '", $elements=array()) {
        for ($n=count($elements)-1; $n > 0 ; $n--) {
            array_splice($elements, $n, 0, $separator);
        }
        $s = implode(' || ', $elements);
        if ($s === '') {
            return " '' ";
        }
        return " $s ";
    }

    public function sql_regex_supported() {
        return true;
    }

    public function sql_regex($positivematch=true) {
        return $positivematch ? '~*' : '!~*';
    }

/// session locking
    public function session_lock_supported() {
        return $this->is_min_version('8.2.0');
    }

    public function get_session_lock($rowid) {
        // TODO: there is a potential locking problem for database running
        //       multiple instances of moodle, we could try to use pg_advisory_lock(int, int),
        //       luckily there is not a big chance that they would collide
        if (!$this->session_lock_supported()) {
            return;
        }

        parent::get_session_lock($rowid);
        $sql = "SELECT pg_advisory_lock($rowid)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            pg_free_result($result);
        }
    }

    public function release_session_lock($rowid) {
        if (!$this->session_lock_supported()) {
            return;
        }
        parent::release_session_lock($rowid);

        $sql = "SELECT pg_advisory_unlock($rowid)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        if ($result) {
            pg_free_result($result);
        }
    }

/// transactions
    /**
     * on DBs that support it, switch to transaction mode and begin a transaction
     * you'll need to ensure you call commit_sql() or your changes *will* be lost.
     *
     * this is _very_ useful for massive updates
     */
    public function begin_sql() {
        $sql = "BEGIN ISOLATION LEVEL READ COMMITTED";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * on DBs that support it, commit the transaction
     */
    public function commit_sql() {
        $sql = "COMMIT";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * on DBs that support it, rollback the transaction
     */
    public function rollback_sql() {
        $sql = "ROLLBACK";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = pg_query($this->pgsql, $sql);
        $this->query_end($result);

        pg_free_result($result);
        return true;
    }

    /**
     * Helper function trimming (whitespace + quotes) any string
     * needed because PG uses to enclose with double quotes some
     * fields in indexes definition and others
     *
     * @param string $str string to apply whitespace + quotes trim
     * @return string trimmed string
     */
    private function trim_quotes($str) {
        return trim(trim($str), "'\"");
    }
}

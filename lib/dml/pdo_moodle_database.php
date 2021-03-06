<?php  //$Id$

require_once($CFG->libdir.'/dml/moodle_database.php');
require_once($CFG->libdir.'/dml/pdo_moodle_recordset.php');

/**
 * Experimental pdo database class
 * @package dml
 */
abstract class pdo_moodle_database extends moodle_database {

    protected $pdb;
    protected $lastError = null;

    /**
     * Contructor - instantiates the database, specifying if it's external (connect to other systems) or no (Moodle DB)
     *              note this has effect to decide if prefix checks must be performed or no
     * @param bool true means external database used
     */
    public function __construct($external=false) {
        parent::__construct($external);
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
     * @return bool success
     */
    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        $this->store_settings($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);

        try {
            $this->pdb = new PDO($this->get_dsn(), $this->dbuser, $this->dbpass, $this->get_pdooptions());
            // generic PDO settings to match adodb's default; subclasses can change this in configure_dbconnection
            $this->pdb->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            $this->pdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->configure_dbconnection();
            return true;
        } catch (PDOException $ex) {
            return false;
        }
    }

    /**
     * Returns the driver-dependent DSN for PDO based on members stored by connect.
     * Must be called after connect (or after $dbname, $dbhost, etc. members have been set).
     * @return string driver-dependent DSN
     */
    protected function get_dsn() {
        return 'mysql:host='.$this->dbhost.';dbname='.$this->dbname;
    }

    /**
     * Returns the driver-dependent connection attributes for PDO based on members stored by connect.
     * Must be called after $dbname, $dbhost, etc. members have been set.
     * @return array A key=>value array of PDO driver-specific connection options
     */
    protected function get_pdooptions() {
        return array(PDO::ATTR_PERSISTENT => !empty($this->dboptions['dbpersist']));
    }

    protected function configure_dbconnection() {
        ///TODO: not needed preconfigure_dbconnection() stuff for PDO drivers?
    }

    /**
     * Returns general database library name
     * Note: can be used before connect()
     * @return string db type adodb, pdo, native
     */
    protected function get_dblibrary() {
        return 'pdo';
    }

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('pdo'.$this->get_dbtype(), 'install');
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('pdo'.$this->get_dbtype().'help', 'install');
    }

    /**
     * Returns localised database description
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_hints() {
        return get_string('databasesettingssub_' . $this->get_dbtype() . '_pdo', 'install');
    }

    /**
     * Returns database server info array
     * @return array
     */
    public function get_server_info() {
        $result = array();
        try {
            $result['description'] = $this->pdb->getAttribute(PDO::ATTR_SERVER_INFO);
        } catch(PDOException $ex) {}
        try {
            $result['version'] = $this->pdb->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch(PDOException $ex) {}
        return $result;
    }

    /**
     * Returns supported query parameter types
     * @return bitmask
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_QM | SQL_PARAMS_NAMED;
    }

    /**
     * Returns last error reported by database engine.
     */
    public function get_last_error() {
        return $this->lastError;
    }

    /**
     * Function to print/save/ignore debuging messages related to SQL queries.
     */
    protected function debug_query($sql, $params = null) {
        echo '<hr /> (', $this->get_dbtype(), '): ',  htmlentities($sql);
        if($params) {
            echo ' (parameters ';
            print_r($params);
            echo ')';
        }
        echo '<hr />';
    }

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool success
     */
    public function change_database_structure($sql) {
        try {
            $this->lastError = null;
            if($this->debug) {
                $this->debug_query($sql);
            }
            $this->pdb->exec($sql);
            $this->reset_caches();
            return true;
        } catch (PDOException $ex) {
            $this->lastError = $ex->getMessage();
            return false;
        }
    }

    public function delete_records_select($table, $select, array $params=null) {
        $sql = "DELETE FROM {{$table}}";
        if ($select) {
            $sql .= " WHERE $select";
        }
        $this->writes++;
        return $this->execute($sql, $params);
    }

    /**
     * Factory method that creates a recordset for return by a query. The generic pdo_moodle_recordset
     * class should fit most cases, but pdo_moodle_database subclasses can overide this method to return
     * a subclass of pdo_moodle_recordset.
     * @param object $sth instance of PDOStatement
     * @return object instance of pdo_moodle_recordset
     */
    protected function create_recordset($sth) {
        return new pdo_moodle_recordset($sth);
    }

    /**
     * Execute general sql query. Should be used only when no other method suitable.
     * Do NOT use this to make changes in db structure, use database_manager::execute_sql() instead!
     * @param string $sql query
     * @param array $params query parameters
     * @return bool success
     */
    public function execute($sql, array $params=null) {
        try {
            $this->lastError = null;
            list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
            if($this->debug) {
                $this->debug_query($sql, $params);
            }
            $sth = $this->pdb->prepare($sql);
            $sth->execute($params);
            return true;
        } catch (PDOException $ex) {
            $this->lastError = $ex->getMessage();
            return false;
        }
    }

    /**
     * Get a number of records as an moodle_recordset.  $sql must be a complete SQL query.
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
     * @return mixed an moodle_recordset object, or false if an error occured.
     */
    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        try {
            $this->lastError = null;
            list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
            $sql = $this->get_limit_clauses($sql, $limitfrom, $limitnum);
            if($this->debug) {
                $this->debug_query($sql, $params);
            }
            $this->reads++;
            $sth = $this->pdb->prepare($sql);
            $sth->execute($params);
            return $this->create_recordset($sth);
        } catch (PDOException $ex) {
            $this->lastError = $ex->getMessage();
            return false;
        }
    }

    /**
     * Returns the sql statement with clauses to append used to limit a recordset range.
     * @param string $sql the SQL statement to limit.
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return string the SQL statement with limiting clauses
     */
    protected function get_limit_clauses($sql, $limitfrom=0, $limitnum=0) {
        return $sql;
    }

    /**
     * Selects rows and return values of first column as array.
     *
     * @param string $sql The SQL query
     * @param array $params array of sql parameters
     * @return mixed array of values or false if an error occured
     */
    public function get_fieldset_sql($sql, array $params=null) {
        if(!$rs = $this->get_recordset_sql($sql, $params)) {
            return false;
        }
        $result = array();
        foreach($rs as $value) {
            $result[] = reset($value);
        }
        $rs->close();
        return $result;
    }

    /**
     * Get a number of records as an array of objects.
     *
     * Return value as for @see function get_records.
     *
     * @param string $sql the SQL select query to execute. The first column of this SELECT statement
     *   must be a unique value (usually the 'id' field), as it will be used as the key of the
     *   returned array.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return mixed an array of objects, or empty array if no records were found, or false if an error occured.
     */
    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        if(!$rs = $this->get_recordset_sql($sql, $params, $limitfrom, $limitnum)) {
            return false;
        }
        $objects = array();
        $debugging = debugging('', DEBUG_DEVELOPER);
        foreach($rs as $value) {
            $key = reset($value);
            if ($debugging && array_key_exists($key, $objects)) {
                debugging("Did you remember to make the first column something unique in your call to get_records? Duplicate value '$key' found in column first column of '$sql'.", DEBUG_DEVELOPER);
            }
            $objects[$key] = (object)$value;
        }
        $rs->close();
        return $objects;
    }

    /**
     * Insert new record into database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool $returnit return it of inserted record
     * @param bool $bulk true means repeated inserts expected
     * @param bool $customsequence true if 'id' included in $params, disables $returnid
     * @return true or new id
     */
    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }

        if ($customsequence) {
            if (!isset($params['id'])) {
                return false;
            }
            $returnid = false;
        } else {
            unset($params['id']);
        }

        if (empty($params)) {
            return false;
        }

        $this->writes++;

        $fields = implode(',', array_keys($params));
        $qms    = array_fill(0, count($params), '?');
        $qms    = implode(',', $qms);

        $sql = "INSERT INTO {{$table}} ($fields) VALUES($qms)";
        if (!$this->execute($sql, $params)) {
            return false;
        }
        if (!$returnid) {
            return true;
        }
        if ($id = $this->pdb->lastInsertId()) {
            return (int)$id;
        }
        return false;
    }

    /**
     * Insert a record into a table and return the "id" field if required,
     * Some conversions and safety checks are carried out. Lobs are supported.
     * If the return ID isn't required, then this just reports success as true/false.
     * $data is an object containing needed data
     * @param string $table The database table to be inserted into
     * @param object $data A data object with values for one or more fields in the record
     * @param bool $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
     * @param bool $bulk true means repeated inserts expected
     * @return true or new id
     */
    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);

        unset($dataobject->id);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if (is_bool($value)) {
                $value = (int)$value; // prevent "false" problems
            }
            if (!empty($column->enums)) {
                // workaround for problem with wrong enums
                if (is_null($value) and !$column->not_null) {
                    // ok - nulls allowed
                } else {
                    if (!in_array((string)$value, $column->enums)) {
                        debugging('Enum value '.s($value).' not allowed in field '.$field.' table '.$table.'.');
                        return false;
                    }
                }
            }
            $cleaned[$field] = $value;
        }

        if (empty($cleaned)) {
            return false;
        }

        return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);
    }

    /**
     * Update record in database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool true means repeated updates expected
     * @return bool success
     */
    public function update_record_raw($table, $params, $bulk=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }
        if (!isset($params['id'])) {
            return false;
        }
        $id = $params['id'];
        unset($params['id']);

        if (empty($params)) {
            return false;
        }

        $sets = array();
        foreach ($params as $field=>$value) {
            $sets[] = "$field = ?";
        }

        $params[] = $id; // last ? in WHERE condition

        $sets = implode(',', $sets);
        $sql = "UPDATE {{$table}} SET $sets WHERE id=?";
        $this->writes++;
        return $this->execute($sql, $params);
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
     * @return bool success
     */
    public function update_record($table, $dataobject, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        if (!isset($dataobject->id) ) {
            return false;
        }

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            if (is_bool($value)) {
                $value = (int)$value; // prevent "false" problems
            }
            $cleaned[$field] = $value;
        }

        return $this->update_record_raw($table, $cleaned, $bulk);
    }

    /**
     * Set a single field in every table row where the select statement evaluates to true.
     *
     * @param string $table The database table to be checked against.
     * @param string $newfield the field to set.
     * @param string $newvalue the value to set the field to.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @return bool success
     */
    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {
        if ($select) {
            $select = "WHERE $select";
        }
        if (is_null($params)) {
            $params = array();
        }
        list($select, $params, $type) = $this->fix_sql_params($select, $params);

        if (is_bool($newvalue)) {
            $newvalue = (int)$newvalue; // prevent "false" problems
        }
        if (is_null($newvalue)) {
            $newfield = "$newfield = NULL";
        } else {
            // make sure SET and WHERE clauses use the same type of parameters,
            // because we don't support different types in the same query
            switch($type) {
            case SQL_PARAMS_NAMED:
                $newfield = "$newfield = :newvalueforupdate";
                $params['newvalueforupdate'] = $newvalue;
                break;
            case SQL_PARAMS_QM:
                $newfield = "$newfield = ?";
                array_unshift($params, $newvalue);
                break;
            default:
                $this->lastError = __FILE__ . ' LINE: ' . __LINE__ . '.';
                print_error(unknowparamtype, 'error', '', $this->lastError);
            }
        }
        $sql = "UPDATE {{$table}} SET $newfield $select";
        $this->writes++;
        return $this->execute($sql, $params);
    }

    public function sql_concat() {
        print_error('TODO');
    }

    public function sql_concat_join($separator="' '", $elements=array()) {
        print_error('TODO');
    }

    public function begin_sql() {
        try {
            $this->pdb->beginTransaction();
            return true;
        } catch(PDOException $ex) {
            return false;
        }
    }
    public function commit_sql() {
        try {
            $this->pdb->commit();
            return true;
        } catch(PDOException $ex) {
            return false;
        }
    }

    public function rollback_sql() {
        try {
            $this->pdb->rollBack();
            return true;
        } catch(PDOException $ex) {
            return false;
        }
    }

    /**
     * Import a record into a table, id field is required.
     * Basic safety checks only. Lobs are supported.
     * @param string $table name of database table to be inserted into
     * @param mixed $dataobject object or array with fields in the record
     * @return bool success
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
}

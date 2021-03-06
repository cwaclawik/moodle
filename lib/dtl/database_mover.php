<?php  //$Id$

class database_mover extends database_exporter {
    /** Importer object used to transfer data. */
    protected $importer;
    protected $feeback;

    /**
     * Object constructor.
     *
     * @param moodle_database $mdb Connection to the source database (a
     * @see moodle_database object).
     * @param moodle_database $mdb_target Connection to the target database (a
     * @see moodle_database object).
     * @param boolean $check_schema - whether or not to check that XML database
     * schema matches the RDBMS database schema before exporting (used by
     * @see export_database).
     */
    public function __construct(moodle_database $mdb_source, moodle_database $mdb_target,
            $check_schema = true, moodle_progress_trace $feeback = null) {
        if (empty($feeback)) {
            $this->feeback = new null_progress_trace();
        } else {
            $this->feeback = $feeback;
        }
        if ($check_schema) {
            $this->feeback->output(get_string('checkingsourcetables', 'dbtransfer'));
        }
        parent::__construct($mdb_source, $check_schema);
        $this->feeback->output(get_string('creatingtargettables', 'dbtransfer'));
        $this->importer = new database_importer($mdb_target, $check_schema);
    }

    /**
     * How to use transactions during the transfer.
     * @param string $mode 'pertable', 'allinone' or 'none'.
     */
    public function set_transaction_mode($mode) {
        $this->importer->set_transaction_mode($mode);
    }

    /**
     * Callback function. Calls importer's begin_database_import callback method.
     *
     * @param float $version the version of the system which generating the data
     * @param string $timestamp the timestamp of the data (in ISO 8601) format.
     * @param string $description a user description of the data.
     * @return void
     */
    public function begin_database_export($version, $release, $timestamp, $description) {
        $this->feeback->output(get_string('copyingtables', 'dbtransfer'));
        $this->importer->begin_database_import($version, $timestamp, $description);
    }

    /**
     * Callback function. Calls importer's begin_table_import callback method.
     *
     * @param xmldb_table $table - XMLDB object for the exported table
     * @return void
     */
    public function begin_table_export(xmldb_table $table) {
        $this->feeback->output(get_string('copyingtable', 'dbtransfer', $table->getName()), 1);
        $this->importer->begin_table_import($table->getName(), $table->getHash());
    }

    /**
     * Callback function. Calls importer's import_table_data callback method.
     *
     * @param xmldb_table $table - XMLDB object of the table from which data
     * was retrived
     * @param object $data - data object (fields and values from record)
     * @return void
     */
    public function export_table_data(xmldb_table $table, $data) {
        $this->importer->import_table_data($table->getName(), $data);
    }

    /**
     * Callback function. Calls importer's finish_table_import callback method.
     * @param xmldb_table $table - XMLDB object for the exported table
     * @return void
     */
    public function finish_table_export(xmldb_table $table) {
        $this->feeback->output(get_string('done', 'dbtransfer', $table->getName()), 2);
        $this->importer->finish_table_import($table->getName());
    }

    /**
     * Callback function. Calls importer's finish_database_import callback method.
     * @return void
     */
    public function finish_database_export() {
        $this->importer->finish_database_import();
    }
}

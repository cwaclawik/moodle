<?php
/**
 * Moodle - Modular Object-Oriented Dynamic Learning Environment
 *         http://moodle.com
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details:
 *
 *         http://www.gnu.org/copyleft/gpl.html
 *
 * @category  Moodle
 * @package   repository
 * @copyright Copyright (c) 1999 onwards Martin Dougiamas     http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html     GNU GPL License
 */

require_once($CFG->dirroot.'/repository/lib.php');

/**
 * repository_mahara class
 * This plugin allowed to connect a retrieve a file from Mahara site
 * This is a subclass of repository class
 */
class repository_mahara extends repository {

    /**
     * Constructor
     * @global <type> $SESSION
     * @global <type> $action
     * @global <type> $CFG
     * @param <type> $repositoryid
     * @param <type> $context
     * @param <type> $options
     */
    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        global $SESSION, $action, $CFG;
        parent::__construct($repositoryid, $context, $options);
    }

    /**
     * Declaration of the methods avalaible from mnet
     * @return <type>
     */
    public static function mnet_publishes() {
        $pf= array();
        $pf['name']        = 'remoterep'; // Name & Description go in lang file
        $pf['apiversion']  = 1;
        $pf['methods']     = array('get_folder_files', 'get_file');

        return array($pf);
    }

  /**
     *
     * @return <type>
     */
    public function check_login() {
        return !empty($this->token);
    }


    /**
     * Display the file listing - no login required
     * @global <type> $SESSION
     * @param <type> $ajax
     * @return <type>
     */
    public function print_login($ajax = true) {
        global $SESSION, $CFG, $DB;
        //jump to the peer to create a session
        //     varlog("hey du bateau");
        require_once($CFG->dirroot . '/mnet/lib.php');
        $this->ensure_environment();
        //require_login();
        //require_once($CFG->dirroot . '/mnet/xmlrpc/client.php');
        $mnetauth = get_auth_plugin('mnet');
        $host = $DB->get_record('mnet_host',array('id' => $this->options['peer'])); //need to retrieve the host url
        //  varlog($host);
        $url = $mnetauth->start_jump_session($host->id, '/repository/ws.php?callback=yes&repo_id=112', true);
        varlog($url);
        $this->token = false;
        //         redirect($url);
        $ret = array();
        $popup_btn = new stdclass;
        $popup_btn->type = 'popup';
        $popup_btn->url = $url;
        $ret['login'] = array($popup_btn);
        return $ret;

    }

    /**
     * Display the file listing for the search term
     * @param <type> $search_text
     * @return <type>
     */
    public function search($search_text) {
        return $this->get_listing('', '', $search_text);
    }

    /**
     * Set the MNET environment
     * @global <type> $MNET
     */
    private function ensure_environment() {
        global $MNET;
        if (empty($MNET)) {
            $MNET = new mnet_environment();
            $MNET->init();
        }
    }

    /**
     * Retrieve the file listing - file picker function
     * @global <type> $CFG
     * @global <type> $DB
     * @global <type> $USER
     * @param <type> $encodedpath
     * @param <type> $search
     * @return <type>
     */
    public function get_listing($path = null, $page = '', $search = '') {
        global $CFG, $DB, $USER;
        // varlog($path);
        ///check that Mahara has a good version
        ///We also check that the "get file list" method has been activated (if it is not
        ///the method will not be returned by the system method system/listMethods)
        require_once($CFG->dirroot . '/mnet/xmlrpc/client.php');
        $this->ensure_environment();

        ///check that the peer has been setup
        if (!array_key_exists('peer',$this->options)) {
            echo json_encode(array('e'=>get_string('error').' 9010: '.get_string('hostnotfound','repository_mahara')));
            exit;
        }

        $host = $DB->get_record('mnet_host',array('id' => $this->options['peer'])); //need to retrieve the host url

        ///check that the peer host exists into the database
        if (empty($host)) {
            echo json_encode(array('e'=>get_string('error').' 9011: '.get_string('hostnotfound','repository_mahara')));
            exit;
        }

        $mnet_peer = new mnet_peer();
        $mnet_peer->set_wwwroot($host->wwwroot);
        $client = new mnet_xmlrpc_client();
        $client->set_method('system/listMethods');
        $client->send($mnet_peer);
        $services = $client->response;

        if (array_key_exists('repository/mahara/repository.class.php/get_folder_files', $services) === false) {
            // varlog($services);
            echo json_encode(array('e'=>get_string('connectionfailure','repository_mahara')));
            exit;
        }


        ///connect to the remote moodle and retrieve the list of files
        $client->set_method('repository/mahara/repository.class.php/get_folder_files');
        $client->add_param($USER->username);
        $client->add_param($path);
        $client->add_param($search);

        ///call the method and manage host error
        if (!$client->send($mnet_peer)) {
            $message =" ";
            foreach ($client->error as $errormessage) {
                $message .= "ERROR: $errormessage . ";
            }
            echo json_encode(array('e'=>$message)); //display all error messages
            exit;
        }

        $services = $client->response;
        $newpath = $services[0];
        $filesandfolders = $services[1];
        // varlog("Here is the return value:");
        // varlog($filesandfolders);
        ///display error message if we could retrieve the list or if nothing were returned
        if (empty($filesandfolders)) {
            echo json_encode(array('e'=>get_string('failtoretrievelist','repository_mahara')));
            exit;
        }


        $list = array();
        if (!empty($filesandfolders['files'])) {
            foreach ($filesandfolders['files'] as $file) {
                if ($file['artefacttype'] == 'image') {
                    //$thumbnail = base64_decode($file['thumbnail']);
                    //varlog("http://jerome.moodle.com/git/mahara/htdocs/artefact/file/download.php?file=".$file['id']."&size=70x55");
                    $thumbnail = "http://jerome.moodle.com/git/mahara/htdocs/artefact/file/download.php?file=".$file['id']."&size=70x55";
                } else {
                    $thumbnail = $CFG->pixpath .'/f/'. mimeinfo('icon32', $file['title']);
                }
                $list[] = array( 'title'=>$file['title'], 'date'=>$file['mtime'], 'size'=>'10MB', 'source'=>$file['id'], 'thumbnail' => $thumbnail);
            }
        }
        if (!empty($filesandfolders['folders'])) {
            foreach ($filesandfolders['folders'] as $folder) {
                $list[] =  array('path'=>$folder['id'], 'title'=>$folder['title'], 'date'=>$folder['mtime'], 'size'=>'0', 'children'=>array(), 'thumbnail' => $CFG->pixpath .'/f/folder.gif');
            }
        }

        $filepickerlisting = array(
            'path' => $newpath,
            'dynload' => 1,
            'nosearch' => 1,
            'list'=> $list,
        );

        //  varlog($filepickerlisting);

        return $filepickerlisting;
    }



    /**
     * Download a file
     * @global object $CFG
     * @param string $url the url of file
     * @param string $file save location
     * @return string the location of the file
     * @see curl package
     */
    public function get_file($id, $file = '') {
        global $CFG, $DB, $USER;

        ///set mnet environment and set the mnet host
        require_once($CFG->dirroot . '/mnet/xmlrpc/client.php');
        $this->ensure_environment();
        $host = $DB->get_record('mnet_host',array('id' => $this->options['peer'])); //retrieve the host url
        $mnet_peer = new mnet_peer();
        $mnet_peer->set_wwwroot($host->wwwroot);

        ///create the client and set the method to call
        $client = new mnet_xmlrpc_client();
        $client->set_method('repository/mahara/repository.class.php/get_file');
        $client->add_param($USER->username);
        $client->add_param($id);

        ///call the method and manage host error
        if (!$client->send($mnet_peer)) {
            $message =" ";
            foreach ($client->error as $errormessage) {
                $message .= "ERROR: $errormessage . ";
            }
            echo json_encode(array('e'=>$message));
            exit;
        }

        $services = $client->response; //service contains the file content in the first case of the array,
        //and the filename in the second


        //the content has been encoded in base64, need to decode it
        $content = base64_decode($services[0]);
        $file = $services[1]; //filename

        ///create a temporary folder with a file
        $path = $this->prepare_file($file);
        ///fill the file with the content
        $fp = fopen($path, 'w');
        fwrite($fp,$content);
        fclose($fp);

        return $path;

    }

    /**
     * Add Instance settings input to Moodle form
     * @global <type> $CFG
     * @global <type> $DB
     * @param <type> $
     */
    public function instance_config_form(&$mform) {
        global $CFG, $DB;

        //retrieve only Moodle peers
        $hosts = $DB->get_records_sql('  SELECT
                                    h.id,
                                    h.wwwroot,
                                    h.ip_address,
                                    h.name,
                                    h.public_key,
                                    h.public_key_expires,
                                    h.transport,
                                    h.portno,
                                    h.last_connect_time,
                                    h.last_log_id,
                                    h.applicationid,
                                    a.name as app_name,
                                    a.display_name as app_display_name,
                                    a.xmlrpc_server_url
                                FROM {mnet_host} h
                                    JOIN {mnet_application} a ON h.applicationid=a.id
                                WHERE
                                    h.id <> ? AND
                                    h.deleted = 0 AND
                                    a.name = ? AND
                                    h.name <> ?',
            array($CFG->mnet_localhost_id, 'mahara', 'All Hosts'));
        $peers = array();
        foreach($hosts as $host) {
            $peers[$host->id] = $host->name;
        }


        $mform->addElement('select', 'peer', get_string('peer', 'repository_mahara'),$peers);
        $mform->addRule('peer', get_string('required'), 'required', null, 'client');

        if (empty($peers)) {
            $mform->addElement('static', null, '',  get_string('nopeer','repository_mahara'));
        }
    }

    /**
     * Names of the instance settings
     * @return <type>
     */
    public static function get_instance_option_names() {
        ///the administrator just need to set a peer
        return array('peer');
    }
}
?>
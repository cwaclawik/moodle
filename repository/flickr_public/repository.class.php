<?php
/**
 * repository_flickr_public class
 * This one is used to create public repository
 * You can set up a public account in admin page, so everyone can
 * access photos in this public account
 *
 * @author Dongsheng Cai <dongsheng@moodle.com>
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->libdir.'/flickrlib.php');

class repository_flickr_public extends repository{
    private $flickr;
    public $photos;

    public function set_option($options = array()){
        if (!empty($options['api_key'])) {
            set_config('api_key', trim($options['api_key']), 'flickr_public');
        }
        unset($options['api_key']);
        $ret = parent::set_option($options);
        return $ret;
    }

    public function get_option($config = ''){
        if($config==='api_key'){
            return trim(get_config('flickr_public', 'api_key'));
        } else {
            $options['api_key'] = trim(get_config('flickr_public', 'api_key'));
        }
        $options = parent::get_option($config);
        return $options;
    }

    public function global_search(){
        if (empty($this->flickr_account)) {
            return false;
        } else {
            return true;
        }
    }

    public function __construct($repositoryid, $context = SITEID, $options = array()){
        global $CFG;
        $options['page']    = optional_param('p', 1, PARAM_INT);
        parent::__construct($repositoryid, $context, $options);
        $this->api_key = $this->get_option('api_key');
        $this->flickr = new phpFlickr($this->api_key);

        $this->flickr_account = $this->get_option('email_address');

        $account = optional_param('flickr_account', '', PARAM_RAW);
        if(!empty($account)) {
            $people = $this->flickr->people_findByEmail($account);
            if(!empty($people)) {
                $this->flickr_account = $account;
            } else {
                throw new repository_exception('invalidemail', 'repository_flickr_public');
            }
        }
    }
    public function check_login(){
        return !empty($this->flickr_account);
    }
    public function print_login($ajax = true){
        if($ajax){
            $ret = array();
            $email_field->label = get_string('username', 'repository_flickr_public').': ';
            $email_field->id    = 'account';
            $email_field->type = 'text';
            $email_field->name = 'flickr_account';
            $ret['login'] = array($email_field);
            return $ret;
        }
    }
    public function search(){
        $people = $this->flickr->people_findByEmail($this->flickr_account);
        $tag    = optional_param('tag', '', PARAM_CLEANHTML);
        $search = optional_param('s', '', PARAM_CLEANHTML);
        if(!empty($tag)){
            $photos = $this->flickr->photos_search(array(
                'tags'=>$tag
                ));
        } else {
            $photos = $this->flickr->photos_search(array(
                'user_id'=>$people['nsid'],
                'text'=>$search));
        }
        $ret = array();
        $ret['list']  = array();
        $ret['nologin'] = true;
        $ret['pages'] = $photos['pages'];
        foreach ($photos['photo'] as $p) {
            if(empty($p['title'])) {
                $p['title'] = get_string('notitle', 'repository_flickr_public');
            }
            if (isset($p['originalformat'])) {
                $format = $p['originalformat'];
            } else {
                $format = 'jpg';
            }
            $ret['list'][] =
                array('title'=>$p['title'].'.'.$format,'source'=>$p['id'],'id'=>$p['id'],'thumbnail'=>$this->flickr->buildPhotoURL($p, 'Square'), 'date'=>'', 'size'=>'unknown', 'url'=>'http://www.flickr.com/photos/'.$p['owner'].'/'.$p['id']);
        }
        if(empty($ret)) {
            throw new repository_exception('nullphotolist', 'repository_flickr_public');
        } else {
            return $ret;
        }
    }
    public function get_listing($path = '1', $search = ''){
        $people = $this->flickr->people_findByEmail($this->flickr_account);
        $photos_url = $this->flickr->urls_getUserPhotos($people['nsid']);

        $photos = $this->flickr->people_getPublicPhotos($people['nsid'], 'original_format', 25, $path);

        $ret = array();
        $ret['manage'] = $photos_url;
        $ret['list']  = array();
        $ret['nologin'] = true;
        $ret['pages'] = $photos['pages'];
        if(is_int($path) && $path <= $ret['pages']) {
            $ret['page'] = $path;
        } else {
            $ret['page'] = 1;
        }
        foreach ($photos['photo'] as $p) {
            if(empty($p['title'])) {
                $p['title'] = get_string('notitle', 'repository_flickr_public');
            }
            if (isset($p['originalformat'])) {
                $format = $p['originalformat'];
            } else {
                $format = 'jpg';
            }
            $ret['list'][] =
                array('title'=>$p['title'].'.'.$format,'source'=>$p['id'],'id'=>$p['id'],'thumbnail'=>$this->flickr->buildPhotoURL($p, 'Square'), 'date'=>'', 'size'=>'unknown', 'url'=>$photos_url.$p['id']);
        }
        if(empty($ret)) {
            throw new repository_exception('nullphotolist', 'repository_flickr_public');
        } else {
            return $ret;
        }
    }
    public function print_listing(){
        return false;
    }
    public function print_search(){
        parent::print_search();
        echo '<label>Keyword: </label><input type="text" name="s" /><br />';
        echo '<label>Tag: </label><input type="text" name="tag" /><br />';
        return true;
    }
    public function get_file($photo_id, $file = ''){
        global $CFG;
        $result = $this->flickr->photos_getSizes($photo_id);
        $url = '';
        if(!empty($result[4])) {
            $url = $result[4]['source'];
        } elseif(!empty($result[3])) {
            $url = $result[3]['source'];
        } elseif(!empty($result[2])) {
            $url = $result[2]['source'];
        }
        if (!file_exists($CFG->dataroot.'/repository/download')) {
            mkdir($CFG->dataroot.'/repository/download/', 0777, true);
        }
        if(is_dir($CFG->dataroot.'/repository/download')) {
            $dir = $CFG->dataroot.'/repository/download/';
        }

        if(empty($file)) {
            $file = $photo_id.'_'.time().'.jpg';
        }
        if(file_exists($dir.$file)) {
            $file = uniqid('m').$file;
        }
        $fp = fopen($dir.$file, 'w');
        $c = new curl;
        $c->download(array(
            array('url'=>$url, 'file'=>$fp)
        ));
        return $dir.$file;
    }
    public static function has_admin_config() {
        return true;
    }

    public static function has_multiple_instances() {
        return true;
    }

    public static function has_instance_config() {
        return true;
    }

    public function instance_config_form(&$mform) {
        $mform->addElement('text', 'email_address', get_string('emailaddress', 'repository_flickr_public'));
        //$mform->addRule('email_address', get_string('required'), 'required', null, 'client');
    }

    public static function get_instance_option_names() {
        return array('email_address');
    }

    public function admin_config_form(&$mform) {
        $api_key = get_config('flickr_public', 'api_key');
        if (empty($api_key)) {
            $api_key = '';
        }
        $strrequired = get_string('required');
        $mform->addElement('text', 'api_key', get_string('apikey', 'repository_flickr_public'), array('value'=>$api_key,'size' => '40'));
        $mform->addRule('api_key', $strrequired, 'required', null, 'client');
    }

    public static function get_admin_option_names() {
        return array('api_key');
    }

    public static function type_init() {
        //here we create a default instances for this type
    }

}

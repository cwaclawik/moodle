<?php // $Id$
require_once($CFG->libdir.'/simpletest/testportfoliolib.php');
require_once($CFG->dirroot.'/portfolio/type/boxnet/lib.php');

Mock::generate('boxclient', 'mock_boxclient');
Mock::generatePartial('portfolio_plugin_boxnet', 'mock_boxnetplugin', array('ensure_ticket', 'ensure_account_tree'));

class testPortfolioPluginBoxnet extends portfoliolib_test {
    public function setUp() {
        parent::setUp();
        $this->plugin = &new mock_boxnetplugin($this);
        $this->plugin->boxclient = new mock_boxclient();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_something() {
        $ticket = md5(rand(0,873907));
        $authtoken = 'ezfoeompplpug3ofii4nud0d8tvg96e0';

        $this->plugin->setReturnValue('ensure_account_tree', true);
        $this->plugin->setReturnValue('ensure_ticket', $ticket);

        $this->plugin->boxclient->setReturnValue('renameFile', true);
        $this->plugin->boxclient->setReturnValue('uploadFile', array('status' => 'upload_ok', 'id' => array(1)));
        $this->plugin->boxclient->setReturnValue('createFolder', array(1 => 'folder 1', 2 => 'folder 2'));
        $this->plugin->boxclient->setReturnValue('isError', false);
        $this->plugin->boxclient->authtoken = $authtoken;

        $this->assertTrue($this->plugin->set('exporter', $this->exporter));
        $this->assertTrue($this->plugin->set('ticket', $ticket));
        $this->assertTrue($this->plugin->set('authtoken', $authtoken));
        $this->plugin->set_export_config(array('folder' => 1));

        $this->assertTrue($this->plugin->prepare_package());
        $this->assertTrue($this->plugin->send_package());
    }
}
?>
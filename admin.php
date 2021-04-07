<?php

use dokuwiki\Form\Form;

/**
 * DokuWiki Plugin deletehistory (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Anna Dabrowska <dokuwiki@cosmocode.de>
 */

class admin_plugin_deletehistory extends DokuWiki_Admin_Plugin
{
    /**
     * @return int sort number in admin menu
     */
    public function getMenuSort()
    {
        return 200;
    }

    /**
     * Should carry out any processing required by the plugin.
     */
    public function handle()
    {
        if (isset($_REQUEST['cmd']) && key($_REQUEST['cmd']) === 'delete' && checkSecurityToken()) {
            /** @var helper_plugin_deletehistory $helper */
            $helper = $this->loadHelper('deletehistory');
            $helper->deleteAllHistory();
            msg($this->getLang('done'));
        }
    }

    /**
     * Render HTML output
     */
    public function html()
    {
        ptln('<h1>' . $this->getLang('menu') . '</h1>');
        echo $this->locale_xhtml('intro');

        $form = new Form();
        $form->setHiddenField('do', 'admin');
        $form->setHiddenField('page', $this->getPluginName());
        $form->addButton('cmd[delete]', $this->getLang('buttonDelete'));
        echo $form->toHTML();
    }
}


<?php
class KwcNewsletter_Kwc_Newsletter_MenuConfig extends Kwf_Component_Abstract_MenuConfig_Abstract
{
    protected function _getParentResource(Kwf_Acl $acl, $type)
    {
        if (!$acl->has('kwc_newsletter')) {
            $acl->add(new Kwf_Acl_Resource_MenuDropdown('kwc_newsletter', array(
                'text' => trlKwfStatic('Newsletter'),
                'icon' => 'email_open_image.png')
            ), 'kwf_component_root');
        }
        return 'kwc_newsletter';
    }

    protected function _getMenuConfigText(Kwf_Component_Data $c, $type) {
        if ($type == 'newsletter') {
            $componentName = Kwc_Abstract::getSetting($this->_class, 'componentName');
            return trlKwfStatic('Edit {0}', $componentName);
        } else if ($type == 'categories') {
            return trlKwfStatic('Edit {0}', trlKwfStatic('Categories'));
        }
    }

    public function addResources(Kwf_Acl $acl)
    {
        $components = Kwf_Component_Data_Root::getInstance()
                ->getComponentsBySameClass($this->_class, array('ignoreVisible'=>true));
        foreach ($components as $c) {
            $subrootName = $this->_getSubrootName($components, $c);

            $menuConfig = array(
                'text' => $this->_getMenuConfigText($c, 'newsletter') . $subrootName,
                'icon' => Kwc_Abstract::getSetting($this->_class, 'componentIcon')
            );

            $acl->add(
                new Kwf_Acl_Resource_Component_MenuUrl($c, $menuConfig),
                $this->_getParentResource($acl, 'newsletter')
            );

            $menuConfig = array(
                'text' => $this->_getMenuConfigText($c, 'categories') . $subrootName,
                'icon' => new Kwf_Asset('package')
            );
            $acl->add(
                new Kwf_Acl_Resource_Component_MenuUrl(
                    'kwc_'.$c->dbId.'-categories',
                    $menuConfig,
                    Kwc_Admin::getInstance($this->_class)->getControllerUrl('Categories').'?componentId='.$c->dbId,
                    $c
                ),
                $this->_getParentResource($acl, 'categories')
            );
        }
    }

    private function _getSubrootName(array $components, Kwf_Component_Data $c)
    {
        $ret = '';
        if (count($components) > 1) {
            $subRoot = $c;
            while($subRoot = $subRoot->parent) {
                if (Kwc_Abstract::getFlag($subRoot->componentClass, 'subroot')) break;
            }
            if ($subRoot) {
                $ret = ' ('.$subRoot->name.')';
            }
        }
        return $ret;
    }

    public function getEventsClass()
    {
        return 'Kwf_Component_Abstract_MenuConfig_SameClass_Events';
    }
}

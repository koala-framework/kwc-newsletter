<?php

namespace KwcNewsletter\Bundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements UserInterface
{
    private $username;
    private $roles;
    private $newsletterComponentId;

    public function __construct($username, array $roles = array(), $newsletterComponentId = null)
    {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        if ('' === $newsletterComponentId || null === $newsletterComponentId) {
            throw new \InvalidArgumentException('The newsLetterComponentId cannot be empty.');
        }

        $this->username = $username;
        $this->roles = $roles;
        $this->newsletterComponentId = $newsletterComponentId;
    }

    /**
     * Returns the newsletter component id of this api user.
     *
     * @return \Kwf_Component_Data The newsletter component
     */
    public function getNewsletterComponent()
    {
        $root = \Kwf_Component_Data_Root::getInstance();
        $subroot = $root->getComponentById($this->newsletterComponentId);
        if (!$subroot) throw new \InvalidArgumentException('Invalid newsLetterComponentId');

        return \Kwf_Component_Data_Root::getInstance()->getComponentByClass(
            'KwcNewsletter_Kwc_Newsletter_Component', array('subroot' => $subroot)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        throw new UnsupportedUserException();
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        throw new UnsupportedUserException();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}

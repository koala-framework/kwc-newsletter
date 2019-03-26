<?php

namespace KwcNewsletter\Bundle\Security;

use KwcNewsletter\Bundle\Model\NewsletterApiKeys;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyUserProvider implements UserProviderInterface
{
    private $apiKeysModel;

    public function __construct(NewsletterApiKeys $apiKeysModel)
    {
        $this->apiKeysModel = $apiKeysModel;
    }

    public function getUsernameForApiKey($apiKey)
    {
        $s = $this->apiKeysModel->select();
        $s->whereEquals('key', $apiKey);
        $apiKeyRow = $this->apiKeysModel->getRow($s);
        if (!$apiKeyRow) {
            throw new AccessDeniedHttpException('Invalid or empty API Key');
        }

        return $apiKeyRow->name;
    }

    public function loadUserByUsername($username)
    {
        $s = $this->apiKeysModel->select();
        $s->whereEquals('name', $username);
        $apiKeyRow = $this->apiKeysModel->getRow($s);
        if (!$apiKeyRow) {
            throw new AccessDeniedHttpException('Invalid or empty API Key');
        }

        return new ApiUser(
            $username,
            array('ROLE_API'),
            $apiKeyRow->newsletter_component_id
        );
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}

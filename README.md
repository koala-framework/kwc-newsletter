# kwc-newsletter
Newsletter Component for Koala Framework

### Installation

### Runner

* Add process-control to `config.ini`

        ...
        processControl.kwcNewsletterStartRunner.cmd = symfony kwc_newsletter:start_runner
        ...

#### Bundle

* Add Bundle to `AppKernel`

        public function registerBundles()
        {
            $bundles = array(
                ...
                new KwcNewsletter\Bundle\KwcNewsletterBundle()
            );
            ...
        }

#### Subscriber API

* Disable CSRF protection for subscribers api in `config.yml`

        kwf:
            csrf_protection:
                ignore_paths:
                    ...
                    - ^/api/v1/subscribers

* Add routes to `routing.yml`

        kwc_newsletter_subscribers_api:
           resource: "@KwcNewsletterBundle/Resources/config/routing.yml"

* Add security access_control entry to `security.yml`

        access_control:
            ...
            - { path: ^/api/v1/subscribers, roles: IS_AUTHENTICATED_ANONYMOUSLY }
            ...

#### Subscriber Open API

* Disable CSRF protection for open api in `config.yml`

        kwf:
            csrf_protection:
                ignore_paths:
                    ...
                    - ^/api/v1/open
                    
* Enable FOS Rest Bundle's serializer in `config.yml`

        fos_rest:
            routing_loader:
                default_format: json
                include_format: false
            format_listener:
                enabled: true
                rules:
                    - { path: '^/api/v1/open', fallback_format: json }

* Add API-key user provider to `security.yml`

        providers:
            ...
            api_key_user_provider:
                id: api_key_user_provider
            ...

* Add firewalls entry to `security.yml`

        firewalls:
            ...
            kwf_newsletter_bundle_open_api:
                pattern: ^/api/v1/open
                anonymous: true
                stateless: true
                simple_preauth:
                    authenticator: apikey_authenticator
                provider: api_key_user_provider
            ...

* Add security access_control entry to `security.yml`

        access_control:
            ...
            - { path: ^/api/v1/open, roles: ROLE_API }
            ...

* Add backend admin route to `bootstrap.php`

        ...
        $front = Kwf_Controller_Front_Component::getInstance();:

        $front->addControllerDirectory('vendor/koala-framework/kwc-newsletter/KwcNewsletter/Controller', 'kwc-newsletter_controller');
        if ($front->getRouter() instanceof Kwf_Controller_Router) {
            $front->getRouter()->AddRoute('kwc-newsletter', new Zend_Controller_Router_Route(
                '/admin/kwc-newsletter/:controller/:action',
                array('module'     => 'kwc-newsletter_controller',
                    'controller' =>'index',
                    'action'     =>'index')));
        }        
        ...
        
* Add component ACL to `app/Acl.php`

        ...
        $this->setComponentAclClass('Component_Acl');

        KwcNewsletter_Acl::initialise($this);
        ...

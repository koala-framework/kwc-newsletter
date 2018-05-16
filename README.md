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

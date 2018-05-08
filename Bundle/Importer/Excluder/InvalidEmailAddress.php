<?php
namespace KwcNewsletter\Bundle\Importer\Excluder;

class InvalidEmailAddress implements ExcluderInterface
{
    /**
     * @var \Kwf_Validate_EmailAddressSimple
     */
    private $validator;

    public function __construct()
    {
        $this->validator = new \Kwf_Validate_EmailAddressSimple();
    }

    public function isExcluded($email)
    {
        return !$email || !$this->validator->isValid($email);
    }
}

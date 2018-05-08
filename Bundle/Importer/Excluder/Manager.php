<?php
namespace KwcNewsletter\Bundle\Importer\Excluder;

class Manager
{
    /**
     * @var ExcluderInterface[]
     */
    private $excluders = array();

    /**
     * @param ExcluderInterface[] $excluders
     */
    public function __construct(array $excluders = array())
    {
        $this->excluders = $excluders;
    }

    public function isExcluded($email)
    {
        $ret = false;
        foreach ($this->excluders as $excluder) {
            $ret = $excluder->isExcluded($email);
            if ($ret) break;
        }
        return $ret;
    }
}

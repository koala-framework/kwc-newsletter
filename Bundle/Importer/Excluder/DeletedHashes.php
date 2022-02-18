<?php
namespace KwcNewsletter\Bundle\Importer\Excluder;

use KwcNewsletter\Bundle\Model\DeletedSubscriberHashes;

class DeletedHashes implements ExcluderInterface
{
    /**
     * @var DeletedSubscriberHashes
     */
    private $model;

    /**
     * @var array
     */
    private $deletedHashes = null;

    public function __construct(DeletedSubscriberHashes $model)
    {
        $this->model = $model;
    }

    public function isExcluded($email)
    {
        if (!$this->deletedHashes) {
            $this->deletedHashes = array();
            foreach ($model->export(\Kwf_Model_Abstract::FORMAT_ARRAY) as $row) {
                $this->deletedHashes[$row['id']] = true;
            }
        }
        return array_key_exists(sha1($email), $this->deletedHashes);
    }
}

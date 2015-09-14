<?php

namespace Anva\MailClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImapContacts
 *
 * @ORM\Table(name="contacts")
 * @ORM\Entity(repositoryClass="Anva\MailClientBundle\Entity\ImapContactsRepository")
 */
class ImapContacts
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="imap_account_id", type="integer")
     */
    private $imapAccountId;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", length=100)
     */
    private $contactName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_email", type="string", length=100)
     */
    private $contactEmail;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set imapAccountId
     *
     * @param integer $imapAccountId
     * @return ImapContacts
     */
    public function setImapAccountId($imapAccountId)
    {
        $this->imapAccountId = $imapAccountId;

        return $this;
    }

    /**
     * Get imapAccountId
     *
     * @return integer 
     */
    public function getImapAccountId()
    {
        return $this->imapAccountId;
    }

    /**
     * Set contactName
     *
     * @param string $contactName
     * @return ImapContacts
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;

        return $this;
    }

    /**
     * Get contactName
     *
     * @return string 
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * Set contactEmail
     *
     * @param string $contactEmail
     * @return ImapContacts
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    /**
     * Get contactEmail
     *
     * @return string 
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }
}

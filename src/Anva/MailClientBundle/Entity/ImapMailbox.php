<?php

namespace Anva\MailClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImapMailbox
 *
 * @ORM\Table(name="imap_mailboxes")
 * @ORM\Entity(repositoryClass="Anva\MailClientBundle\Entity\ImapMailboxRepository")
 */
class ImapMailbox
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
     * @ORM\Column(name="imapaccount_id", type="integer")
     */
    private $imapaccountId;

    /**
     * @var string
     *
     * @ORM\Column(name="uid_validity", type="string", length=100)
     */
    private $uidValidity;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=250)
     */
    private $address;

    /**
     * @var integer
     *
     * @ORM\Column(name="unread_no", type="integer")
     */
    private $unreadNo;


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
     * Set imapaccountId
     *
     * @param integer $imapaccountId
     * @return ImapMailbox
     */
    public function setImapaccountId($imapaccountId)
    {
        $this->imapaccountId = $imapaccountId;

        return $this;
    }

    /**
     * Get imapaccountId
     *
     * @return integer 
     */
    public function getImapaccountId()
    {
        return $this->imapaccountId;
    }

    /**
     * Set uidValidity
     *
     * @param string $uidValidity
     * @return ImapMailbox
     */
    public function setUidValidity($uidValidity)
    {
        $this->uidValidity = $uidValidity;

        return $this;
    }

    /**
     * Get uidValidity
     *
     * @return string 
     */
    public function getUidValidity()
    {
        return $this->uidValidity;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ImapMailbox
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return ImapMailbox
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set unreadNo
     *
     * @param integer $unreadNo
     * @return ImapMailbox
     */
    public function setUnreadNo($unreadNo)
    {
        $this->unreadNo = $unreadNo;

        return $this;
    }

    /**
     * Get unreadNo
     *
     * @return integer 
     */
    public function getUnreadNo()
    {
        return $this->unreadNo;
    }
}

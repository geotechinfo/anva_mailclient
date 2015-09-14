<?php

namespace Anva\MailClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImapMail
 *
 * @ORM\Table(name="imap_mails")
 * @ORM\Entity(repositoryClass="Anva\MailClientBundle\Entity\ImapMailRepository")
 */
class ImapMail
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
     * @var integer
     *
     * @ORM\Column(name="mailbox_id", type="integer")
     */
    private $mailboxId;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", length=100)
     */
    private $uid;

    /**
     * @var string
     *
     * @ORM\Column(name="mailFrom", type="string", length=250)
     */
    private $mailFrom;

    /**
     * @var boolean
     *
     * @ORM\Column(name="readStat", type="boolean")
     */
    private $readStat;

    /**
     * @var string
     *
     * @ORM\Column(name="timeStamp", type="string", length=25)
     */
    private $timeStamp;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=1000)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="string", length=50000)
     */
    private $body;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hasAttachment", type="boolean")
     */
    private $hasAttachment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="flagged", type="boolean")
     */
    private $flagged;


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
     * Set mailboxId
     *
     * @param integer $mailboxId
     * @return ImapMail
     */
    public function setMailboxId($mailboxId)
    {
        $this->mailboxId = $mailboxId;

        return $this;
    }

    /**
     * Get mailboxId
     *
     * @return integer 
     */
    public function getMailboxId()
    {
        return $this->mailboxId;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return ImapMail
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set mailFrom
     *
     * @param string $mailFrom
     * @return ImapMail
     */
    public function setMailFrom($mailFrom)
    {
        $this->mailFrom = $mailFrom;

        return $this;
    }

    /**
     * Get mailFrom
     *
     * @return string 
     */
    public function getMailFrom()
    {
        return $this->mailFrom;
    }

    /**
     * Set readStat
     *
     * @param boolean $readStat
     * @return ImapMail
     */
    public function setReadStat($readStat)
    {
        $this->readStat = $readStat;

        return $this;
    }

    /**
     * Get readStat
     *
     * @return boolean 
     */
    public function getReadStat()
    {
        return $this->readStat;
    }

    /**
     * Set timeStamp
     *
     * @param string $timeStamp
     * @return ImapMail
     */
    public function setTimeStamp($timeStamp)
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get timeStamp
     *
     * @return string 
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return ImapMail
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return ImapMail
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set hasAttachment
     *
     * @param boolean $hasAttachment
     * @return ImapMail
     */
    public function setHasAttachment($hasAttachment)
    {
        $this->hasAttachment = $hasAttachment;

        return $this;
    }

    /**
     * Get hasAttachment
     *
     * @return boolean 
     */
    public function getHasAttachment()
    {
        return $this->hasAttachment;
    }

    /**
     * Set flagged
     *
     * @param boolean $flagged
     * @return ImapMail
     */
    public function setFlagged($flagged)
    {
        $this->flagged = $flagged;

        return $this;
    }

    /**
     * Get flagged
     *
     * @return boolean 
     */
    public function getFlagged()
    {
        return $this->flagged;
    }
}

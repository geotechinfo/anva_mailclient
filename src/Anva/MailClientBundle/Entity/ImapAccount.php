<?php

namespace Anva\MailClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImapAccount
 *
 * @ORM\Table(name="imap_accounts")
 * @ORM\Entity(repositoryClass="Anva\MailClientBundle\Entity\ImapAccountRepository")
 */
class ImapAccount
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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;
	
	/**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=50)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=50)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=50)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="imap_host", type="string", length=100)
     */
    private $imapHost;

    /**
     * @var integer
     *
     * @ORM\Column(name="imap_port", type="integer")
     */
    private $imapPort;
	
	/**
     * @var string
     *
     * @ORM\Column(name="smtp_host", type="string", length=100, nullable=true)
     */
    private $smtpHost = null;

    /**
     * @var integer
     *
     * @ORM\Column(name="smtp_port", type="integer", nullable=true)
     */
    private $smtpPort = null;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_tls", type="boolean")
     */
    private $isTls;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_perpage", type="integer")
     */
    private $itemPerpage;


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
     * Set userId
     *
     * @param integer $userId
     * @return ImapAccount
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }
	
	/**
     * Set firstName
     *
     * @param string $firstName
     * @return ImapAccount
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }
	
	/**
     * Set lastName
     *
     * @param string $lastName
     * @return ImapAccount
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return ImapAccount
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return ImapAccount
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set imapHost
     *
     * @param string $imapHost
     * @return ImapAccount
     */
    public function setImapHost($imapHost)
    {
        $this->imapHost = $imapHost;

        return $this;
    }

    /**
     * Get imapHost
     *
     * @return string 
     */
    public function getImapHost()
    {
        return $this->imapHost;
    }

    /**
     * Set imapPort
     *
     * @param integer $imapPort
     * @return ImapAccount
     */
    public function setImapPort($imapPort)
    {
        $this->imapPort = $imapPort;

        return $this;
    }

    /**
     * Get imapPort
     *
     * @return integer 
     */
    public function getImapPort()
    {
        return $this->imapPort;
    }
	
	/**
     * Set smtpHost
     *
     * @param string $smtpHost
     * @return ImapAccount
     */
    public function setSmtpHost($smtpHost)
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    /**
     * Get smtpHost
     *
     * @return string 
     */
    public function getSmtpHost()
    {
        return $this->smtpHost;
    }

    /**
     * Set smtpPort
     *
     * @param integer $smtpPort
     * @return ImapAccount
     */
    public function setSmtpPort($smtpPort)
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    /**
     * Get smtpPort
     *
     * @return integer 
     */
    public function getSmtpPort()
    {
        return $this->smtpPort;
    }

    /**
     * Set isTls
     *
     * @param boolean $isTls
     * @return ImapAccount
     */
    public function setIsTls($isTls)
    {
        $this->isTls = $isTls;

        return $this;
    }

    /**
     * Get isTls
     *
     * @return boolean 
     */
    public function getIsTls()
    {
        return $this->isTls;
    }

    /**
     * Set itemPerpage
     *
     * @param integer $itemPerpage
     * @return ImapAccount
     */
    public function setItemPerpage($itemPerpage)
    {
        $this->itemPerpage = $itemPerpage;

        return $this;
    }

    /**
     * Get itemPerpage
     *
     * @return integer 
     */
    public function getItemPerpage()
    {
        return $this->itemPerpage;
    }
}

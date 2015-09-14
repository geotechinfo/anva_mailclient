<?php
namespace Anva\MailClientBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

use Anva\MailClientBundle\Services\ImapClass;
use Anva\MailClientBundle\Entity\ImapMailbox;

class UtilityHelper{
	protected $entity_manager;
	public function __construct(EntityManager $entityManager)
	{
		$this->entity_manager = $entityManager;
	}
	
	// Get user's IMAP settings
    public function imap_detail() {
		$session = new Session();
		if($session->has('userid')){ // If user logged in
			$user_id = $session->get('userid');

			$entity = $this->entity_manager->getRepository('MailClientBundle:ImapAccount')->findOneBy(array('userId'=>$user_id));
			$return = new \stdClass();
			if($entity){ // If IMAP detail exist
				$address = "{".$entity->getImapHost().":".$entity->getImapPort()."/imap/ssl/novalidate-cert}";
				
				$return->userId = $entity->getUserId();
				$return->imapaccountId = $entity->getId();
				$return->firstName = $entity->getFirstname();
				$return->lastName = $entity->getLastname();
				$return->email = $entity->getEmail();
				$return->password = $entity->getPassword();
				$return->imapHost = $entity->getImapHost();
				$return->imapPort = $entity->getImapPort();
				$return->isTls = $entity->getIsTls();
				$return->address = $address;
				$return->itemPerpage = $entity->getItemPerpage();
				
				// Fetch imap account
				$imap_handler = new ImapClass($entity->getEmail(), $entity->getPassword());
				$imap_account = $imap_handler->accountDetail($address);
				
				$return->usageQuota = $imap_account->usageQuota;
				$return->limitQuota = $imap_account->limitQuota;
			}else{ // If IMAP detail empty
				// Fetch user detail
				$entity = $this->entity_manager->getRepository('MailClientBundle:User')->findOneBy(array('id'=>$user_id));
				$return->userId = $user_id;
				$return->firstName = $entity->getFirstname();
				$return->lastName = $entity->getLastname();
			}
			return $return;
		}
		return false;
    }
}
<?php
/*
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Brahma
*/
namespace Anva\MailClientBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use Anva\MailClientBundle\Helper\UtilityHelper;
use Anva\MailClientBundle\Services\ImapClass;
use Anva\MailClientBundle\Entity\ImapMailbox;
use Anva\MailClientBundle\Entity\ImapMail;
use Anva\MailClientBundle\Entity\ImapContacts;

class ServiceController extends Controller
{	
    /**
	 * @Route("/service/listmailbox")
     */
    public function listMailboxAction(Request $request)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		// Fetch mailboxes from IMAP
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);		
		$imap_mailboxes = $imap_handler->listMailbox($imap_detail->address);
		
		// Fetch database mailboxes
		$db_mailboxes = $this->getDoctrine()->getRepository('MailClientBundle:ImapMailbox')->findBy(array("imapaccountId"=>$imap_detail->imapaccountId));
		
		// Database sync with IMAP mailboxes(*)
		foreach($db_mailboxes as $db_mailbox){ // Delete nonexist mailboxes
			$db_mailboxid = $db_mailbox->getId();
			foreach($imap_mailboxes as $imap_mailbox){
				if($db_mailbox->getUidValidity() == $imap_mailbox->uidValidity){
					$db_mailboxid = null;
					break;
				}
			}
			if(!is_null($db_mailboxid)){
				$manager = $this->getDoctrine()->getManager();
				$entity = $manager->getRepository('MailClientBundle:ImapMailbox')->findOneBy(array('id' => $db_mailboxid));
				$manager->remove($entity);
				$manager->flush();
				$manager->clear();
			}
		}
		
		foreach($imap_mailboxes as $imap_mailbox){ // Add/Update mailboxes
			$db_mailboxid = null;
			foreach($db_mailboxes as $db_mailbox){
				if($db_mailbox->getUidValidity() == $imap_mailbox->uidValidity){
					$db_mailboxid = $db_mailbox->getId();
					break;
				}
			}
			if(!is_null($db_mailboxid)){ // Update existing mailbox
				$manager = $this->getDoctrine()->getManager();
				$entity = $manager->getRepository('MailClientBundle:ImapMailbox')->findOneBy(array('id' => $db_mailboxid));
				$entity->setName($imap_mailbox->name);
				$entity->setAddress($imap_mailbox->address);
				$entity->setUnreadNo($imap_mailbox->unreadNo);
				$manager->flush();
				$manager->clear();
			}else{ // Add new mailbox
				$manager = $this->getDoctrine()->getManager();
				$entity = new ImapMailbox();
				$entity->setImapaccountId($imap_detail->imapaccountId);
				$entity->setUidValidity($imap_mailbox->uidValidity);
				$entity->setName($imap_mailbox->name);
				$entity->setAddress($imap_mailbox->address);
				$entity->setUnreadNo($imap_mailbox->unreadNo);
				$manager->persist($entity);	
				$manager->flush();
				$manager->clear();
			}
		}

		// Fetch database mailboxes
		$mailbox_list = $this->getDoctrine()->getRepository('MailClientBundle:ImapMailbox')->findBy(array('imapaccountId'=>$imap_detail->imapaccountId));
					
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$mailbox_data = $serializer->serialize($mailbox_list, 'json');
		
		$response = new Response($mailbox_data);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
     * @Route("/service/syncmails")
     */
    public function syncMailsAction(Request $request)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		// Fetch mailboxes from IMAP
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);		
		
		// Fetch database mailboxes
		$db_mailboxes = $this->getDoctrine()->getRepository('MailClientBundle:ImapMailbox')->findBy(array('imapaccountId'=>$imap_detail->imapaccountId));
		
		$since = date('j F Y', strtotime('-7 days'));
		$imap_maillist = array();
		$imap_counter = 0;
		foreach($db_mailboxes as $db_mailbox){
			$mailbox_maillist = $imap_handler->sinceMail(base64_decode($db_mailbox->getAddress()), $since);
			foreach($mailbox_maillist as $mailbox_mail){
				$imap_maillist[$imap_counter] = new \stdClass();
				$imap_maillist[$imap_counter]->mailboxId = $db_mailbox->getId();
				$imap_maillist[$imap_counter]->emailUid = $mailbox_mail->emailUid;
				$imap_maillist[$imap_counter]->mailFrom = $mailbox_mail->from;
				$imap_maillist[$imap_counter]->readStat = $mailbox_mail->readStat;
				$imap_maillist[$imap_counter]->timeStamp = $mailbox_mail->timeStamp;
				$imap_maillist[$imap_counter]->subject = $mailbox_mail->subject;
				$imap_maillist[$imap_counter]->body = $mailbox_mail->body;
				$imap_maillist[$imap_counter]->hasAttachment = $mailbox_mail->hasAttachment;
				$imap_maillist[$imap_counter]->flagged = $mailbox_mail->flagged;
				$imap_counter++;
			}
		}
		
		// Fetch database maillist
		$db_maillist = $this->getDoctrine()->getRepository('MailClientBundle:ImapMail')->findBy(array("imapaccountId"=>$imap_detail->imapaccountId));
		
		foreach($db_maillist as $db_mail){ // Delete nonexist mails
			$db_mailid = $db_mail->getId();
			foreach($imap_maillist as $imap_mail){
				if($db_mail->getMailboxId() == $imap_mail->mailboxId && $db_mail->getUid() == $imap_mail->emailUid){
					$db_mailid = null;
					break;
				}
			}
			if(!is_null($db_mailid)){
				$manager = $this->getDoctrine()->getManager();
				$entity = $manager->getRepository('MailClientBundle:ImapMail')->findOneBy(array('id' => $db_mailid));
				$manager->remove($entity);
				$manager->flush();
				$manager->clear();
			}
		}
		$manager = $this->getDoctrine()->getManager();
			
		foreach($imap_maillist as $imap_mail){ // Add/Update mails

			// Add or update contacts
			$contactName = (filter_var($imap_mail->mailFrom, FILTER_VALIDATE_EMAIL) === true?'':strip_tags($imap_mail->mailFrom));
			$pattern	=	"/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
 			preg_match_all($pattern, $imap_mail->mailFrom, $from_emails);
			
			if(isset($from_emails[0][0])){
				$contacts = $manager->getRepository('MailClientBundle:ImapContacts')->findBy(array('imapAccountId' =>$imap_detail->imapaccountId,'contactEmail'=>$from_emails[0][0]));
				if(count($contacts)==0){
					$imap_contact = new ImapContacts();
					$imap_contact->setImapAccountId($imap_detail->imapaccountId);
					$imap_contact->setContactName($contactName);
					$imap_contact->setContactEmail($from_emails[0][0]);

					$manager->persist($imap_contact);	
					$manager->flush();
					$manager->clear();
				}
			}			

			$db_mailid = null;
			foreach($db_maillist as $db_mail){
				if($db_mail->getMailboxId() == $imap_mail->mailboxId && $db_mail->getUid() == $imap_mail->emailUid){
					$db_mailid = $db_mail->getId();
					break;
				}
			}
			if(!is_null($db_mailid)){ // Update existing mails
				$entity = $manager->getRepository('MailClientBundle:ImapMail')->findOneBy(array('id' => $db_mailid));
				$entity->setMailFrom($imap_mail->mailFrom);
				$entity->setReadStat($imap_mail->readStat);
				$entity->setTimeStamp($imap_mail->timeStamp);
				$entity->setSubject($imap_mail->subject);
				$entity->setBody($imap_mail->body);
				$entity->setHasAttachment((count($imap_mail->hasAttachment)>0) ? true : false);
				$entity->setFlagged($imap_mail->flagged);
				
				$manager->flush();
				$manager->clear();


			}else{ // Add new mails
				$entity = new ImapMail();
				
				$entity->setImapaccountId($imap_detail->imapaccountId);
				$entity->setMailboxId($imap_mail->mailboxId);
				$entity->setUid($imap_mail->emailUid);
				$entity->setMailFrom($imap_mail->mailFrom);
				$entity->setReadStat($imap_mail->readStat);
				$entity->setTimeStamp($imap_mail->timeStamp);
				$entity->setSubject($imap_mail->subject);
				$entity->setBody($imap_mail->body);
				$entity->setHasAttachment((count($imap_mail->hasAttachment)>0) ? true : false);
				$entity->setFlagged($imap_mail->flagged);
				
				$manager->persist($entity);	
				$manager->flush();
				$manager->clear();
			}
		}
		
		$response = new Response(json_encode(array()));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
	 * @Route("/service/createmailbox")
     */
    public function createMailboxAction(Request $request)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		$post_data = json_decode($request->getContent());
		$data_set = array();
		// Create new mailbox
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		$data_set = $imap_handler->createMailbox($imap_detail->address, $post_data->mailboxName);
		
		$response = new Response(json_encode($data_set));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
	 * @Route("/service/listmail/{address}/{pageno}", defaults={"address"="null", "pageno"=1})
     */
    public function listMailAction(Request $request, $address, $pageno=1)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		$address = ($address == "null") ? $imap_detail->address : base64_decode($address);
		$data_set = array();
		
		// Here we need to check IMAP availability.
		// If IMAP is not available then we need to fetch them from database.
		// Otherwise we will fetch emails from IMAP.
		
		// Fetch IMAP mail list
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		$data_set = $imap_handler->listMail($address, $pageno, $imap_detail->itemPerpage);
		
		$response = new Response(json_encode($data_set));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }

    /**
	 * @Route("/service/searchmail/{address}/{searchParams}/{pageno}", defaults={"address"="inbox","pageno"=1})
     */
    public function searchMailAction(Request $request, $address,$searchParams,$pageno=1)
    {
    	$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		$address = ($address == "null") ? $imap_detail->address : base64_decode($address);
		$data_set = array();
		// Search mail list
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		$data_set = $imap_handler->searchMail($imap_detail->address,$searchParams,$pageno);

		$response = new Response(json_encode($data_set));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
	 * @Route("/service/deletemail/{address}/{mailuids}")
     */
    public function mailDeleteAction(Request $request, $address, $mailuids)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }
		
		$address = ($address == "null") ? $imap_detail->address : base64_decode($address);
		$data_set = array();
		// Delete multiple mail
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		$data_set = $imap_handler->mailDelete($address, json_decode($mailuids, true));

		$response = new Response(json_encode($data_set));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
	 * @Route("/service/changeflag/{address}/{mailuids}/{type}/{flag}")
     */
    public function changeFlagAction(Request $request, $address, $mailuids, $type, $flag)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }
		
    	$address = ($address == "null") ? $imap_detail->address : base64_decode($address);
		$data_set = array();
		
		// Change mail flag
		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		$data_set = $imap_handler->changeFlag($address, json_decode($mailuids, true), array("type"=>$type, "flag"=>$flag));

		$response = new Response(json_encode($data_set));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
	
	/**
	 * @Route("/service/fetchmail/{address}/{emailuid}")
     */
    public function fetchMailAction(Request $request, $address, $emailuid)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return false;
        }

		$address = ($address == "null") ? $imap_detail->address : base64_decode($address);		
		if(!empty($address) && !empty($emailuid)){
			$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
			$raw_data = $imap_handler->viewMail($address, $emailuid);
			$to = array();
			$cc =array();
			$bcc = array();
			$attachments = array();
			if(!empty($raw_data['to'])){
				$to_explode = explode(',',$raw_data['to']);
				
				foreach ($to_explode as $key => $value) {
					$to[] =array('email'=>$value,'name'=>$value,'id'=>''); 
				}
			}
			if(!empty($raw_data['cc'])){
				$cc_explode = explode(',',$raw_data['cc']);
				
				foreach ($cc_explode as $key => $value) {
					$cc[] =array('email'=>$value,'name'=>$value,'id'=>''); 
				}
			}
			if(!empty($raw_data['bcc'])){
				$bcc_explode = explode(',',$raw_data['bcc']);
				
				foreach ($bcc_explode as $key => $value) {
					$bcc[] =array('email'=>$value,'name'=>$value,'id'=>''); 
				}
			}
			
			$directory = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
			foreach ($raw_data['attachments'] as $key => $value) {
				$new_name = md5(uniqid())."_".$value['name'];

				file_put_contents($directory.'/'.$new_name,$value['attachment']);
				$attachments[]['response'] =array(
					'originalName'=>$value['name'],
					'tempName'=>$new_name,
					'tempUrl'=>$this->getRequest()->getUriForPath('/uploads/'.$new_name),
					'fileSize'=>number_format(ceil(filesize($directory.'/'.$new_name)/1024))
				); 
			}
			$data_set['uid'] = $emailuid;
			$data_set['subject'] = $raw_data['subject'];
			$data_set['body'] = $raw_data['body'];
			$data_set['timeStamp'] = $raw_data['time_stamp'];
			$data_set['from'] = $raw_data['from'];
			$data_set['raw_to'] = $raw_data['to'];
			$data_set['raw_cc'] = $raw_data['cc'];
			$data_set['raw_bcc'] = $raw_data['bcc'];
			$data_set['to'] = json_encode($to);
			$data_set['cc'] = json_encode($cc);
			$data_set['bcc'] = json_encode($bcc);
			$data_set['attachments'] = json_encode($attachments);
			$data_set['emails'] = json_encode(array('to'=>$to,'cc'=>$cc,'bcc'=>$bcc));
    	}
		return new JsonResponse($data_set);
    }
	
	/**
	 * @Route("service/sendmail")
     */
    public function sendMailAction(Request $request)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return $this->redirectToRoute('login');
        }

    	$mailDetails = json_decode($request->getContent(), true);
		$attchments = array();
		$directory = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
		$attchCount = 0;
		foreach($mailDetails["attachements"] as $attachment){			
			$fileName = $directory."/".$attachment;
			$fileHandle = fopen($fileName, "r");
			$contents = fread($fileHandle, filesize($fileName));
			fclose($fileHandle);
			
			$attchments[$attchCount] = new \stdClass();
			$attchments[$attchCount]->fileName = substr($attachment, 33);
			$attchments[$attchCount]->content = $contents;
			$attchCount++;
		}
		
		// Checking Draft or Send
		$manager = $this->getDoctrine()->getManager();
		$connection = $manager->getConnection();
		$statement = $connection->prepare("SELECT * FROM imap_mailboxes WHERE imapaccount_id = ".$imap_detail->imapaccountId." AND  name like '%Draft%' ");
		$statement->execute();
		$draftbox = $statement->fetch();
		if($mailDetails["sendType"]=='draft'){
			$address = base64_decode($draftbox["address"]);
		}else{
			$statement = $connection->prepare("SELECT * FROM imap_mailboxes WHERE imapaccount_id = ".$imap_detail->imapaccountId." AND  name like '%Sent%' ");
			$statement->execute();
			$sentbox = $statement->fetch();	
			$address = base64_decode($sentbox["address"]);
		}

		$imap_handler = new ImapClass($imap_detail->email, $imap_detail->password);
		if(!empty($mailDetails['uid'])){
			$mailUids[] = $mailDetails['uid'];
			$imap_handler->mailDelete(base64_decode($draftbox["address"]), $mailUids);
		}

		// Compose mail content
		$send_status = $imap_handler->composeMail($address, $mailDetails["sendType"], $mailDetails["to"], $mailDetails["cc"], $mailDetails["bcc"], $mailDetails["subject"], $mailDetails["body"], $attchments);
		$send_status["status"] = true;		
		
		return new JsonResponse($send_status);
    }
}

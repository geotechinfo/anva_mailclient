<?php
/*
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Jana
*/
namespace Anva\MailClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use Anva\MailClientBundle\Helper\UtilityHelper;
use Anva\MailClientBundle\Services\ImapClass;
use Anva\MailClientBundle\Entity\User;
use Anva\MailClientBundle\Entity\UserRepository;
use Anva\MailClientBundle\Entity\ImapAccount;
use Anva\MailClientBundle\Entity\ImapAccountRepository;
use Anva\MailClientBundle\Entity\ImapMailbox;
use Anva\MailClientBundle\Entity\ImapMailboxRepository;

class UserController extends Controller
{
    function __construct(){
        $this->session = new Session();
    }
	
	/**
     * @Route("/register", name="register")
     */
    public function registerAction()
    {		
        return $this->render('@MailClient/User/register.html.twig');
    }

    /**
     * @Route("/doregister", name="doregister")
     */
    public function doRegisterAction()
    {
        $params = $this->getRequest()->request->all();
        $user = new User();
		$user->setFirstname($params['firstname']);
		$user->setLastname($params['lastname']);
        $user->setUsername($params['username']);
        $user->setPassword(password_hash($params['password'],PASSWORD_BCRYPT));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('login');
    }
	
	/**
     * @Route("/", name="login")
     */
    public function loginAction(Request $request)
    {
		/*$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if($imap_detail){
            return $this->redirectToRoute('mailbox');
        }*/
		
        return $this->render('@MailClient/User/login.html.twig');
	}

    /**
     * @Route("/dologin", name="dologin")
     */
    public function dologinAction(Request $request)
    {
		$params['username'] = $request->request->get('username');
		$params['password'] = $request->request->get('password');

		$user= $this->getDoctrine()->getRepository('MailClientBundle:User')->attempt($params);

		if($user && $user->isEnabled()){
			$this->session->set('userid', $user->getId());
			$imap_account = $this->getDoctrine()->getRepository('MailClientBundle:ImapAccount')->findBy(array('userId'=>$user->getId()));
			if(count($imap_account)){
				return $this->redirectToRoute('mailbox');
			}else{
				return $this->redirectToRoute('settings');
			}
		}else{
			$this->addFlash('error', 'This is an invalid credential.');
			return $this->redirectToRoute('login');
		}
	}

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
		if($this->session->has('userid')){
            $this->session->remove('userid');
        }
        
        return $this->redirectToRoute('login');
	}

	/**
     * @Route("/settings", name="settings")
     */
    public function settingsAction(Request $request)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return $this->redirectToRoute('login');
        }
		
		$post_detail = $request->query->all();
		if(count($post_detail)>0){ $imap_detail = $post_detail; }

        return $this->render('@MailClient/User/settings.html.twig', array("imap_detail"=>$imap_detail));
	}

    /**
     * @Route("/savesettings", name="savesettings")
     */
    public function savesettingsAction()
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return $this->redirectToRoute('login');
        }
		
		$form_params = $this->getRequest()->request->all();
		// If details are not provided
		if($form_params['email'] == "" || $form_params['password'] == "" || $form_params['imapHost'] == "" || $form_params['imapPort'] == "" || $form_params['itemPerpage'] == ""){
			$this->addFlash('error', 'Please provide all IMAP details.');
			return $this->redirectToRoute('settings', ['test' => $form_params], 307);
		}
		
		$address = "{".$form_params['imapHost'].":".$form_params['imapPort']."/imap/ssl/novalidate-cert}";
		// Fetch imap account
		$imap_handler = new ImapClass($form_params['email'], $form_params['password']);
		$imap_account = $imap_handler->accountDetail($address);
		
		// If IMAP details are invalid
		if(!$imap_account){
			$this->addFlash('error', 'Please provide valid IMAP details.');
			return $this->redirectToRoute('settings', $form_params);
		}
		
		if($imap_detail->imapaccountId){ // Update IMAP settings
			$manager = $this->getDoctrine()->getManager();
			$entity = $manager->getRepository('MailClientBundle:ImapAccount')->findOneBy(array('id' => $imap_detail->imapaccountId));
			$entity->setFirstname($form_params['firstName']);
			$entity->setLastname($form_params['lastName']);
			$entity->setEmail($form_params['email']);
			$entity->setPassword($form_params['password']);
			$entity->setImapHost($form_params['imapHost']);
			$entity->setImapPort($form_params['imapPort']);
			$entity->setIsTls(isset($form_params['isTls'])?$form_params['isTls']:0);
			$entity->setItemPerpage($form_params['itemPerpage']);
			$manager->flush();
			$manager->clear();
		}else{ // Create IMAP settings
			$manager = $this->getDoctrine()->getManager();
			$entity = new ImapAccount();
			$entity->setUserId($imap_detail->userId);
			$entity->setFirstname($form_params['firstName']);
			$entity->setLastname($form_params['lastName']);
			$entity->setEmail($form_params['email']);
			$entity->setPassword($form_params['password']);
			$entity->setImapHost($form_params['imapHost']);
			$entity->setImapPort($form_params['imapPort']);
			$entity->setIsTls(isset($form_params['isTls'])?$form_params['isTls']:0);
			$entity->setItemPerpage($form_params['itemPerpage']);
			$manager->persist($entity);	
			$manager->flush();
			$manager->clear();
		}
		return $this->redirectToRoute('mailbox');
	}
}

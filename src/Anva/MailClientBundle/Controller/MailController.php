<?php
/*
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Brahma
*/
namespace Anva\MailClientBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Anva\MailClientBundle\Helper\UtilityHelper;
use Anva\MailClientBundle\Services\ImapClass;
use Anva\MailClientBundle\Entity\ImapMailbox;

class MailController extends Controller
{	
	/**
	 * @Route("/mailbox/{address}", defaults={"address"="inbox"}, name="mailbox")
     */
    public function mailboxAction(Request $request, $address)
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return $this->redirectToRoute('login');
        }
		
    	$is_redirect_to_compose = (strpos(base64_decode($address), 'draft')?1:0);
		$data_set = array("mailbox_address"=>$address,'is_redirect_to_compose'=>$is_redirect_to_compose);
        return $this->render('@MailClient/Mail/mailbox.html.twig', array("imap_detail"=>$imap_detail, "data_set"=>$data_set));
    }
		
	/**
	 * @Route("/upload", name="upload")
     */
    public function uploadAction(Request $request)
    {
    	$json=array();
		foreach($request->files as $uploadedFile) {
			$originalName = $uploadedFile->getClientOriginalName();
			$arr = explode('.', $originalName);
			$extension = end($arr);
			$directory = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
		    $fileName = md5(uniqid()).'_'.$originalName;
		    $file = $uploadedFile->move($directory, $fileName);
		    
		    $json[]=array('tempName'=>$fileName,'originalName'=>$originalName);
		}
		return new JsonResponse($json);
    }
}

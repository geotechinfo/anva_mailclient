<?php
/*
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Jana
*/
namespace Anva\MailClientBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Anva\MailClientBundle\Helper\UtilityHelper;
use Anva\MailClientBundle\Services\ImapClass;

class ContactController extends Controller
{
    /**
	 * @Route("/getcontact/{query}", name="getcontact")
     */
    public function addressBookAction(Request $request, $query="null")
    {
		$utility_helper = $this->get('utilityHelper');
		$imap_detail = $utility_helper->imap_detail();
        if(!$imap_detail){
            return $this->redirectToRoute('login');
        }

		$query = urldecode($query);
		$manager = $this->getDoctrine()->getManager();
		$connection = $manager->getConnection();
		$statement = $connection->prepare("SELECT * FROM contacts WHERE imap_account_id = ".$imap_detail->imapaccountId." AND  (contact_name like '%".$query."%' OR contact_email like '%".$query."%')");			
		$statement->execute();
		$ImapContacts = $statement->fetchAll();

		$json = array();
    	foreach ($ImapContacts as $k => $v) {    			
			$json[] = array(
						'name'=>$v['contact_name']."<".$v['contact_email'].">",
						'email'=>$v['contact_email'],
						'id'=>$v['imap_account_id'],
			);
		}
		return new JsonResponse($json);
    }
}

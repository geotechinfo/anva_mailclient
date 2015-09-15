<?php
/*
Purpose: IMAP class from PHP generic methods.
Version: 1.0
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Brahma
*/
namespace Anva\MailClientBundle\Services;

class ImapClass {
	private $username = null;
	private $password = null;
	private $encoding = 'UTF-8';
	
	public function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	// Connect Imap server
	private function _imapHandle($address){
		$imap_handle = \imap_open($address, $this->username, $this->password);
		/*if(!$imap_handle) {
			throw new \Exception('Connection error: ' . imap_last_error());
		}*/
		if($imap_handle) {
			return $imap_handle;
		}
		return false;
	}
	
	// Check has attachment
	private function _existAttachment($imap_stream, $email_number){ 
        $structure = imap_fetchstructure($imap_stream, $email_number);
		
		$attachments = array();
		if(isset($structure->parts) && count($structure->parts)){
            for($i = 0; $i < count($structure->parts); $i++){
                if($structure->parts[$i]->ifdparameters){
                    foreach($structure->parts[$i]->dparameters as $object) {
                        if(strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
                if($structure->parts[$i]->ifparameters){
                    foreach($structure->parts[$i]->parameters as $object) {
                        if(strtolower($object->attribute) == 'name'){
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
            }
		}
		return $attachments;
	}
	
	// Format mailbox name
	private function _mailboxName($address){
		// Remove  } charactars from name
        if (preg_match("/}/i", $address)) {
            $mailboxAddress = explode('}', $address);
        }
		
        // Remove the ] if it exists
        if (preg_match("/]/i", $address)) {
            $mailboxAddress = explode(']/', $address);
        }

        // Remove any slashes
        $mailboxName = @trim(stripslashes($mailboxAddress[1]));

        // Remove inbox from the name
        $mailboxName = str_replace('INBOX.', '', $mailboxName);
		
		if($mailboxName != ""){
			return $mailboxName;
		}else{
			return false;
		}
	}
	
	// Get mail body
	function _getBody($uid, $imap){
		$body = $this->_getPart($imap, $uid, "TEXT/HTML");
		if ($body == "") {
			$body = $this->_getPart($imap, $uid, "TEXT/PLAIN");
		}
		return $body;
	}

	// Get mail parts
	function _getPart($imap, $uid, $mimetype, $structure = false, $partNumber = false){
		if (!$structure) {
			$structure = imap_fetchstructure($imap, $uid, FT_UID);
		}
		if ($structure) {
			if ($mimetype == $this->_getMime($structure)) {
				if (!$partNumber) {
					$partNumber = 1;
				}
				$text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
				switch ($structure->encoding) {
					case 3:
						return imap_base64($text);
					case 4:
						return imap_qprint($text);
					default:
						return $text;
				}
			}

			if ($structure->type == 1) {
				foreach ($structure->parts as $index => $subStruct) {
					$prefix = "";
					if ($partNumber) {
						$prefix = $partNumber . ".";
					}
					$data = $this->_getPart($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
					if ($data) {
						return $data;
					}
				}
			}
		}
		return false;
	}

	// Get mime type
	private function _getMime($structure){
		$primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

		if ($structure->subtype) {
			return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
		}
		return "TEXT/PLAIN";
	}
	
	// Fetch has attachment
	private function _fetchAttachment($imap_stream, $email_number){
        $structure = imap_fetchstructure($imap_stream, $email_number);
		
		$attachments = array();
		if(isset($structure->parts) && count($structure->parts)){
            for($i = 0; $i < count($structure->parts); $i++){
                if($structure->parts[$i]->ifdparameters){
                    foreach($structure->parts[$i]->dparameters as $object) {
                        if(strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
                if($structure->parts[$i]->ifparameters){
                    foreach($structure->parts[$i]->parameters as $object) {
                        if(strtolower($object->attribute) == 'name'){
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
                if(isset($attachments[$i]) && $attachments[$i]['name'] != ""){
                    $attachments[$i]['attachment'] = imap_fetchbody($imap_stream, $email_number, $i+1);
                    if($structure->parts[$i]->encoding == 3){ 
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    }elseif($structure->parts[$i]->encoding == 4){ 
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }
		}
		return $attachments;
	}
	
	// Readable storage format
	function _formatBytes($bytes) {
		$base = log($bytes, 1024);
		$suffixes = array('', 'kb', 'MB', 'GB', 'TB');   
		return round(pow(1024, $base - floor($base)), 2) . $suffixes[floor($base)];
	} 
	
	// Fetch account info
	public function accountDetail($address){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			$quota_value = \imap_get_quotaroot($imap_handle, 'INBOX');
			$return_detail = new \stdClass();
			if (is_array($quota_value)) {
				$return_detail->usageQuota = $this->_formatBytes($quota_value['usage']*1024);
				$return_detail->limitQuota = $this->_formatBytes($quota_value['limit']*1024);
				return $return_detail;
			}
		}		
		return false;
	}
	
	// Fetch mailbox list
	public function listMailbox($address){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			$mail_boxes = \imap_getsubscribed($imap_handle, $address, "*");
			if($mail_boxes) {
				$return_boxes = array();
				$mailbox_count = 0;
				foreach($mail_boxes as $mail_box){
					$mailbox_name = $this->_mailboxName($mail_box->name);
					if($mailbox_name != false){
						$mailbox_detail = @imap_status($imap_handle, $mail_box->name, SA_UNSEEN+SA_UIDVALIDITY);
						if($mailbox_detail){
							$return_boxes[$mailbox_count] = new \stdClass();
							$return_boxes[$mailbox_count]->uidValidity = $mailbox_detail->uidvalidity;
							$return_boxes[$mailbox_count]->name = $mailbox_name;
							$return_boxes[$mailbox_count]->address = base64_encode($mail_box->name);
							$return_boxes[$mailbox_count]->unreadNo = $mailbox_detail->unseen;
							$mailbox_count++;
						}
					}
				}
				return $return_boxes;
			}
		}
		return false;
	}
	
	// Fetch maillist since
	public function sinceMail($address, $since){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			$search = 'SINCE "'.$since.'"';

			$imap_search = \imap_search($imap_handle, $search, SE_UID);
			if($imap_search != false && count($imap_search)>0){
				$imap_search = array_reverse($imap_search);

				$email_range = implode(',',$imap_search);
				$mail_list = \imap_fetch_overview($imap_handle, $email_range, FT_UID);	
				$return_list = array();
				if($mail_list) {
					$mailitem_count = 0;
					foreach($mail_list as $mail_item){
						$return_list[$mailitem_count] = new \stdClass();
						$return_list[$mailitem_count]->emailUid = $mail_item->uid;
						$return_list[$mailitem_count]->from = (!property_exists($mail_item, "from")) ? "" : $mail_item->from;
						$return_list[$mailitem_count]->readStat = ($mail_item->seen) ? "" : "unread";
						$return_list[$mailitem_count]->timeStamp = ($mail_item->udate*1000);
						$return_list[$mailitem_count]->hasAttachment = $this->_existAttachment($imap_handle, $mail_item->msgno);
						$return_list[$mailitem_count]->flagged = $mail_item->flagged;
						$return_list[$mailitem_count]->subject = (!property_exists($mail_item, "subject")) ? "" : $mail_item->subject;
						$return_list[$mailitem_count]->body = ""; // We need to put mail body here
						$mailitem_count++;
					}
				}
				return $return_list;
			}
			imap_close($imap_handle);
		}
		return array();
	}
	
	// Create new mailbox
	public function createMailbox($address, $mailboxName){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			$imap_create = @imap_createmailbox($imap_handle , imap_utf7_encode($address.$mailboxName)) or imap_errors();
			if($imap_create){
				return true;
			}
		}
		return false;
	}
	
	// Fetch mail list
	public function listMail($address, $page_no=1, $item_perpage=10){
		$imap_handle = $this->_imapHandle($address);
		$mailbox_name = ($this->_mailboxName($address) == false) ? "Inbox" : $this->_mailboxName($address);
		
		$imap_check = \imap_check($imap_handle);
        $email_total = $imap_check->Nmsgs;
		
		if($email_total == 0){
			return array(
				"mailboxName"=> $mailbox_name,
				"totalPage"=>0,
				"startEmail"=>0,
				"endEmail"=>0,
				"totalPage"=>0,
				"emailList"=>array()
			);
		}

		$total_page = ceil($email_total/$item_perpage);
		$page_no = ($page_no < 1 || $total_page < $page_no) ? 1 : $page_no;
		$email_end = $email_total-($item_perpage*($page_no-1));
		$email_start = ($email_end <= $item_perpage) ? 1 : $email_end-($item_perpage-1);
		$email_range = $email_start.":".$email_end;

		$mail_list = \imap_fetch_overview($imap_handle, $email_range, 0);
		usort($mail_list, function($a, $b) {
			return($b->udate-$a->udate);
		});
				
		$return_list = array();
		if(!$mail_list) {
			throw new \Exception('Connection error: ' . imap_last_error());
		}else{
			$mailitem_count = 0;
			foreach($mail_list as $mail_item){
				$return_list[$mailitem_count] = new \stdClass();
				$return_list[$mailitem_count]->emailUid = $mail_item->uid;
				$return_list[$mailitem_count]->from = $mail_item->from;
				$return_list[$mailitem_count]->readStat = ($mail_item->seen) ? "" : "unread";
				$return_list[$mailitem_count]->timeStamp = ($mail_item->udate*1000);
				$return_list[$mailitem_count]->subject = (!property_exists($mail_item, "subject")) ? "" : $mail_item->subject;
				$return_list[$mailitem_count]->hasAttachment = $this->_existAttachment($imap_handle, $mail_item->msgno);
				$return_list[$mailitem_count]->flagged = $mail_item->flagged;
				$mailitem_count++;
			}
		}
		
		return array(
			"mailboxName"=> $mailbox_name,
			"totalPage"=> $total_page,
			"startEmail"=> $email_total-$email_end+1,
			"endEmail"=> $email_total-($email_start-1),
			"emailList"=> $return_list
		);
	}
	
	// Search mail details
	public function searchMail($address, $searchString, $page_no=1, $item_perpage=10){
		$imap_handle = $this->_imapHandle($address);
		$search = 'SUBJECT "'.strtolower($searchString).'" BODY "'.strtolower($searchString).'"';
		$mailbox_name = ($this->_mailboxName($address) == false) ? "Inbox" : $this->_mailboxName($address);

		$imap_search = \imap_search($imap_handle, $search, SE_UID);
		$imap_search = array_reverse($imap_search);
		$email_total = count($imap_search);

		$total_page = ceil($email_total/$item_perpage);
		$page_no = ($page_no < 1 || $total_page < $page_no) ? 1 : $page_no;

		$email_start = ($page_no-1)*$item_perpage;
		$email_end = $email_start+($item_perpage-1);
		$range = range($email_start, $email_end);

		$email_ids = array();
		foreach ($range as $key => $value) {
			if(isset($imap_search[$value])){
				$email_ids[] = $imap_search[$value];
			}
			
		}
		$email_range = implode(',',$email_ids);
		$mail_list = \imap_fetch_overview($imap_handle, $email_range, FT_UID);
		usort($mail_list, function($a, $b) {
			return($b->udate-$a->udate);
		});
				
		$return_list = array();
		if(!$mail_list) {
			throw new \Exception('Connection error: ' . imap_last_error());
		}else{
			$mailitem_count = 0;
			foreach($mail_list as $mail_item){
				$return_list[$mailitem_count] = new \stdClass();
				$return_list[$mailitem_count]->emailUid = $mail_item->uid;
				$return_list[$mailitem_count]->from = $mail_item->from;
				$return_list[$mailitem_count]->readStat = ($mail_item->seen) ? "" : "unread";
				$return_list[$mailitem_count]->timeStamp = ($mail_item->udate*1000);
				$return_list[$mailitem_count]->subject = (!property_exists($mail_item, "subject")) ? "" : $mail_item->subject;
				$return_list[$mailitem_count]->hasAttachment = $this->_existAttachment($imap_handle, $mail_item->msgno);
				$return_list[$mailitem_count]->flagged = $mail_item->flagged;
				$mailitem_count++;
			}
		}
		return array(
			"mailboxName"=> $mailbox_name,
			"totalPage"=> $total_page,
			"startEmail"=> $email_start+1,
			"endEmail"=> $email_start+(count($email_ids)),
			"emailList"=> $return_list
		);
	}
	
	// Delete multiple mail
	public function mailDelete($address, $mailUids){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			/*
			\imap_expunge($imap_handle);
			\imap_close($imap_handle);
			foreach($mailUids as $mailuid){
				\imap_delete($imap_handle, $mailuid, FT_UID);
			}
			*/
			$imap_setflag = @imap_setflag_full($imap_handle, implode(",", $mailUids), "\\Deleted", FT_UID) or imap_errors();
			return true;
		}
		return false;
	}
	
	// Change mail flag
	public function changeFlag($address, $mailUids, $flagTerm){
		$imap_handle = $this->_imapHandle($address);
		if($imap_handle){
			if($flagTerm["type"]=="set"){
				$imap_setflag = @imap_setflag_full($imap_handle, implode(",", $mailUids), "\\".$flagTerm["flag"], ST_UID);
			}else{
				$imap_setflag = @imap_clearflag_full($imap_handle, implode(",", $mailUids), "\\".$flagTerm["flag"], ST_UID);
			}
			if($imap_setflag){
				return true;
			}
		}
		return false;
	}
		
	// Fetch mail detail
	public function viewMail($address, $emailuid){
		$imap_handle = $this->_imapHandle($address);

		$email_msgno = \imap_msgno($imap_handle, $emailuid);
		$mail_header = \imap_headerinfo($imap_handle, $email_msgno);
		return array(
			"subject"=> $mail_header->subject,
			"body"=> $this->_getBody($emailuid, $imap_handle),
			"time_stamp" => ($mail_header->udate*1000),
			"from"=> $mail_header->fromaddress,
			"to"=> isset($mail_header->toaddress)?$mail_header->toaddress:'',
			"cc"=> isset($mail_header->ccaddress)?$mail_header->ccaddress:'',
			"bcc"=> isset($mail_header->bccaddress)?$mail_header->bccaddress:'',
			"attachments"=> $this->_fetchAttachment($imap_handle, $email_msgno)
		);
	}
	
	// Compose mail content
	public function composeMail($address, $composeType, $to, $cc, $bcc, $subject, $content, $attachments){
		$imap_handle = $this->_imapHandle($address);
		
		$envelope["from"]= $this->username;
		$envelope["reply_to"]= $this->username;
		$envelope["subject"]  = $subject;
		
		$envelope["to"] = "";
		foreach($to as $email){
			$envelope["to"] .= ($envelope["to"] == "") ? $email["email"] : ",".$email["email"];
		}
		$envelope["cc"] = "";
		foreach($cc as $email){
			$envelope["cc"] .= ($envelope["cc"] == "") ? $email["email"] : ",".$email["email"];
		}
		$envelope["bcc"] = "";
		foreach($bcc as $email){
			$envelope["bcc"] .= ($envelope["bcc"] == "") ? $email["email"] : ",".$email["email"];
		}

		$body = array();
		$part["type"] = TYPEMULTIPART;
		$part["subtype"] = "mixed";
		array_push($body, $part);
		
		foreach($attachments as $attachment){
			$part["type"] = TYPEAPPLICATION;
			$part["encoding"] = ENCBINARY;
			$part["subtype"] = "octet-stream";
			$part['disposition.type'] = 'attachment';
			$part['disposition'] = array ('filename'=>$attachment->fileName);
			$part["description"] = $attachment->fileName;
			$part["contents.data"] = $attachment->content;
			array_push($body, $part);
		}

		$part["type"] = TYPETEXT;
		$part["subtype"] = "html";
		$part["disposition.type"] = "INLINE";
		$part["description"] = "Description";
		$part["contents.data"] = $content;
		array_push($body, $part);

		$mailData = imap_mail_compose($envelope, $body);
		if($composeType=='send'){
			//ini_set(sendmail_from, $this->username);
			//imap_mail($envelope["to"], $subject, $mailData);
			//ini_restore(sendmail_from);
			@mail($envelope["to"], $subject, $mailData);
		}		
		
		$append = imap_append($imap_handle, $address, $mailData, "\\Seen");
		$uid = false;
		if($append){
			$check = imap_check($imap_handle);
			$uid=imap_uid($imap_handle,$check->Nmsgs);
		}
		imap_close($imap_handle);
		
		return array(
			"lastUid"=> base64_encode($uid)
		);
	}
}
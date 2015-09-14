/*
Copyright: GeoTech InfoServices Pvt. Ltd.
Developer: Santanu Brahma & Santanu Jana
*/

// AngularJS configuration
var emailClient = angular.module('emailClient',['ngTagsInput', 'angularFileUpload'], function($interpolateProvider) {
	$interpolateProvider.startSymbol('[[');
	$interpolateProvider.endSymbol(']]');
	
});
emailClient.config(function($httpProvider) {
	$httpProvider.defaults.useXDomain = true;
	delete $httpProvider.defaults.headers.common['X-Requested-With'];
});

emailClient.factory('GlobalService', function() {
	return {
		to : [],
		cc : [],
		bcc : [],
		attachments : [],
		emails:[],
	};
});

emailClient.filter("sanitize", ['$sce', function($sce) {
	return function(htmlCode){
		return $sce.trustAsHtml(htmlCode);
	}
}]);

emailClient.controller('GreetingsController', ['$scope', function($scope) {
	$scope.greetings = 'Welcome to Anva Mail Client.';         
}]);

// Mail controller
emailClient.controller('MailController', ['$scope', '$http', 'GlobalService', function($scope, $http, GlobalService) {
	$scope.currentPage = "sync";
	$scope.messageType = false;
	$scope.messageText = null;
	
	// Sync with IMAP server
	$scope.syncEverything = function(isInit) {
		// Maintaining parallel task
		var doneOthers = 0;
		$scope.listMailbox(true).then(function(){
			doneOthers++;
			if(doneOthers==2){
				if(isInit==true){
					$scope.currentPage = "list";
				}
			}
			
			$http({
				url: 'service/syncmails',
				method: 'POST'
			}).success(function(data){
				window.setTimeout(function() {
					$scope.$apply(function() {
						$scope.syncEverything(false);
					});
				}, 50000);
			});
		});
		$scope.listMails(null, true).then(function(){
			doneOthers++;
			if(doneOthers==2){
				if(isInit==true){
					$scope.currentPage = "list";
				}
			}
		});
	};

	// Fetch mailbox list
	$scope.mailboxList = null;
	$scope.listMailbox = function(isSync) {
		return $http({
			url: 'service/listmailbox',
			method: (isSync==true) ? 'POST' : 'GET'
		}).success(function(data){
			$scope.mailboxList = data;
		});
	};
	
	// Create new mailbox
	$scope.mbCreateInit = false;
	$scope.mbCreateError = "null";
	$scope.createMailbox = function(mailboxName) {
		if(typeof(mailboxName) == "undefined" || mailboxName.length == 0){
			$scope.mbCreateError = "Please provide a folder name without any special characters.";
		}else{
			$scope.mbCreateInit = true;
			$http.post("service/createmailbox",
				{
					mailboxName: mailboxName
				}
				).then(
				function(successResponse){
					if(successResponse.data == true){
						$scope.listMailbox(false).then(function(){
							$scope.mbCreateInit = false;
							$scope.mbCreateError = "null";
							$('#createMailbox').modal('hide');
							//toastr.success('Folder has been created successfully.');
							$scope.messageType = "success";
							$scope.messageText = "Folder has been created successfully.";
						});
					}else{
						$scope.mbCreateInit = false;
						$scope.mbCreateError = "Folder creation failed or this name already exist.";
					}
				},
				function(errorResponse){
					
				}
			);
		}
	};		
	
	// Display mail list
	$scope.searchString = '';
	$scope.pageNo = 1;
	$scope.address = null;
	$scope.mailboxName = null;
	
	$scope.totalPage = 0;
	$scope.startEmail = 0;
	$scope.endEmail = 0;
	$scope.emailList = [];
	$scope.listMails = function(address, isSync){ // Display maillist for mailbox		
		$scope.searchString = '';
		if(address != null){
			$scope.address = address;
			$scope.pageNo = 1;
		}
		return $http({
			url: 'service/listmail/'+$scope.address+'/'+$scope.pageNo,
			method: (isSync==true) ? 'POST' : 'GET'
		}).success(function(data){
			$scope.mailboxName = data.mailboxName;
			$scope.totalPage = data.totalPage;
			$scope.startEmail = data.startEmail;
			$scope.endEmail = data.endEmail;
			$scope.emailList = data.emailList;
			
			if(isSync==false){
				$scope.currentPage = "list";
			}
		});
	};
	
	$scope.searchMails = function(searchString){ // Display maillist by search
		if(searchString != ''){
			$scope.searchString = searchString;
			$scope.pageNo =1;
		}
		return $http.get('service/searchmail/'+$scope.address+'/'+$scope.searchString+'/'+$scope.pageNo).success(function(data){
			$scope.currentPage = "list";
			$scope.mailboxName = data.mailboxName;
			$scope.totalPage = data.totalPage;
			$scope.startEmail = data.startEmail;
			$scope.endEmail = data.endEmail;
			$scope.emailList = data.emailList;
		});
	};

	$scope.reloadList = function() { // Reload mailist page
		if($scope.searchString == ''){
			return $scope.listMails(null, false);
		}else{
			return $scope.searchMails(null);
		}
	};
	$scope.nextList = function() { // Next mailist page
		if ($scope.pageNo < $scope.totalPage) {
			$scope.pageNo++;
			$scope.reloadList();
		}
	};
	$scope.previousList = function() { // Previous mailist page
		if ($scope.pageNo > 1) {
			$scope.pageNo--;
			$scope.reloadList();
		}
	};
	
	// Select multiple mails
	$scope.selectedMails=[];
	$scope.toggleSelection = function toggleSelection(mailId){
		var selectionid = $scope.selectedMails.indexOf(mailId);
		if(selectionid > -1){
			$scope.selectedMails.splice(selectionid, 1);
		}else{
			$scope.selectedMails.push(mailId);
		}
	};
	
	// Delete selected mails
	$scope.deleteEmail = function(){
		var selectedMails = encodeURI(JSON.stringify($scope.selectedMails));
		$http.get("service/deletemail/"+$scope.address+"/"+selectedMails,
			{	
			}
			).then(
			function(successResponse){
				if(successResponse.data == true){
					$scope.reloadList().then(function(){
						$scope.messageType = "success";
						$scope.messageText = "Selected mail(s) have been moved to the trash.";
					});
				}else{
					$scope.messageType = "error";
					$scope.messageText = "We are unable to delete selected mail(s).";
				}
			},
			function(errorResponse){

			}
		);
	};
	
	// Change mail flags
	$scope.changeFlag = function(type, flag){
		var selectedMails = encodeURI(JSON.stringify($scope.selectedMails));
		$http.get("service/changeflag/"+$scope.address+"/"+selectedMails+"/"+type+"/"+flag,
			{	
			}
			).then(
			function(successResponse){
				var readableText = "";
				if(type=="set" && flag=="Seen"){
					readableText = "read";
				}else if(type=="unset" && flag=="Seen"){
					readableText = "unread";
				}else if(type=="set" && flag=="Flagged"){
					readableText = "important";
				}else if(type=="unset" && flag=="Flagged"){
					readableText = "not important";
				}
				
				if(successResponse.data == true){
					$scope.reloadList().then(function(){
						$scope.messageType = "success";
						$scope.messageText = "Selected mail(s) have been marked as "+readableText+".";
					});
				}else{
					$scope.messageType = "success";
					$scope.messageText = "We are unable to change flag for selected mail(s).";
				}
			},
			function(errorResponse){

			}
		);
	}
	
	// Compose a new mail
	$scope.composeMail = function(){
		$scope.from = "";
		$scope.to = [];
		$scope.cc = [];
		$scope.bcc = [];
		$scope.subject = "";
		$scope.bodyHtml = "";
		$scope.timeStamp = "";
		GlobalService.to = [];
		GlobalService.cc = [];
		GlobalService.bcc = [];
		GlobalService.attachments = [];
		GlobalService.emails = [{to:[], cc:[], bcc:[]}];
		
		$scope.currentPage = "load";
		window.setTimeout(function() {
			$scope.$apply(function() {
				$scope.currentPage = "compose";
			});
		}, 500);
	};
	
	// View mail or edit draft
	$scope.from = '';
	$scope.timeStamp = '';
	$scope.to = '';
	$scope.cc = '';
	$scope.bcc = '';
	$scope.raw_to = '';
	$scope.raw_cc = '';
	$scope.raw_bcc = '';
	$scope.subject = '';
	$scope.bodyHtml = '';
	$scope.attachments = '';
	$scope.mailuid = '';
	$scope.btnDisabled  = false;
	
	$scope.viewMail = function(mailUid){		
		$scope.mailuid = mailUid;
		$http.get('service/fetchmail/'+$scope.address+"/"+mailUid)
		.then(
			function(successResponse){
				$scope.from = successResponse.data.from;
				$scope.to = successResponse.data.to;
				$scope.cc = successResponse.data.cc;
				$scope.bcc = successResponse.data.bcc;
				$scope.raw_to = successResponse.data.raw_to;
				$scope.raw_cc = successResponse.data.raw_cc;
				$scope.raw_bcc = successResponse.data.raw_bcc;
				$scope.subject = successResponse.data.subject;
				$scope.bodyHtml = successResponse.data.body;
				$scope.timeStamp = successResponse.data.timeStamp;
				
				GlobalService.to = $.parseJSON(successResponse.data.to);
				GlobalService.cc = $.parseJSON(successResponse.data.cc);
				GlobalService.bcc = $.parseJSON(successResponse.data.bcc);
				GlobalService.attachments = $.parseJSON(successResponse.data.attachments);
				GlobalService.emails = $.parseJSON(successResponse.data.emails);

				if($scope.mailboxName.match(/draft/gi)!=null){
					$scope.currentPage = "compose";
				}else{
					$scope.currentPage = "view";
				}
			},
			function(errorResponse){}
		);
	};
	
	// Send a mail
	$scope.emailSend = function(sendType){
		$scope.btnDisabled = true;
		var files = [];
		angular.forEach(GlobalService.attachments, function(value, key) {
			files = files.concat(value.response.tempName);
		});

		$http.post('service/sendmail',
			{
				uid:$scope.mailuid,
				sendType: sendType,	// type can be draft or send
				to: GlobalService.to,
				cc: GlobalService.cc,
				bcc: GlobalService.bcc,
				subject: $scope.subject,
				body: $('.summernote').code(),
				attachements: files
			}
		).then(
			function(successResponse) {
				$scope.uid = successResponse.data.lastUid;
				$scope.btnDisabled = false;
				
				$scope.listMails(null, false).then(function(){
					if(sendType=="draft"){
						toastr.success('Mail successfully saved in Draft.');
					}else if(sendType=="send"){
						toastr.success('Mail successfully sent.');
						$scope.messageType = "success";
						$scope.messageText = "Selected mail(s) have been moved to the trash.";
					}else{
					
					}
				})
			}, 
			function(errorResponse) {
				
			}
		);
	}
	
	// Discard a draft item
	$scope.discardEmail = function(){
		$http.get("service/deletemail",
			{
				address: $scope.address,
				emailids: [$scope.encodedUid]
			}
			).then(
			function(successResponse){
				
			},
			function(errorResponse){

			}
		);
	};

	//Reply mail
	$scope.replyEmail = function(){
		$scope.subject = "Re:"+$scope.subject;
		$scope.bodyHtml = "<br/><br/><hr/><hr/>"+$scope.bodyHtml;					
		$scope.to = $scope.from;					
		GlobalService.to = [{email:$scope.from,name:$scope.from,id:''}];
		GlobalService.emails.to = GlobalService.to;
		$scope.currentPage = "compose";
	}

	//Forword mail
	$scope.forwordEmail = function(){
		$scope.subject = "Fwd:"+$scope.subject;
		$scope.bodyHtml = $scope.bodyHtml;					
		$scope.to = [];					
		$scope.cc = [];					
		$scope.bcc = [];					
		GlobalService.emails.to = [];
		GlobalService.emails.cc = [];
		GlobalService.emails.cbb = [];
		$scope.currentPage = "compose";
	}
}]);

// Contact controller
emailClient.controller('ContactController', ['$scope', '$http','GlobalService',function($scope, $http,GlobalService) {
	$scope.tags = GlobalService.emails;
	$scope.to = [];
	$scope.loadTags = function(query) {
		return $http.post('getcontact/'+encodeURIComponent(query));
	};
	//$scope.loadTags();

	$scope.tagAdded = function(tag,email_type) {
		if(!tag.email){
			result = tag.name.match(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/ig);
			tag.email = (result?result[0]:null);
		}

		if(email_type=="to"){
			GlobalService.to = GlobalService.to.concat(tag);
		}
		if(email_type=="cc"){
			GlobalService.cc = GlobalService.cc.concat(tag);
		}
		if(email_type=="bcc"){
			GlobalService.bcc = GlobalService.bcc.concat(tag);
		}
	};

	$scope.tagRemoved = function(tag,email_type) {
		var email='';
		if(!tag.email){
			result = tag.name.match(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/ig);
			email = (result)?result[0]:null;
		}else{
			email = tag.email;
		}

		if(email_type=="to"){
			angular.forEach(GlobalService.to, function(value, key) {
				if(value.email==email){
					GlobalService.to.splice(key, 1);
				}
			});
		}
		if(email_type=="cc"){
			angular.forEach(GlobalService.cc, function(value, key) {
				if(value.email==email){
					GlobalService.cc.splice(key, 1);
				}
			});
		}
		if(email_type=="bcc"){
			angular.forEach(GlobalService.bcc, function(value, key) {
				if(value.email==email){
					GlobalService.bcc.splice(key, 1);
				}
			});
		}
	};
}]);

// Upload controller
emailClient.controller('UploadController', ['$scope', '$http', 'GlobalService', 'FileUploader', function($scope, $http, GlobalService, FileUploader) {
	var uploader = $scope.uploader = new FileUploader({
		url: "{{ url('upload') }}",
		autoUpload:true,
		queueLimit :20
	});

	$scope.uploader.uploadedFiles =  GlobalService.attachments
	// FILTERS
	uploader.filters.push({
		name: 'customFilter',
		fn: function(item /*{File|FileLikeObject}*/, options) {
			return this.queue.length < 15;
		}
	});

	// CALLBACKS
	uploader.onWhenAddingFileFailed = function(item /*{File|FileLikeObject}*/, filter, options) {
		//console.info('onWhenAddingFileFailed', item, filter, options);
	};
	uploader.onAfterAddingFile = function(fileItem) {
		//console.info('onAfterAddingFile', fileItem);
	};
	uploader.onAfterAddingAll = function(addedFileItems) {
		//console.info('onAfterAddingAll', addedFileItems);
	};
	uploader.onBeforeUploadItem = function(item) {
		//console.info('onBeforeUploadItem', item);
	};
	uploader.onProgressItem = function(fileItem, progress) {
		//console.info('onProgressItem', fileItem, progress);
	};
	uploader.onProgressAll = function(progress) {
		//console.info('onProgressAll', progress);
	};
	uploader.onSuccessItem = function(fileItem, response, status, headers) {
		//console.info('onSuccessItem', fileItem, response, status, headers);
	};
	uploader.onErrorItem = function(fileItem, response, status, headers) {
		//console.info('onErrorItem', fileItem, response, status, headers);
	};
	uploader.onCancelItem = function(fileItem, response, status, headers) {
		//console.info('onCancelItem', fileItem, response, status, headers);
	};
	uploader.onCompleteItem = function(fileItem, response, status, headers) {
		var attachment = [{'fileItem':fileItem,'response':response[0]}];
		GlobalService.attachments = GlobalService.attachments.concat(attachment);
	};
	uploader.onCompleteAll = function() {
		
	};

	uploader.onItemRemove = function(fileItem){
		fileItem.remove();
		angular.forEach(GlobalService.attachments, function(value, key) {
			if(value.fileItem === fileItem ){
				GlobalService.attachments.splice(key, 1);
			}
		});
	}

	uploader.removeUploadedItem = function(fileItem){
		$('#uploadedFilesContainer a[href="'+fileItem.response.tempUrl+'"]').closest('.alert').remove();
		angular.forEach(GlobalService.attachments, function(value, key) {
			if(value.response.tempName == fileItem.response.tempName){
				GlobalService.attachments.splice(key, 1);
			}
		});
	}

	uploader.onRemoveAllItems = function(){
		uploader.clearQueue();
		GlobalService.attachments=[];
	}
}]);
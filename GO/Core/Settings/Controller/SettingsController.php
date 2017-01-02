<?php
namespace GO\Core\Settings\Controller;

use GO\Core\Controller;
use GO\Core\Email\Model\Message;

class SettingsController extends Controller {
	
	public function actionRead(){		
		$this->renderModel(GO()->getSettings());
	}

	public function actionUpdate() {

		$settings = GO()->getSettings();

		$settings->setValues(GO()->getRequest()->body['data']);
		$settings->save();

		$this->renderModel($settings);
	}
	
	public function actionTestSmtp(){
		
		$message = new Message(
						GO()->getSettings()->smtpAccount, 
						"Test message from ".GO()->getConfig()->productName, 
						"If you received this message your SMTP account is working!");
		
		$message->setTo(GO()->getSettings()->smtpAccount->fromEmail, GO()->getSettings()->smtpAccount->fromName);
		
		$numberOfRecipients = $message->send();
		
		$this->render(['success' => $numberOfRecipients === 1]);
		
	}
}

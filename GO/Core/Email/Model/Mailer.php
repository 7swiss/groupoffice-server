<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GO\Core\Email\Model;

/**
 * App component to write new system emails
 *
 * @author mdhart
 */
class Mailer {

	public function compose() {

		return new Message(GO()->getSettings()->smtpAccount);
	}
}

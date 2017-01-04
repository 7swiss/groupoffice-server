<?php

namespace GO\Modules\GroupOffice\Imap\Model;

use Exception;
use IFW;
use IFW\Imap\Mailbox;
use IFW\Orm\Record;
use IFW\Util\ArrayUtil;
use GO\Modules\GroupOffice\Messages\Model\Message as MessagesMessage;
/**
 * The Folder model
 *

 * @property int $highestSyncedUid
 * 
 * @property Account $account
 * @property Folder[] $folders
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Folder extends Record {

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $parentFolderId;

	/**
	 * 
	 * @var int
	 */							
	public $accountId;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * Paths are delimited by this char. Usually a "/: or ".". INBOX/Test for example.
	 * @var string
	 */							
	public $delimiter;

	/**
	 * When changed the uid's must be resynchronized with IMAP

https://tools.ietf.org/html/rfc3501#section-2.3.1.1
	 * @var int
	 */							
	public $uidValidity;

	/**
	 * See: https://tools.ietf.org/html/rfc4551#section-3.1.1
	 * @var int
	 */							
	public $highestModSeq;

	/**
	 * Used for sorting. prioriy first and then name. Used for putting inbox, trash, sent and drafts on top.
	 * @var int
	 */							
	public $priority = 10;

	private $imapMailbox;


	protected static function defineRelations() {

		self::hasMany('folders', Folder::class, ['id' => 'parentFolderId']);
		self::hasOne('account', Account::class, ['accountId' => 'id']);
		self::hasMany('messages', Message::class, ['id' => 'folderId']);

		parent::defineRelations();
	}
	
	protected static function internalGetPermissions() {
		return new IFW\Auth\Permissions\ViaRelation('account');
	}

	/**
	 * 
	 * @return int Number of new messages
	 * @throws Exception
	 */
	public function sync() {
		
//		echo "Sync folder: ".$this->name."\n";
		
		GO()->getProcess()->setProgress(null);
		GO()->debug("Sync new ".$this->name);
		$count = $this->syncNewFromImap();
		
		GO()->getProcess()->setProgress(null);
		GO()->debug("Sync updates ".$this->name);
		$this->syncUpdatesFromImap();
		
		GO()->getProcess()->setProgress(null);
		GO()->debug("Sync deletes ".$this->name);
		$this->syncDeletesFromImap();
		
		GO()->getProcess()->setProgress(null);
		
		GO()->debug("Sync updates to imap ".$this->name);
		$this->syncUpdatesToImap();		
		
		GO()->debug("Sync deletes to imap ".$this->name);
		$this->syncDeletesToImap();		
		
		GO()->getProcess()->setProgress(null);
		//the highest modseq will be updated so we'll only update messages newer since now next sync
		$status = $this->getImapMailbox()->getStatus(['highestmodseq']);					
		$this->highestModSeq = $status['highestmodseq'];
		
		if (!$this->save()) {
			throw new \Exception("Could not save folder '".$this->name."'");
		}
		
		return $count;
	}
	
	/**
	 * Generate tag models from the folder name
	 * 
	 * It strips of all special folder names like INBOX, Sent, Trash etc. and uses
	 * any subfolder name as tag.
	 * 
	 * @return \GO\Core\Tags\Model\Tag
	 */
	public function toTags() {
		
		//ignore standard folders
		$name = trim(str_replace([
				'INBOX',
				$this->account->sentFolder,
				$this->account->draftsFolder,
				$this->account->trashFolder,
				$this->account->actionedFolder,
				$this->account->spamFolder,
				'Junk'
				], '', $this->name), $this->delimiter);
		
		if(empty($name)) {
			return [];
		}
		
		$tagNames =  explode($this->delimiter, $name);
		
		$tags = [];
		foreach($tagNames as $tagName) {
			$tag = \GO\Core\Tags\Model\Tag::find(['name'=>$tagName])->single();
			if(!$tag) {
				$tag = new \GO\Core\Tags\Model\Tag();
				$tag->name = $tagName;
			}
			
			$tags[] = $tag;
		}
		
		return $tags;
	}
	
	
	/**
	 * Get the message type for this folder
	 * 
	 * @todo make this user configurable
	 * @return int
	 */
	public function getMessageType() {
		
		$name = str_replace('INBOX'.$this->delimiter, '', $this->name);
		
		if(strpos($name, $this->account->sentFolder) === 0) {
			return MessagesMessage::TYPE_SENT;
		}
			
		if(strpos($name, $this->account->draftsFolder) === 0) {
			return MessagesMessage::TYPE_DRAFT;
		}
		
		if(strpos($name, $this->account->spamFolder) === 0 || strpos($name, 'Junk') === 0) {
			return MessagesMessage::TYPE_JUNK;
		}
		
		if(strpos($name, $this->account->trashFolder) === 0) {
			return MessagesMessage::TYPE_TRASH;
		}
		
		if(strpos($name, $this->account->actionedFolder) === 0) {
			return MessagesMessage::TYPE_ACTIONED;
		}
		
		return MessagesMessage::TYPE_INCOMING;		
	}	
	

	private function syncNewFromImap() {
		
		$count = 0;
		if (!$this->getImapMailbox()) {
			throw new Exception("IMAP mailbox doesn't exist after folder sync for '".$this->name."'");
		}

		if ($this->getImapMailbox()->noSelect) {
			GO()->debug("Folder '".$this->name."' is not selectable");
			return true;
		}
		
		if(!$this->getImapMailbox()->select()) {
			throw new Exception("Could not select IMAP mailbox '".$this->name.'"');
		}
		
//		GO()->getDebugger()->enabled = true;

		$uids = $this->diffUids();

		//limit to avoid too long imap command error		
		$chunks = array_chunk($uids, 1000);		
		while($subset = array_shift($chunks)){		
		
			$messages = $this->getImapMailbox()->getMessagesUnsorted($subset);

			while ($imapMessage = array_shift($messages)) {
				
				
				GO()->debug('Creating local '.$imapMessage->uid.' ' .$imapMessage->subject);

				/* @var $imapMessage IFW\Imap\Message */

				//Find existing cached message. It may not have an imapUid already. 
				//In some cases there can be duplicate MessageId values when you send a mail to yourself for example.					
				$message = Message::find(['imapUid' => $imapMessage->uid, 'folderId' => $this->id])->single();
				if (!$message) {
					$message = new Message();
					$message->imapUid = $imapMessage->uid;
					$message->folderId = $this->id;					
					$message->inReplyToUuid = $imapMessage->inReplyTo;
					
					//put in own message ID as well so it's easier for threading later on
					$refs = $imapMessage->references;
					if(!empty($imapMessage->messageId) && !in_array($imapMessage->messageId, $refs))
						$refs[] = $imapMessage->messageId;
					
					if(!empty($imapMessage->inReplyTo) && !in_array($imapMessage->inReplyTo, $refs))
						$refs[] = $imapMessage->inReplyTo;
					
					foreach($refs as $uuid) {
						$tr = new MessageReference();
						$tr->uuid = $uuid;
						$message->references[] = $tr;
					}
				}

				try{					
					$message->applyMessage($imapMessage);					
										
					if (!$message->save()) {
						throw new Exception("Failed to save message: ".var_export($message->getValidationErrors(), true));
					}				
		
					$count++;					
					
					GO()->getProcess()->setProgress(null);

				} catch(Exception $e) {
					GO()->debug("Failed to sync ".$imapMessage->messageId." ".$imapMessage->subject.' '.$imapMessage->internaldate->format('c'));

					throw $e;
				}
			}
		}

		return $count;
	}
	

	private function syncUpdatesFromImap() {
		$messages = $this->getImapMailbox()->getMessagesUnsorted('1:*', ['FLAGS'], $this->highestModSeq);

		if (!empty($messages)) {

			foreach ($messages as $imapMessage) {
				
				GO()->debug('Updating local '.$imapMessage->uid.' ' .$imapMessage->subject);
				
				$message = Message::find(['imapUid' => $imapMessage->uid, 'folderId' => $this->id])->single();
				if (!$message) {
					//can happen on a just arrived message
					GO()->debug("Message with uid " . $imapMessage->uid . " not found in folder " . $this->name, 'sync');
					continue;
				}
				
				$message->updateMessage($imapMessage);
				
				$thread = $message->message->thread;		
				
				if(isset($thread)) {
					$thread->tags = $this->toTags();
				}
				
				if (!$message->save()) {
					throw new Exception("Failed to save message");
				}
				
				GO()->getProcess()->setProgress(null);				
				
			}
		}

	


		return true;
	}

	/**
	 * Get the IMAP mailbox
	 * 
	 * @return Mailbox
	 */
	public function getImapMailbox() {
		if (!isset($this->imapMailbox)) {

			if (!$this->account) {
				throw new Exception("Could not find account!");
			}

			$this->imapMailbox = Mailbox::findByName($this->account->connect(), $this->name);
		}

		return $this->imapMailbox;
	}

	/**
	 * Get the highest UID present in the database
	 * 
	 * @return int
	 */
	public function getHighestSyncedUid() {
		$store = clone $this->messages;
		$store->getQuery()->select('max(imapUid) AS highestSyncedUid')->fetchMode(\PDO::FETCH_COLUMN, 0);
		return (int) $store->single();
	}

	/**
	 * Get the lowest UID present in the database
	 * 
	 * @return int
	 */
	public function getLowestSyncedUid() {

		$store = clone $this->messages;
		$store->getQuery()->select('min(imapUid) AS lowestSyncedUid')->fetchMode(\PDO::FETCH_COLUMN, 0);
		return (int) $store->single();

	}
	
	
	private function findAllUidsFromImap() {
		return $this->getImapMailbox()->search();
	}

	private function diffUids() {



		//Get all UID's and get the ones higher than our highest synced uid and lower
		//than our lowest synced uid.		
		$lowestSyncedUid = $this->getLowestSyncedUid();
		$highestSyncedUid = $this->getHighestSyncedUid();

//		GO()->debug("UID's in db for " . $this->name . ": " . $lowestSyncedUid . ':' . $highestSyncedUid, 'sync');

		$allUids = $fetchUids = $this->findAllUidsFromImap();
		if ($allUids === false) {
//			GO()->debug("Could not fetch UID's of folder ".$this->name);
			return [];
		}

		if ($lowestSyncedUid) {

			$arrayObject = new ArrayUtil($fetchUids);

			//chop off allready synced uids			
			$startKey = $arrayObject->findKey(function($uid) use ($lowestSyncedUid) {
				return $uid <= $lowestSyncedUid;
			});

			if (!$startKey) {
				$startKey = 0;
			}

			$endKey = $arrayObject->findKey(function($uid) use ($highestSyncedUid) {
				return $uid >= $highestSyncedUid;
			});

			if (!$endKey) {
				//highest not found. Then we must have everything and our latest will be removed.
				$endKey = count($fetchUids);
			}
			array_splice($fetchUids, $startKey, $endKey - $startKey + 1);
		}

		$fetchUids = array_reverse($fetchUids);

		//uid search always returns the latest UID
		if (empty($fetchUids)) {

			//double check if we have all UID's
			$dbUids = $this->findAllUidsFromDb();
//			GO()->debug("DB UID's: " . var_export($dbUids, true));
			$fetchUids = array_diff($allUids, $dbUids);

			if (empty($fetchUids)) {
				return [];
			} else {
				GO()->debug("Fetched ".count($fetchUids)." missing UID's");
			}
		}

//		if (count($fetchUids) > self::$maxSyncMessages) {

//			$slice = array_slice($fetchUids, 0, self::$maxSyncMessages);
//			return $slice;
//		} else {
//			$this->syncComplete = true;

			return $fetchUids;
//		}
	}
	
	/**
	 * Get all IMAP UID's in the database
	 * 
	 * @return array
	 */
	public function findAllUidsFromDb() {
		
		$messagesData = clone $this->messages;
		$messagesData->getQuery()->select('imapUid')->fetchMode(\PDO::FETCH_COLUMN, 0);
		
		return $messagesData->all();
	}
	
	
	private function syncDeletesFromImap() {
		$dbUids = $this->findAllUidsFromDb();
		
		$imapUids = $this->findAllUidsFromImap();

		$diff = array_diff($dbUids, $imapUids);

		if(!empty($diff)) {

			foreach ($diff as $uidToDelete) {
				
				GO()->debug('Deleting local '.$uidToDelete);
				
				$message = Message::find([
						'folderId' => $this->id, 
						'imapUid' => $uidToDelete])->single();
				if($message) {
					if($message->message) {
						$message->message->delete();
					}
					$message->delete();
				}
				
				GO()->getProcess()->setProgress(null);
			}
		}
	}
	
	
	
	private function syncUpdatesToImap() {
		$messages = MessagesMessage::find(
						(new IFW\Orm\Query())
						->joinRelation('imapMessage', true)
						->where('imapMessage.syncedAt<t.modifiedAt')
						->andWhere(['imapMessage.folderId'=>$this->id])
					
		);
		
		$conn = $this->account->connect();
		
		
		$imapMailbox = Mailbox::findByName($conn, $this->name);
		
		
//		$conn->debug = true;
		
		$folderType = $this->getMessageType();
		
		foreach($messages as $message) {
			
			$imapMessage = $imapMailbox->getMessage($message->imapMessage->imapUid, false, ['FLAGS']);
			
			GO()->debug("Modified message: ".$imapMessage->uid." in mailbox : ".$imapMailbox->getName().' '.$message->imapMessage->syncedAt->format('c').' < '.$message->modifiedAt->format('c'));
			
			$flags = $this->diffFlags($imapMessage, $message, false);
			if(!empty($flags)) {
				if(!$imapMailbox->setFlags($imapMessage->uid, $flags, false)) {
					throw new Exception("Could not set flags on ".$imapMessage->uid." in mailbox : ".$imapMailbox->getName());
				}
			}
			
			$flags = $this->diffFlags($imapMessage, $message, true);
			if(!empty($flags)) {
				if(!$imapMailbox->setFlags($imapMessage->uid, $flags, true)){
					throw new Exception("Could not set flags on ".$imapMessage->uid." in mailbox : ".$imapMailbox->getName());
				}
			}
			
			if($message->type != $folderType) {
				$folder = $this->account->getFolderForType($message->type);
				
				if(isset($folder)) {				
					
					$newUids = $imapMailbox->moveMessages($message->imapMessage->imapUid, $folder->name);
					if(!$newUids) {
						throw new \Exception("Failed to move message from ".$imapMailbox->getName().' to '.$folder->name);
					}
					
					//Adjust  ImapMessage model for sync.
					$newUid = array_shift($newUids);
					
					$message->imapMessage->folderId=$folder->id;
					$message->imapMessage->imapUid = $newUid;
					if(!$message->imapMessage->save()) {
						throw new \Exception("Could not save message");
					}					
				}
			}			
			
			$message->imapMessage->syncedAt = new \DateTime();		
			
			if(!$message->imapMessage->save()){
				throw new \Exception("Could not save ".$message->getClassName().'::'.$message->id);
			}
			
			GO()->getProcess()->setProgress(null);
		}
		
//		$conn->debug = false;

	}

	private function diffFlags(\IFW\Imap\Message $imapMessage, MessagesMessage $message, $findToClear = false) {
		
		$flags = [];
		if($imapMessage->flagged != $message->flagged && $message->flagged == !$findToClear) {
			$flags[] = '\\Flagged';
		}
		
		if($imapMessage->seen != $message->seen && $message->seen == !$findToClear) {
			$flags[] = '\\Seen';
		}
		
		if($imapMessage->answered != $message->answered && $message->answered == !$findToClear) {
			$flags[] = '\\Answered';
		}
		
		if($imapMessage->forwarded != $message->forwarded && $message->forwarded == !$findToClear) {
			$flags[] = '$Forwarded';
		}

		
		return $flags;
	}
	
	private function syncDeletesToImap() {
		$deletedMessages = Message::find(
						(new IFW\Orm\Query())
						->joinRelation('message', false, 'LEFT')
						->andWhere(['folderId'=>$this->id, 'message.id'=>null])												
						)->all();
		
		$uids = [];
		foreach($deletedMessages as $message) {
			$uids[] = $message->imapUid;
		}
		
		
		if($this->getImapMailbox()->deleteMessages($uids)) {
			foreach($deletedMessages as $message) {
				$message->delete();
			}
		}
	}

}

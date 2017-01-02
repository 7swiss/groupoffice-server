<?php

namespace GO\Modules\GroupOffice\Announcements;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Announcements\Controller\AnnouncementThumbController;

class Module extends InstallableModule {
	
	const ACTION_CREATE_ANNOUNCEMENTS = "createAnnouncements";

	public function routes() {
		
		Controller\AnnouncementController::routes()
				->get('announcements', 'store')
				->get('announcements/0','new')
				->get('announcements/:announcementId','read')
				->put('announcements/:announcementId', 'update')
				->post('announcements', 'create')
				->delete('announcements/:announcementId','delete');
		
		AnnouncementThumbController::routes()
				->get('announcements/:announcementId/thumb', 'download');
	}

}

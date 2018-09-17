<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of deleteProjectActivityAction
 *
 * @author orangehrm
 */
class deleteProjectActivityAction extends baseAdminAction {

	private $projectService;

	public function getProjectService() {
		if (is_null($this->projectService)) {
			$this->projectService = new ProjectService();
			$this->projectService->setProjectDao(new ProjectDao());
		}
		return $this->projectService;
	}

	/**
	 *
	 * @param <type> $request
	 */
	public function execute($request) {
		$toBeDeletedActivityIds = $request->getParameter('chkSelectRow');
		$projectId = $request->getParameter('projectId');
		$form = new DefaultListForm();
        $form->bind($request->getParameter($form->getName()));
        if ($form->isValid()) {
            if (!empty($toBeDeletedActivityIds)) {
                $delete = true;
                foreach ($toBeDeletedActivityIds as $toBeDeletedActivityId) {
                    $deletable = $this->getProjectService()->hasActivityGotTimesheetItems($toBeDeletedActivityId);
                    if ($deletable) {
                        $delete = false;
                        break;
                    }
                }
                if ($delete) {
                    foreach ($toBeDeletedActivityIds as $toBeDeletedActivityId) {

                        $customer = $this->getProjectService()->deleteProjectActivities($toBeDeletedActivityId);
                    }
                    $this->getUser()->setFlash('success', __(TopLevelMessages::DELETE_SUCCESS));
                } else {
                    $this->getUser()->setFlash('error', __('Not Allowed to Delete Project Activites Which Have Time Logged Against'));
                }
            }
        } else {
            $this->handleBadRequest();
            $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
        }
        $this->redirect('admin/saveProject?projectId=' . $projectId . '#ProjectActivities');
	}

}

?>

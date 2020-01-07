<?php

namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Builds an example page.
 */
class LocalAccessController {

	/**
	* Checks access for a specific request.
	*
	* @param \Drupal\Core\Session\AccountInterface $account
	*   Run access checks for this account.
	*
	* @return \Drupal\Core\Access\AccessResultInterface
	*   The access result.
	*/
	public function access(AccountInterface $account) {
		// Check permissions and combine that with any custom access checking needed. Pass forward
		// parameters from the route and/or request as needed.
		//return AccessResult::allowedIf($account->hasPermission('do example things') && $this->someOtherCustomCondition());
		
		//echo json_encode($_SERVER['REQUEST_METHOD']);
		return AccessResult::allowedIf($_SERVER['REQUEST_METHOD'] == "POST");
	}

}
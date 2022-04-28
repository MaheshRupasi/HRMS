<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Installer\Controller\Upgrader\Api;

use Doctrine\DBAL\Exception;
use OrangeHRM\Authentication\Dto\UserCredential;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Installer\Controller\AbstractInstallerRestController;
use OrangeHRM\Installer\Util\Messages;
use OrangeHRM\Installer\Util\StateContainer;
use OrangeHRM\Installer\Util\SystemConfig;
use OrangeHRM\Installer\Util\UpgraderConfigUtility;

class DatabaseConfigAPI extends AbstractInstallerRestController
{
    /**
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        $dbHost = $request->request->get('dbHost');
        $dbPort = $request->request->get('dbPort');
        $dbUser = $request->request->get('dbUser');
        $dbPassword = $request->request->get('dbPassword');
        $dbName = $request->request->get('dbName');

        StateContainer::getInstance()->storeDbInfo($dbHost, $dbPort, new UserCredential($dbUser, $dbPassword), $dbName);

        $response = $this->getResponse();

        $systemConfig = new SystemConfig();
        $upgraderConfigUtility = new UpgraderConfigUtility();

        if (!$systemConfig->checkPDOExtensionEnabled()) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return
                [
                    'error' => [
                        'status' => $response->getStatusCode(),
                        'message' => 'Please Enable PDO Extension To Proceed'
                    ]
                ];
        }

        if (!$systemConfig->checkPDOMySqlExtensionEnabled()) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return
                [
                    'error' => [
                        'status' => $response->getStatusCode(),
                        'message' => 'Please Enable PDO-MYSQL Extension To Proceed'
                    ]
                ];
        }

        $connection = $upgraderConfigUtility->checkDatabaseConnection();
        if ($connection instanceof Exception) {
            $errorMessage = $connection->getMessage();
            $errorCode = $connection->getCode();

            if ($errorCode === Messages::ERROR_CODE_INVALID_HOST_PORT) {
                $message = "The MySQL server isn't running on `$dbHost:$dbPort`. " . Messages::ERROR_MESSAGE_INVALID_HOST_PORT;
            } elseif ($errorCode === Messages::ERROR_CODE_ACCESS_DENIED) {
                $message = Messages::ERROR_MESSAGE_ACCESS_DENIED;
            } elseif ($errorCode === Messages::ERROR_CODE_DATABASE_NOT_EXISTS) {
                $message = 'Database Not Exist';
            } else {
                $message = $errorMessage . ' ' . Messages::ERROR_MESSAGE_REFER_LOG_FOR_MORE;
            }
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return
                [
                    'error' => [
                        'status' => $response->getStatusCode(),
                        'message' => $message
                    ]
                ];
        } elseif ($upgraderConfigUtility->checkDatabaseStatus()) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $response->getStatusCode(),
                    'message' => 'Failed to Proceed: Interrupted Database'
                ]
            ];
        } else {
            return [
                'data' => [
                    'dbHost' => $dbHost,
                    'dbPort' => $dbPort,
                    'dbUser' => $dbUser,
                    'dbName' => $dbName,
                ],
                'meta' => []
            ];
        }
    }

    /**
     * @inheritDoc
     */
    protected function handleGet(Request $request): array
    {
        $dbInfo = StateContainer::getInstance()->getDbInfo();
        return [
            'data' => [
                'dbHost' => $dbInfo[StateContainer::DB_HOST],
                'dbPort' => $dbInfo[StateContainer::DB_PORT],
                'dbName' => $dbInfo[StateContainer::DB_NAME],
                'dbUser' => $dbInfo[StateContainer::DB_USER],
            ],
            'meta' => []

        ];
    }
}

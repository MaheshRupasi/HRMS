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
 * Boston, MA 02110-1301, USA
 */

use OrangeHRM\Authentication\Auth\AuthProviderChain;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\Framework\Services;
use OrangeHRM\LDAP\Auth\LDAPAuthProvider;

class LDAPAuthenticationPluginConfiguration implements PluginConfigurationInterface
{
    use ServiceContainerTrait;
    use ConfigServiceTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        if ($this->getConfigService()->getLDAPSetting()->isEnable()) {
            /** @var AuthProviderChain $authProviderChain */
            $authProviderChain = $this->getContainer()->get(Services::AUTH_PROVIDER_CHAIN);
            $authProviderChain->addProvider(new LDAPAuthProvider());
        }
    }
}

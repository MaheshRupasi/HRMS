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

namespace OrangeHRM\Core\Command;

use OrangeHRM\Config\Config;
use OrangeHRM\Framework\Console\Command;
use OrangeHRM\Framework\Console\Scheduling\Schedule;
use OrangeHRM\Framework\Console\Scheduling\SchedulerConfigurationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunScheduleCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function getCommandName(): string
    {
        return 'orangehrm:run-schedule';
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pluginConfigs = Config::get('ohrm_plugin_configs');
        $schedule = new Schedule();
        foreach (array_values($pluginConfigs) as $pluginConfig) {
            $configClass = new $pluginConfig['classname']();
            if ($configClass instanceof SchedulerConfigurationInterface) {
                $configClass->schedule($schedule);
            }
        }

//        $command = $this->getApplication()->find('orangehrm:ldap-sync-user');
        $this->getIO()->note('Class: ' . get_class($this->getApplication()));

        $events = $schedule->events($schedule->dueEvents(new \DateTimeZone('Pacific/Auckland'))); // TODO:: change timezone
        $this->getIO()->note('Event count: '. \count($events));

        foreach ($events as $event) {
            $this->getIO()->note('PID: ' . $event->start());
            $this->getIO()->note($event->getOutputStream() ?? 'OutputStream: NULL');
            $this->getIO()->note($event->getWorkingDirectory() ?? 'OutputStream: NULL');
        }

        $this->getIO()->success('Success');
        return self::SUCCESS;
    }
}

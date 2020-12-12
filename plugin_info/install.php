<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function github_install() {
	$cron = cron::byClassAndFunction('github', 'pull');
	if ( ! is_object($cron)) {
		$cron = new cron();
		$cron->setClass('github');
		$cron->setFunction('pull');
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule('0 * * * *');
		$cron->save();
	}
}

function github_update() {
    foreach (eqLogic::byType('github') as $eqLogic) {
		if ($eqLogic->getConfiguration('type') == 'repo') {        
            $cmd = $eqLogic->getCmd(null, 'daily_unique_clones');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Clones par jour');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('daily_unique_clones');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(5);
                $cmd->save();
            }
            $cmd = $eqLogic->getCmd(null, 'daily_unique_views');
            if ( ! is_object($cmd)) {
                $cmd = new githubCmd();
                $cmd->setName('Vues par jour');
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId('daily_unique_views');
                $cmd->setType('info');
                $cmd->setSubType('numeric');
                $cmd->setIsHistorized(1);
                $cmd->setTemplate('dashboard','tile');
                $cmd->setTemplate('mobile','tile');
                $cmd->setOrder(6);
                $cmd->save();
            }
		}
		$eqLogic->save();
	}
}

function github_remove() {
	$cron = cron::byClassAndFunction('github', 'pull');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
}
?>
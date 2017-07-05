<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

/**
 * Description of IPFilter
 *
 * @author Carlos García Gómez
 */
class IPFilter {

    const MAX_ATTEMPTS = 5;
    const BAN_SECONDS = 600;

    private $filePath;
    private $ipList;

    public function __construct($folder = '') {
        $this->filePath = $folder . '/Cache/ip.list';
        $this->ipList = [];

        if (file_exists($this->filePath)) {
            /// Read IP list file
            $file = fopen($this->filePath, 'r');
            if ($file) {
                while (!feof($file)) {
                    $line = explode(';', trim(fgets($file)));
                    if (count($line) === 3 && (int)$line[2] > time()) { /// if not expired
                        $this->ipList[] = ['ip' => $line[0], 'count' => (int) $line[1], 'expire' => (int) $line[2]];
                    }
                }

                fclose($file);
            }
        }
    }

    public function isBanned($ip) {
        $banned = FALSE;

        foreach ($this->ipList as $line) {
            if ($line['ip'] === $ip && $line['count'] > self::MAX_ATTEMPTS) {
                $banned = TRUE;
                break;
            }
        }

        return $banned;
    }

    public function setAttempt($ip) {
        $found = FALSE;
        foreach ($this->ipList as $key => $line) {
            if ($line['ip'] === $ip) {
                $this->ipList[$key]['count'] ++;
                $this->ipList[$key]['expire'] = time() + self::BAN_SECONDS;
                $found = TRUE;
                break;
            }
        }

        if (!$found) {
            $this->ipList[] = ['ip' => $ip, 'count' => 1, 'expire' => time() + self::BAN_SECONDS];
        }
        
        $this->save();
    }

    private function save() {
        $file = fopen($this->filePath, 'w');
        if ($file) {
            foreach ($this->ipList as $line) {
                fwrite($file, $line['ip'] . ';' . $line['count'] . ';' . $line['expire']."\n");
            }

            fclose($file);
        }
    }

}

<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

 /**
 * Helper class for generating and organizing log files.
 */
namespace Facebook\BusinessExtension\Helper;

//TODO(T135444965): Remove/upgrade this
class LogOrganization
{
    // 4096 -- Good balance for tracking backwards
    const BUFFER = 4096;

    static $criticalLines = array();

    public static function organizeLogs() {
        $arrayOfFiles = array("var/log/debug.log", "var/log/cron.log", "var/log/magento.cron.log", "var/log/system.log");
        $countCrit = 0;

        foreach($arrayOfFiles as $value) {
            if (!file_exists($value)) {
                continue;
            }

            $fp = fopen($value, 'r');
            $pos = -2; // Skip final new line character (Set to -1 if not present)
            $currentLine = '';

            while (-1 !== fseek($fp, $pos, SEEK_END)) {
                $char = fgetc($fp);
                if ("\n" == $char) {
                    if (substr($currentLine, 0, 3) == "[20") {
                        $time = strtotime(substr($currentLine, 1, 20));
                        if ($time > strtotime('-7 day')) {
                            break;
                        }

                        // Saving only critical log entries with "Facebook" phrase
                        if (strpos($currentLine, "CRITICAL") !== false &&
                            strpos($currentLine, "Facebook") !== false) {
                            self::$criticalLines[] = $currentLine;
                            $countCrit++;
                        }
                        $currentLine = '';

                        if ($countCrit == 500) {
                            break;
                        }
                    }
                } else {
                    $currentLine = $char . $currentLine;
                }
                $pos--;
            }

            if (substr($currentLine, 0, 3) == "[20") {
                $time = strtotime(substr($currentLine, 1, 20));
                if ($time > strtotime('-7 day')) {
                    break;
                }

                if (strpos($currentLine, "CRITICAL") !== false  &&
                    strpos($currentLine, "Facebook") !== false) {
                    self::$criticalLines[] = $currentLine;
                    $countCrit++;
                }
                $currentLine = '';
            }

            $countCrit = 0;
        }
        fclose($fp);

        $amuLogs = self::tailCustom("var/log/facebook-business-extension.log", 100);
        $amuLogsArr = explode("\n", $amuLogs);
        self::$criticalLines = array_merge(self::$criticalLines, $amuLogsArr);

        usort(self::$criticalLines, function ($x, $y) {
            $t1 = strtotime(substr($x, 1, 19));
            $t2 = strtotime(substr($y, 1, 19));

            return ($t1 - $t2);
        });

        return self::$criticalLines;
    }

    public static function tailCustom($filepath, $lines) {
        $f = fopen($filepath, "rb");
        if ($f === false) {
            return false;
        }

        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }

        $output = '';
        $chunk = '';

        while (ftell($f) > 0 && $lines >=   0) {
            $seek = min(ftell($f), self::BUFFER);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }

        fclose($f);
        return trim($output);
    }
}
